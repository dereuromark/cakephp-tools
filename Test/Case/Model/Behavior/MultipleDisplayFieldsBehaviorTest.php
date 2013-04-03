<?php
App::uses('MultipleDisplayFieldsBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MultipleDisplayFieldsBehaviorTest extends MyCakeTestCase {

	public $fixtures = array('core.comment', 'core.user');

	public $Comment;

	public $MultipleDisplayFieldsBehavior;

	public function setUp() {
		parent::setUp();

		$this->MultipleDisplayFieldsBehavior = new MultipleDisplayFieldsBehavior();

		$this->Comment = ClassRegistry::init('Comment');
		$this->Comment->bindModel(array('belongsTo' => array('User')), false);
		$this->Comment->displayField = 'comment';
	}

	public function testObject() {
		$this->assertTrue(is_object($this->MultipleDisplayFieldsBehavior));
		$this->assertInstanceOf('MultipleDisplayFieldsBehavior', $this->MultipleDisplayFieldsBehavior);
	}

	public function testSimple() {
		$this->Comment->Behaviors->load('Tools.MultipleDisplayFields');
		$res = $this->Comment->find('first');
		$this->assertSame(7, count($res['Comment']));
		$this->Comment->Behaviors->unload('MultipleDisplayFields');

		// auto %s pattern
		$config = array(
			'fields' => array(
				$this->Comment->alias . '.comment', $this->Comment->alias . '.published'
			),
		);
		$this->Comment->Behaviors->load('Tools.MultipleDisplayFields', $config);
		$res = $this->Comment->find('list');
		$this->debug($res);
		$this->assertEquals('First Comment for First Article Y', $res[1]);
		$this->Comment->Behaviors->unload('MultipleDisplayFields');

		// custom pattern
		$config = array(
			'fields' => array(
				$this->Comment->alias . '.comment', $this->Comment->alias . '.published'
			),
			'pattern' => '%s (%s)',
		);
		$this->Comment->Behaviors->load('Tools.MultipleDisplayFields', $config);
		$res = $this->Comment->find('list');
		$this->debug($res);
		$this->assertEquals('First Comment for First Article (Y)', $res[1]);
	}

	public function testAdvanced() {
		//$res = $this->Comment->find('first', array('contain' => array('User')));
		$config = array(
			'fields' => array(
				$this->Comment->alias . '.comment', $this->Comment->User->alias . '.user', $this->Comment->alias . '.published'
			),
			'displayField' => array('display_field'),
			'pattern' => '%s by %s (%s)',
		);
		$this->Comment->Behaviors->load('Tools.MultipleDisplayFields', $config);
		$res = $this->Comment->find('list', array('contain' => array('User')));
		$this->debug($res);
		$this->assertEquals('First Comment for First Article by Y (nate)', $res[1]);
	}

}
