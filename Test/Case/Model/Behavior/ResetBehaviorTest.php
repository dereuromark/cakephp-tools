<?php

App::uses('ResetBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('AppModel', 'Model');

class ResetBehaviorTest extends MyCakeTestCase {

	public $ResetBehavior;

	public $Model;

	public $fixtures = array('core.comment');

	public function setUp() {
		parent::setUp();

		$this->ResetBehavior = new ResetBehavior();

		$this->Model = ClassRegistry::init('MyComment');
		$this->Model->Behaviors->attach('Tools.Reset');
	}

	public function testObject() {
		$this->assertTrue(is_object($this->ResetBehavior));
		$this->assertInstanceOf('ResetBehavior', $this->ResetBehavior);
	}

	public function testResetRecords() {
		$x = $this->Model->find('first', array('order' => array('updated'=>'DESC')));
		$this->assertTrue($x['MyComment']['updated'] < '2007-12-31');

		$result = $this->Model->resetRecords();
		$this->assertTrue($result);

		$x = $this->Model->find('first', array('order' => array('updated'=>'ASC')));
		$this->assertTrue($x['MyComment']['updated'] > (date('Y')-1) . '-12-31');
	}

}

class MyComment extends AppModel {

	public $fixture = 'core.comment';

	public $useTable = 'comments';

}