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
		$x = $this->Model->find('first', array('order' => array('updated' => 'DESC')));

		$result = $this->Model->resetRecords();
		$this->assertTrue((bool)$result);

		$y = $this->Model->find('first', array('order' => array('updated' => 'DESC')));
		$this->assertSame($x, $y);
	}

	public function testResetRecordsWithUpdatedTimestamp() {
		$this->Model->Behaviors->unload('Reset');
		$this->Model->Behaviors->load('Tools.Reset', array('updateTimestamp' => true));

		$x = $this->Model->find('first', array('order' => array('updated' => 'DESC')));
		$this->assertTrue($x['MyComment']['updated'] < '2007-12-31');

		$result = $this->Model->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Model->find('first', array('order' => array('updated' => 'ASC')));
		$this->assertTrue($x['MyComment']['updated'] > (date('Y') - 1) . '-12-31');
	}

	public function testResetWithCallback() {
		$this->Model->Behaviors->unload('Reset');
		$this->Model->Behaviors->load('Tools.Reset', array('callback' => 'customCallback'));

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$this->assertEquals('Second Comment for Second Article', $x['MyComment']['comment']);

		$result = $this->Model->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$expected = 'Second Comment for Second Article xyz';
		$this->assertEquals($expected, $x['MyComment']['comment']);
	}

	public function testResetWithObjectCallback() {
		$this->Model->Behaviors->unload('Reset');
		$this->Model->Behaviors->load('Tools.Reset', array('callback' => array($this->Model, 'customObjectCallback')));

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$this->assertEquals('Second Comment for Second Article', $x['MyComment']['comment']);

		$result = $this->Model->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$expected = 'Second Comment for Second Article xxx';
		$this->assertEquals($expected, $x['MyComment']['comment']);
	}

	public function testResetWithStaticCallback() {
		$this->Model->Behaviors->unload('Reset');
		$this->Model->Behaviors->load('Tools.Reset', array('callback' => 'MyComment::customStaticCallback'));

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$this->assertEquals('Second Comment for Second Article', $x['MyComment']['comment']);

		$result = $this->Model->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$expected = 'Second Comment for Second Article yyy';
		$this->assertEquals($expected, $x['MyComment']['comment']);
	}

	public function testResetWithCallbackAndFields() {
		$this->Model->Behaviors->unload('Reset');
		$this->Model->Behaviors->load('Tools.Reset', array(
			'fields' => array('id'),
			'updateFields' => array('comment'),
			'callback' => 'MyComment::fieldsCallback'));

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$this->assertEquals('Second Comment for Second Article', $x['MyComment']['comment']);

		$result = $this->Model->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$expected = 'foo';
		$this->assertEquals($expected, $x['MyComment']['comment']);
	}

	public function testResetWithCallbackAndFieldsAutoAdded() {
		$this->Model->Behaviors->unload('Reset');
		$this->Model->Behaviors->load('Tools.Reset', array(
			'fields' => array('id'),
			'updateFields' => array('id'),
			'callback' => 'MyComment::fieldsCallbackAuto'));

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$this->assertEquals('Second Comment for Second Article', $x['MyComment']['comment']);

		$result = $this->Model->resetRecords();
		$this->assertTrue((bool)$result);

		$x = $this->Model->find('first', array('conditions' => array('id' => 6)));
		$expected = 'bar';
		$this->assertEquals($expected, $x['MyComment']['comment']);
	}

}

class MyComment extends AppModel {

	public $fixture = 'core.comment';

	public $useTable = 'comments';

	public $displayField = 'comment';

	public function customCallback($data, &$updateFields) {
		$data[$this->alias][$this->displayField] .= ' xyz';
		$fields[] = 'some_other_field';
		return $data;
	}

	public function customObjectCallback($data, &$updateFields) {
		$data[$this->alias][$this->displayField] .= ' xxx';
		$updateFields[] = 'some_other_field';
		return $data;
	}

	public static function customStaticCallback($data, &$updateFields) {
		$data['MyComment']['comment'] .= ' yyy';
		$updateFields[] = 'some_other_field';
		return $data;
	}

	public static function fieldsCallback($data, &$updateFields) {
		$data['MyComment']['comment'] = 'foo';
		return $data;
	}

	public static function fieldsCallbackAuto($data, &$updateFields) {
		$data['MyComment']['comment'] = 'bar';
		$updateFields[] = 'comment';
		return $data;
	}

}
