<?php

App::uses('MasterPasswordBehavior', 'Tools.Model/Behavior');
App::uses('ModelBehavior', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

Configure::write('MasterPassword.password', '7c4a8d09ca3762af61e59520943dc26494f8941b');

class MasterPasswordBehaviorTest extends MyCakeTestCase {

	public $MasterPasswordBehavior;

	public function setUp() {
		parent::setUp();

		$this->MasterPasswordBehavior = new MasterPasswordBehavior();
		$this->Model = ClassRegistry::init('MasterPasswordTestModel');
	}

	public function testObject() {
		$this->assertTrue(is_object($this->MasterPasswordBehavior));
		$this->assertInstanceOf('MasterPasswordBehavior', $this->MasterPasswordBehavior);
	}

	/**
	 * Test 123456
	 */
	public function testSinglePwd() {
		$this->Model->Behaviors->load('Tools.MasterPassword');

		$data = array(
			'some_comment' => 'xyz',
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertFalse($res);

		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => ''
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertFalse($res);

		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => '123'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertFalse($res);

		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => '123456'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);
	}

	/**
	 * Test xxzzyy
	 * with specific settings
	 */
	public function testComplex() {
		Configure::write('MasterPassword.password', '373e28e7cdb42d7aefc49c5f34fa589a7ff1eefd0ac01f573d90299f79a01a05');
		$this->Model->Behaviors->load('Tools.MasterPassword', array('field' => 'master_password', 'hash' => 'sha256', 'message' => 'No way'));

		$data = array(
			'some_comment' => 'xyz',
			'master_password' => '123'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertFalse($res);
		$this->assertEquals(__('No way'), $this->Model->validationErrors['master_password'][0]);

		$data = array(
			'some_comment' => 'xyz',
			'master_password' => 'xxyyzz'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);
		$this->assertEmpty($this->Model->invalidFields());
	}

	/**
	 * Test xxzzyy with salt
	 * with specific settings
	 */
	public function testWithSalt() {
		$hash = hash('sha256', Configure::read('Security.salt') . 'xxyyzz');
		Configure::write('MasterPassword.password', $hash);
		$this->Model->Behaviors->load('Tools.MasterPassword', array('hash' => 'sha256', 'salt' => true));
		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => 'xxyyzz'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);

		$hash = hash('sha256', '123' . 'xxyyzz');
		Configure::write('MasterPassword.password', $hash);
		$this->Model->Behaviors->load('Tools.MasterPassword', array('hash' => 'sha256', 'salt' => '123'));
		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => 'xxyyzz'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);
	}

	/**
	 * Test 123456 and 654321
	 */
	public function testMultiplePwd() {
		Configure::write('MasterPassword.password', array('dd5fef9c1c1da1394d6d34b248c51be2ad740840', '7c4a8d09ca3762af61e59520943dc26494f8941b'));
		$this->Model->Behaviors->load('Tools.MasterPassword');

		$data = array(
			'some_comment' => 'xyz',
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertFalse($res);

		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => ''
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertFalse($res);

		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => '123'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertFalse($res);

		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => '123456'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);

		$data = array(
			'some_comment' => 'xyz',
			'master_pwd' => '654321'
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);
	}

}

class MasterPasswordTestModel extends CakeTestModel {

	public $useTable = false;

}
