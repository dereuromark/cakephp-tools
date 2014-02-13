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

}
