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
	public $fixtures = array('plugin.tools.soft_delete_post');

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
