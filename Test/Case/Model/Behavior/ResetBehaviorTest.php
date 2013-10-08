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
		$this->Model->Behaviors->load('Tools.Reset');
	}

	public function testObject() {
		$this->assertTrue(is_object($this->ResetBehavior));
		$this->assertInstanceOf('ResetBehavior', $this->ResetBehavior);
	}

	public function testResetRecords() {
		$x = $this->Model->find('first', array('order' => array('updated'=>'DESC')));

		$result = $this->Model->resetRecords();
		$this->assertTrue($result);

		$y = $this->Model->find('first', array('order' => array('updated'=>'DESC')));
		$this->assertSame($x, $y);
	}

	public function testResetRecordsWithUpdatedTimestamp() {
		$this->Model->Behaviors->unload('Reset');
		$this->Model->Behaviors->load('Tools.Reset', array('updateTimestamp' => true));

		$x = $this->Model->find('first', array('order' => array('updated'=>'DESC')));
		$this->assertTrue($x['MyComment']['updated'] < '2007-12-31');

		$result = $this->Model->resetRecords();
		$this->assertTrue($result);

		$x = $this->Model->find('first', array('order' => array('updated'=>'ASC')));
		$this->assertTrue($x['MyComment']['updated'] > (date('Y')-1) . '-12-31');
	}

	public function testResetWithCallback() {
		$this->Model->Behaviors->unload('Reset');
		$this->Model->Behaviors->load('Tools.Reset', array('callback' => 'customCallback'));

		$x = $this->Model->find('first', array('conditions' => array('id'=>6)));
		$this->assertEquals('Second Comment for Second Article', $x['MyComment']['comment']);

		$result = $this->Model->resetRecords();
		$this->assertTrue($result);

		$x = $this->Model->find('first', array('conditions' => array('id'=>6)));
		$expected = 'Second Comment for Second Article xyz';
		$this->assertEquals($expected, $x['MyComment']['comment']);
	}

}

class MyComment extends AppModel {

	public $fixture = 'core.comment';

	public $useTable = 'comments';

	public $displayField = 'comment';

	public function customCallback(&$data, &$fields) {
		$data[$this->alias][$this->displayField] .= ' xyz';
		$fields[] = 'some_other_field';
	}

}
