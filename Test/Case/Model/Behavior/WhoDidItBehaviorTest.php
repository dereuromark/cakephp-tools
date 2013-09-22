<?php

App::uses('WhoDidItBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class WhoDidItBehaviorTest extends MyCakeTestCase {

	/**
	 * Model for tests
	 *
	 * @var
	 */
	public $Model;

	/**
	 * Fixtures for tests
	 *
	 * @var array
	 */
	public $fixtures = array('plugin.tools.who_did_it_player', 'core.cake_session');

	public function setUp() {
		parent::setUp();

		$this->Model = ClassRegistry::init('WhoDidItPlayer');
		$this->Model->Behaviors->load('Tools.WhoDidIt');
	}

	public function testModel() {
		$this->assertInstanceOf('AppModel', $this->Model);
	}

	public function testSaveWithDefaultSettings() {
		// create (id + name + created + modified)
		$data = array(
			'name' => 'Foo'
		);
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);
		$this->assertTrue(count($res['WhoDidItPlayer']) === 4);

		// update (id + name + modified)
		$res = $this->Model->save($data + array('id' => $this->Model->id));
		$this->assertTrue((bool)$res);
		$this->assertTrue(count($res['WhoDidItPlayer']) === 3);

		// create a new one being logged in
		CakeSession::write('Auth.User.id', '1');
		$data = array(
			'name' => 'Foo2'
		);
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);
		$this->assertTrue(count($res['WhoDidItPlayer']) === 6);
		$this->assertEquals('1', $res['WhoDidItPlayer']['created_by']);
		$this->assertEquals('1', $res['WhoDidItPlayer']['modified_by']);

		// now update being logged in
		$res = $this->Model->save($data + array('id' => $this->Model->id));
		$this->assertTrue((bool)$res);
		$this->assertTrue(count($res['WhoDidItPlayer']) === 4);
		$this->assertEquals('1', $res['WhoDidItPlayer']['modified_by']);
	}

	/**
	 * Usually the fields modified_by and created_by should not be present in forms.
	 * For some admin views this might be the case. Here we don't want to modify then
	 * in most cases. We also don't want the modified timestamp to be wrongly raised.
	 *
	 * @return void
	 */
	public function testSaveWithAlreadySetModified() {
		CakeSession::write('Auth.User.id', '1');

		$data = array(
			'name' => 'Foo'
		);
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);
		$this->assertTrue(count($res['WhoDidItPlayer']) === 6);

		// update (id + name + modified)
		CakeSession::write('Auth.User.id', '2');
		$data += array('modified_by' => $res['WhoDidItPlayer']['modified_by']);
		$res = $this->Model->save($data + array('id' => $this->Model->id));

		$this->assertTrue((bool)$res);
		$this->assertFalse($res['WhoDidItPlayer']['modified']);
		$this->assertTrue(count($res['WhoDidItPlayer']) === 4);
	}

}
