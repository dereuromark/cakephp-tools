<?php

App::uses('KeyValueBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class KeyValueBehaviorTest extends MyCakeTestCase {

	public $fixtures = array('plugin.tools.key_value', 'core.user');

	public $KeyValueBehavior;

	public $Model;

	public function setUp() {
		parent::setUp();

		$this->KeyValueBehavior = new KeyValueBehavior();
		$this->Model = ClassRegistry::init('User');
		$this->Model->Behaviors->load('Tools.KeyValue');
	}

	public function testObject() {
		$this->assertTrue(is_object($this->KeyValueBehavior));
		$this->assertInstanceOf('KeyValueBehavior', $this->KeyValueBehavior);
	}

	public function testValidate() {
		$res = $this->Model->validateSection(array('User' => array('x' => 1, 'y' => '')));
		$this->assertTrue(!empty($res));

		$this->Model->keyValueValidate = array(
			'User' => array('y' => 'notEmpty'),
		);
		$res = $this->Model->validateSection(array('User' => array('x' => 1, 'y' => '')));
		$this->assertFalse($res);

		$res = $this->Model->validateSection(array('User' => array('x' => 1, 'y' => '1')));
		$this->assertTrue(!empty($res));
	}

	public function testSaveAndGet() {
		$this->Model->saveSection(1, array('User' => array('x' => 1, 'y' => 'z')));

		$res = $this->Model->getSection(2);
		$this->assertTrue(empty($res));

		$res = $this->Model->getSection(1);
		$this->assertTrue(!empty($res['User']));
		$this->assertEquals('z', $res['User']['y']);

		$res = $this->Model->saveSection(2, array('User' => array('x' => 1, 'y' => 'z')), 'Profile');
		$this->assertTrue($res);

		$res = $this->Model->getSection(2);
		$this->assertTrue(empty($res));

		$res = $this->Model->saveSection(2, array('User' => array('e' => 'f'), 'Profile' => array('x' => 3, 'y' => 'abc')), 'Profile');
		$this->assertTrue($res);

		$res = $this->Model->getSection(2);
		$this->debug($res);
		$this->assertTrue(!empty($res['Profile']));

		$res = $this->Model->getSection(2, 'Profile');
		$this->assertIdentical(array('x' => '3', 'y' => 'abc'), $res);
	}

	public function testDefaults() {
		$this->Model->keyValueDefaults = array(
			'User' => array(
				'x' => 0,
				'y' => '',
				'z' => '123',
			)
		);
		$this->Model->Behaviors->unload('KeyValue');
		$this->Model->Behaviors->load('Tools.KeyValue');

		$this->Model->saveSection(0, array('User' => array('x' => 1, 'y' => 'z')));

		$res = $this->Model->getSection(0, 'User');
		$this->assertEquals(array('x' => 1, 'y' => 'z', 'z' => 123), $res);

		$res = $this->Model->getSection(0, 'User', 'y');
		$this->assertEquals('z', $res);

		$res = $this->Model->getSection(0, 'User', 'a');
		$this->assertEquals(null, $res);

		$res = $this->Model->getSection(0, 'User', 'z');
		$this->assertEquals('123', $res);
	}

	public function testReset() {
		$this->Model->saveSection(0, array('User' => array('x' => 1, 'y' => 'z')));
		$res = $this->Model->Behaviors->KeyValue->KeyValue->find('count');
		$this->assertEquals(3, $res);

		$res = $this->Model->resetSection(0, 'User', 'y');
		$this->assertTrue($res);
		$res = $this->Model->Behaviors->KeyValue->KeyValue->find('count');
		$this->assertEquals(2, $res);

		$this->Model->Behaviors->unload('KeyValue');
		$this->Model->Behaviors->load('Tools.KeyValue', array('defaultOnEmpty' => true));
		$this->Model->keyValueDefaults = array(
			'User' => array(
				'x' => 0,
				'y' => '',
				'z' => '123',
			)
		);
		$this->Model->saveSection(0, array('User' => array('x' => 1, 'y' => 'z', 'z' => 123)));
		$res = $this->Model->Behaviors->KeyValue->KeyValue->find('count');
		$this->assertEquals(4, $res);

		$res = $this->Model->saveSection(0, array('User' => array('x' => 0, 'y' => null)));
		$this->assertTrue($res);
		$res = $this->Model->Behaviors->KeyValue->KeyValue->find('count');
		$this->assertEquals(3, $res);

		$this->Model->Behaviors->unload('KeyValue');
		$this->Model->Behaviors->load('Tools.KeyValue', array('deleteIfDefault' => true));
		$res = $this->Model->saveSection(0, array('User' => array('z' => '123')));
		$this->assertTrue($res);
		$res = $this->Model->Behaviors->KeyValue->KeyValue->find('count');
		$this->assertEquals(2, $res);

		$res = $this->Model->resetSection(1);
		$res = $this->Model->Behaviors->KeyValue->KeyValue->find('count');
		$this->assertEquals(1, $res);

		$res = $this->Model->resetSection();
		$res = $this->Model->Behaviors->KeyValue->KeyValue->find('count');
		$this->assertEquals(0, $res);
	}

}
