<?php
App::uses('ComponentCollection', 'Controller');

class PasswordableBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'core.user',
	);

	/**
	 * setUp method
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('Passwordable.auth', 'AuthTestComponent');

		$this->User = ClassRegistry::init('User');
		if (isset($this->User->validate['pwd'])) {
			unset($this->User->validate['pwd']);
		}
		if (isset($this->User->validate['pwd_repeat'])) {
			unset($this->User->validate['pwd_repeat']);
		}
		if (isset($this->User->validate['pwd_current'])) {
			unset($this->User->validate['pwd_current']);
		}
		if (isset($this->User->order)) {
			unset($this->User->order);
		}
	}

	/**
	 * Tear-down method. Resets environment state.
	 */
	public function tearDown() {
		unset($this->User);
		parent::tearDown();

		ClassRegistry::flush();
	}


	public function testObject() {
		$this->User->Behaviors->load('Tools.Passwordable', array());
		$this->assertInstanceOf('PasswordableBehavior', $this->User->Behaviors->Passwordable);
		$res = $this->User->Behaviors->attached('Passwordable');
		$this->assertTrue($res);
	}

	/**
	 * make sure validation is triggered correctly
	 */
	public function testValidate() {
		$this->User->Behaviors->load('Tools.Passwordable', array());

		$this->User->create();
		$data = array(
			'pwd' => '123456',
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors); ob_flush();
		$this->assertFalse($is);
		$this->assertEquals(array('pwd_repeat'), array_keys($this->User->validationErrors));


		$this->User->create();
		$data = array(
			'pwd' => '1234ab',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors); ob_flush();
		$this->assertFalse($is);
		$this->assertEquals(array(__('valErrPwdNotMatch')), $this->User->validationErrors['pwd_repeat']);

		$this->User->create();
		$data = array(
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		//debug($this->User->validate);
		$is = $this->User->validates();
		$this->assertTrue(!empty($is));

	}

	/**
	 * test that confirm false does not require confirmation
	 */
	public function testValidateNoConfirm() {
		$this->User->Behaviors->load('Tools.Passwordable', array('confirm'=>false));
		$this->User->create();
		$data = array(
			'pwd' => '123456',
		);
		$this->User->set($data);
		$is = $this->User->save();
		debug($is); ob_flush();
		$this->assertTrue(!empty($is));
	}

	/**
	 * validation and update process gets skipped if no values are entered
	 */
	public function testValidateEmpty() {
		$this->User->Behaviors->load('Tools.Passwordable');
		$this->User->create();
		$data = array(
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->User->set($data);
		$is = $this->User->save();
		debug($this->User->validationErrors); ob_flush();
		$this->assertFalse($is);
		$this->assertEquals(array('pwd', 'pwd_repeat'), array_keys($this->User->validationErrors));


		$this->User->Behaviors->unload('Passwordable');
		$this->User->validate = array();

		$this->User->Behaviors->load('Tools.Passwordable', array('current'=>true));
		$this->User->create();
		$data = array(
			'id' => 123,
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '123',
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors); ob_flush();
		$this->assertFalse($is);
		$this->assertEquals(array('pwd', 'pwd_repeat', 'pwd_current'), array_keys($this->User->validationErrors));

		$this->tearDown();
		$this->setUp();

		$this->User->Behaviors->load('Tools.Passwordable', array('allowEmpty'=>true, 'current'=>true));
		$this->User->create();
		$data = array(
			'user' => 'foo',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '',
		);
		$is = $this->User->save($data);
		$this->assertTrue(!empty($is));
	}

	/**
	 * test aliases for field names
	 */
	public function testDifferentFieldNames() {
		$this->User->Behaviors->load('Tools.Passwordable', array(
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'formFieldCurrent' => 'passw_current',
		));
		$this->User->create();
		$data = array(
			'passw' => '123456',
			'passw_repeat' => '123456'
		);
		$this->User->set($data);
		//debug($this->User->data);
		$is = $this->User->save();
		$this->assertTrue(!empty($is));

	}

	/**
	 * assert that allowSame false does not allow storing the same password as previously entered
	 */
	public function testNotSame() {
		$this->User->Behaviors->load('Tools.Passwordable', array(
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'formFieldCurrent' => 'passw_current',
			'allowSame' => false,
			'current' => true
		));
		$this->User->create();
		$data = array(
			'id' => 5,
			'passw_current' => 'some',
			'passw' => 'some',
			'passw_repeat' => 'some'
		);
		$this->User->set($data);
		$is = $this->User->save();
		debug($this->User->validationErrors);
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => 5,
			'passw_current' => 'some',
			'passw' => 'new',
			'passw_repeat' => 'new'
		);
		$this->User->set($data);
		debug($this->User->data);
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}

	/**
	 * assert that allowSame false does not allow storing the same password as previously entered
	 */
	public function testNotSameWithoutCurrentField() {
		$this->User->Behaviors->load('Tools.Passwordable', array(
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'allowSame' => false,
			'current' => false
		));
		$this->User->create();
		$data = array(
			'passw' => 'some',
			'passw_repeat' => 'some'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$id = $is['User']['id'];

		$this->User->create();
		$data = array(
			'id' => $id,
			'passw' => 'some',
			'passw_repeat' => 'some'
		);
		$this->User->set($data);
		$is = $this->User->save();
		debug($this->User->validationErrors); ob_flush();
		$this->assertFalse((bool)$is);

		$this->User->create();
		$data = array(
			'id' => $id,
			'passw' => 'new',
			'passw_repeat' => 'new'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
	}

	/**
	 * needs faking of pwd check...
	 */
	public function testValidateCurrent() {
		$this->assertFalse($this->User->Behaviors->attached('Passwordable'));
		$this->User->create();
		$data = array('user'=>'xyz', 'password'=>Security::hash('some', null, true));
		$res = $this->User->save($data);
		$this->assertTrue(!empty($res));
		$uid = $this->User->id;

		# cake bug => attached behavior validation rules cannot be triggered
		//$this->tearDown();
		//$this->setUp();

		$this->User->Behaviors->load('Tools.Passwordable', array('current'=>true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '1234',
			'pwd_repeat' => '123456',
			//'pwd_current' => '',
		);
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->attached('Passwordable'));
		//debug($this->User->validate); ob_flush();
		$is = $this->User->save();
		debug($this->User->validationErrors); ob_flush();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somex',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		//debug($this->User->validationErrors); ob_flush();
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'some',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		//debug($this->User->validationErrors); ob_flush();
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}

}


/**
 * FAKER!
 * 2011-11-03 ms
 */
class AuthTestComponent {

	public function identify($request, $response) {
		$user = $request->data['User'];
		if ($user['id'] == '5' && $user['password'] == 'some') {
			return true;
		}
		return false;
	}

}


