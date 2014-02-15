<?php

App::uses('NamedScopeBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class NamedScopeBehaviorTest extends MyCakeTestCase {

	public $NamedScopeBehavior;

	public $Comment;

	public $fixtures = array('core.comment', 'core.user');

	public function setUp() {
		parent::setUp();

		$this->NamedScopeBehavior = new NamedScopeBehavior();

		$this->Comment = ClassRegistry::init('Comment');
		$this->Comment->bindModel(array('belongsTo' => array('User')), false);
		$this->Comment->displayField = 'comment';
		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');
	}

	public function testObject() {
		$this->assertTrue(is_object($this->NamedScopeBehavior));
		$this->assertInstanceOf('NamedScopeBehavior', $this->NamedScopeBehavior);
	}

	/**
	 * NamedScopeBehaviorTest::testScope()
	 *
	 * @return void
	 */
	public function testScope() {
		$result = $this->Comment->scope('active');
		$this->assertNull($result);

		$this->Comment->scope('active', array('published' => 'Y'));
		$result = $this->Comment->scope('active');
		$this->assertEquals(array('published' => 'Y'), $result);

		$this->Comment->scope('active', array('published' => 'Y', 'active' => 1));
		$result = $this->Comment->scope('active');
		$this->assertEquals(array('published' => 'Y', 'active' => 1), $result);

		$result = $this->Comment->scope();
		$this->assertEquals(array('active' => array('published' => 'Y', 'active' => 1)), $result);
	}

	/**
	 * NamedScopeBehaviorTest::testBasic()
	 *
	 * @return void
	 */
	public function testBasic() {
		$before = $this->Comment->find('count');

		$this->Comment->scope('active', array('published' => 'Y'));
		$options = array(
			'scope' => array('active')
		);
		$after = $this->Comment->find('count', $options);
		$this->assertTrue($before > $after);
		$this->assertSame(5, $after);
	}

	/**
	 * NamedScopeBehaviorTest::testCrossModel()
	 *
	 * @return void
	 */
	public function testCrossModel() {
		$before = $this->Comment->find('count');

		$this->Comment->scope('active', array('Comment.published' => 'Y'));
		$this->Comment->User->scope('senior', array('User.id <' => '3'));

		$options = array(
			'contain' => array('User'),
			'scope' => array('Comment.active', 'User.senior')
		);
		$after = $this->Comment->find('count', $options);
		$this->assertTrue($before > $after);
		$this->assertSame(4, $after);
	}

	/**
	 * NamedScopeBehaviorTest::testCrossModelWithAttributeScope()
	 *
	 * @return void
	 */
	public function testCrossModelWithAttributeScope() {
		$this->Comment->scopes = array('active' => array('Comment.published' => 'Y'));
		$this->Comment->User->scopes = array('senior' => array('User.id <' => '2'));

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');

		$options = array(
			'contain' => array('User'),
			'scope' => array('Comment.active', 'User.senior')
		);
		$after = $this->Comment->find('count', $options);
		$this->assertSame(2, $after);
	}

	/**
	 * NamedScopeBehaviorTest::testScopedFind()
	 *
	 * @return void
	 */
	public function testScopedFind() {
		$this->Comment->scopes = array('active' => array('Comment.published' => 'Y'));
		$this->Comment->User->scopes = array('senior' => array('User.id <' => '2'));

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');

		$this->Comment->scopedFinds = array(
			'activeAndSenior' => array(
				'name' => 'Active and Senior',
				'find' => array(
					'virtualFields' => array(
						//'fullname' => "CONCAT(User.id, '-', User.user)"
					),
					'options' => array(
						'scope' => array('Comment.active', 'User.senior'),
						'contain' => array('User'),
						'fields' => array('User.id', 'User.user'),
						'order' => array('User.user' => 'ASC'),
					),
				)
			)
		);
		$result = $this->Comment->scopedFind('activeAndSenior');
		$this->assertSame(2, count($result));

		$result = $this->Comment->scopedFind('activeAndSenior', array('type' => 'count'));
		$this->assertSame(2, $result);
	}

	/**
	 * NamedScopeBehaviorTest::testScopedFindWithVirtualFields()
	 *
	 * @return void
	 */
	public function testScopedFindWithVirtualFields() {
		$this->Comment->scopes = array('active' => array('Comment.published' => 'Y'));
		$this->Comment->User->scopes = array('senior' => array('User.id <' => '2'));

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');

		$this->Comment->scopedFinds = array(
			'activeAndSenior' => array(
				'name' => 'Active and Senior',
				'find' => array(
					'virtualFields' => array(
						'fullname' => "CONCAT(User.id, '-', User.user)"
					),
					'options' => array(
						'scope' => array('Comment.active', 'User.senior'),
						'contain' => array('User'),
						'fields' => array('User.id', 'fullname'),
						'order' => array('fullname' => 'ASC'),
					),
				)
			)
		);
		$result = $this->Comment->scopedFind('activeAndSenior');
		$this->assertSame(2, count($result));

		$scopedFinds = $this->Comment->scopedFinds();
		$this->assertSame(array('activeAndSenior' => 'Active and Senior'), $scopedFinds);
	}

	/**
	 * NamedScopeBehaviorTest::testScopedFindWithLimit()
	 *
	 * @return void
	 */
	public function testScopedFindWithLimit() {
		$this->Comment->scopes = array('active' => array('Comment.published' => 'Y'));
		$this->Comment->User->scopes = array('senior' => array('User.id <' => '2'));

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');

		$this->Comment->scopedFinds = array(
			'activeAndSenior' => array(
				'name' => 'Active and Senior',
				'find' => array(
					'virtualFields' => array(
						'fullname' => "CONCAT(User.id, '-', User.user)"
					),
					'options' => array(
						'scope' => array('Comment.active', 'User.senior'),
						'contain' => array('User'),
						'fields' => array('User.id', 'fullname'),
						'order' => array('fullname' => 'ASC'),
					),
				)
			)
		);
		$result = $this->Comment->scopedFind('activeAndSenior', array('options' => array('limit' => 1)));
		$this->assertSame(1, count($result));
	}

	/**
	 * NamedScopeBehaviorTest::testException()
	 *
	 * @expectedException RuntimeException
	 * @return void
	 */
	public function testException() {
		$this->Comment->scopedFind('foo');
	}

}
