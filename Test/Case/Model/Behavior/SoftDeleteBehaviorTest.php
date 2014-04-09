<?php
/**
 * Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('Behavior', 'Model');
App::uses('SoftDeleteBehavior', 'Tools.Model/Behavior');

/**
 * SoftDeleteBehavior Test case
 */
class SoftDeleteBehaviorTest extends CakeTestCase {

	/**
	 * Fixtures property
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.tools.soft_delete_category',
		'plugin.tools.soft_delete_post',
		'plugin.tools.soft_delete_user'
	);

	/**
	 * Creates the model instance
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Post = new SoftDeletedPost();
		$this->Behavior = new SoftDeleteTestBehavior();
	}

	/**
	 * Destroy the model instance
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Post);
		unset($this->Behavior);
		ClassRegistry::flush();
	}

	/**
	 * Test saving a item
	 *
	 * @return void
	 */
	public function testSoftDelete() {
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data[$this->Post->alias][$this->Post->primaryKey], 1);
		$this->assertFalse($this->Post->softDeleted);
		$result = $this->Post->delete(1);
		$this->assertFalse($result);
		$this->assertTrue($this->Post->softDeleted);

		$data = $this->Post->read(null, 1);
		$this->assertEmpty($data);
		$this->Post->Behaviors->unload('SoftDeleteTest');
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data['Post']['deleted'], 1);

		//$result = abs(strtotime($data['Post']['updated']) - strtotime($data['Post']['deleted_date']));
		//$this->assertWithinMargin($result, 0, 1, $data['Post']['updated'].'/'.$data['Post']['deleted_date']);
	}

	/**
	 * Test that overwriting delete() on AppModel level makes SoftDelete return true for delete()
	 *
	 * @return void
	 */
	public function testSoftDeleteReturningTrue() {
		$this->Post = new ModifiedSoftDeletedPost();
		$this->Post->Behaviors->load('Tools.SoftDelete');

		$data = $this->Post->read(null, 1);
		$this->assertEquals($data[$this->Post->alias][$this->Post->primaryKey], 1);
		//$this->assertFalse($this->Post->softDeleted);
		$result = $this->Post->delete(1);
		$this->assertTrue($result);
		//$this->assertTrue($this->Post->softDeleted);
	}

	/**
	 * TestUnDelete
	 *
	 * @return void
	 */
	public function testUnDelete() {
		$data = $this->Post->read(null, 1);
		$result = $this->Post->delete(1);
		$result = $this->Post->undelete(1);
		$data = $this->Post->read(null, 1);
		$this->assertEquals($data['Post']['deleted'], 0);
	}

	/**
	 * TestSoftDeletePurge
	 *
	 * @return void
	 */
	public function testSoftDeletePurge() {
		$this->Post->Behaviors->disable('SoftDeleteTest');
		$data = $this->Post->read(null, 3);
		$this->assertTrue(!empty($data));
		$this->Post->Behaviors->enable('SoftDeleteTest');
		$data = $this->Post->read(null, 3);
		$this->assertEmpty($data);
		$count = $this->Post->purgeDeletedCount();
		$this->assertEquals($count, 1);
		$this->Post->purgeDeleted();

		$data = $this->Post->read(null, 3);
		$this->assertEmpty($data);
		$this->Post->Behaviors->disable('SoftDeleteTest');
		$data = $this->Post->read(null, 3);
		$this->assertEmpty($data);
	}

	/**
	 * testSoftDeleteWithCounterCache
	 *
	 * @return void
	 */
	public function testSoftDeleteWithCounterCache() {
		$this->Post->Category->id = 1;
		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(2, $count);

		$this->assertFalse($this->Post->softDeleted);
		$this->Post->delete(1);
		$this->assertTrue($this->Post->softDeleted);

		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(1, $count);
	}

	/**
	 * testSoftDeleteWithMultipleCounterCache
	 *
	 * @return void
	 */
	public function testSoftDeleteWithMultipleCounterCache() {
		$this->Post->belongsTo['Category']['counterCache'] = array(
			'post_count' => array('Post.deleted' => false),
			'deleted_post_count' => array('Post.deleted' => true)
		);

		$this->Post->Category->id = 1;
		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(2, $count);
		$count = $this->Post->Category->field('deleted_post_count');
		$this->assertEquals(0, $count);

		$this->assertFalse($this->Post->softDeleted);
		$this->Post->delete(1);
		$this->assertTrue($this->Post->softDeleted);

		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(1, $count);
		$count = $this->Post->Category->field('deleted_post_count');
		$this->assertEquals(1, $count);
	}

	/**
	 * testSoftDeleteWithCounterCacheOnMultipleAssociations
	 *
	 * @return void
	 */
	public function testSoftDeleteWithCounterCacheOnMultipleAssociations() {
		$this->Post->bindModel(array(
			'belongsTo' => array(
				'User' => array(
					'className' => 'SoftDeleteUser',
					'counterCache' => true
				)
			)
		),
		false);

		$this->Post->Category->id = 1;
		$this->Post->User->id = 1;

		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(2, $count);

		$count = $this->Post->User->field('post_count');
		$this->assertEquals(2, $count);

		$this->assertFalse($this->Post->softDeleted);
		$this->Post->delete(1);
		$this->assertTrue($this->Post->softDeleted);

		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(1, $count);

		$count = $this->Post->User->field('post_count');
		$this->assertEquals(1, $count);
	}

	/**
	 * testSoftDeleteWithoutCounterCache
	 *
	 * @return void
	 */
	public function testSoftDeleteWithoutCounterCache() {
		$Post = $this->getMock('SoftDeletedPost', array('updateCounterCache'));
		$Post->expects($this->never())->method('updateCounterCache');

		$Post->belongsTo = array();
		$Post->delete(1);
	}

	/**
	 * testUnDeleteWithCounterCache
	 *
	 * @return void
	 */
	public function testUnDeleteWithCounterCache() {
		$this->Post->Category->id = 2;
		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(0, $count);

		$this->assertEmpty($this->Post->read(null, 3));

		$this->Post->undelete(3);

		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(1, $count);
	}

	/**
	 * testUnDeleteWithMultipleCounterCache
	 *
	 * @return void
	 */
	public function testUnDeleteWithMultipleCounterCache() {
		$this->Post->belongsTo['Category']['counterCache'] = array(
			'post_count' => array('Post.deleted' => false),
			'deleted_post_count' => array('Post.deleted' => true)
		);

		$this->Post->Category->id = 2;
		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(0, $count);
		$count = $this->Post->Category->field('deleted_post_count');
		$this->assertEquals(1, $count);

		$this->assertEmpty($this->Post->read(null, 3));

		$this->Post->undelete(3);

		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(1, $count);
		$count = $this->Post->Category->field('deleted_post_count');
		$this->assertEquals(0, $count);
	}

	/**
	 * testUnDeleteWithCounterCacheOnMultipleAssociations
	 *
	 * @return void
	 */
	public function testUnDeleteWithCounterCacheOnMultipleAssociations() {
		$this->Post->bindModel(array(
				'belongsTo' => array(
					'User' => array(
						'className' => 'SoftDeleteUser',
						'counterCache' => true
					)
				)
			),
			false);

		$this->Post->Category->id = 2;
		$this->Post->User->id = 1;

		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(0, $count);
		$count = $this->Post->User->field('post_count');
		$this->assertEquals(2, $count);

		$this->assertEmpty($this->Post->read(null, 3));

		$this->Post->undelete(3);

		$count = $this->Post->Category->field('post_count');
		$this->assertEquals(1, $count);
		$count = $this->Post->User->field('post_count');
		$this->assertEquals(3, $count);
	}

	/**
	 * testUnDeleteWithoutCounterCache
	 *
	 * @return void
	 */
	public function testUnDeleteWithoutCounterCache() {
		$Post = $this->getMock('SoftDeletedPost', array('updateCounterCache'));
		$Post->expects($this->never())->method('updateCounterCache');

		$Post->belongsTo = array();
		$Post->undelete(3);
	}

		// $result = $this->Model->read();
		// $this->assertEquals($result['SoftDeletedPost']['slug'], 'fourth_Post');

		///Should not update
		// $this->Model->saveField('title', 'Fourth Post (Part 1)');
		// $result = $this->Model->read();
		// $this->assertEquals($result['SoftDeletedPost']['slug'], 'fourth_Post');

		////Should update
		// $this->Model->Behaviors->SluggableTest->settings['SoftDeletedPost']['update'] = true;
		// $this->Model->saveField('title', 'Fourth Post (Part 2)');
		// $result = $this->Model->read();
		// $this->assertEquals($result['SoftDeletedPost']['slug'], 'fourth_Post_part_2');

		////Updating the item should not update the slug
		// $this->Model->saveField('body', 'Here goes the content.');
		// $result = $this->Model->read();
		// $this->assertEquals($result['SoftDeletedPost']['slug'], 'fourth_Post_part_2');

}

/**
 * SoftDeleteTestBehavior
 *
 */
class SoftDeleteTestBehavior extends SoftDeleteBehavior {
}

/**
 * SoftDeleteCategory
 *
 */
class SoftDeleteCategory extends CakeTestModel {

	/**
	 * Use Table
	 *
	 * @var string
	 */
	public $useTable = 'soft_delete_categories';

	/**
	 * Alias
	 *
	 * @var string
	 */
	public $alias = 'Category';

}

/**
 * SoftDeleteUser
 *
 */
class SoftDeleteUser extends CakeTestModel {

	/**
	 * Use Table
	 *
	 * @var string
	 */
	public $useTable = 'soft_delete_users';

	/**
	 * Alias
	 *
	 * @var string
	 */
	public $alias = 'User';

}

/**
 * SoftDeletedPost
 *
 */
class SoftDeletedPost extends CakeTestModel {

	/**
	 * Use Table
	 *
	 * @var string
	 */
	public $useTable = 'soft_delete_posts';

	/**
	 * Behaviors
	 *
	 * @var array
	 */
	public $actsAs = array('Tools.SoftDeleteTest');

	/**
	 * Alias
	 *
	 * @var string
	 */
	public $alias = 'Post';

	/**
	 * belongsTo associations
	 *
	 * @var array
	 */
	public $belongsTo = array(
		'Category' => array(
			'className' => 'SoftDeleteCategory',
			'counterCache' => true
		)
	);

}

/**
 * SoftDeletedPost returning true on delete()
 *
 */
class ModifiedSoftDeletedPost extends SoftDeletedPost {

	public function delete($id = null, $cascade = true) {
		$result = parent::delete($id, $cascade);
		if (!$result && $this->Behaviors->loaded('SoftDelete')) {
			return $this->softDeleted;
		}
		return $result;
	}
}
