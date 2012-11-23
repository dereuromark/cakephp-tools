<?php

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');

App::uses('ComponentCollection', 'Controller');

class ChangePasswordBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'core.user',
	);

	/**
	 * setUp method
	 */
	public function setUp() {
		parent::setUp();
		
		$this->User = ClassRegistry::init('User');
	}

	/**
	 * Tear-down method.  Resets environment state.
	 */
	public function tearDown() {
		unset($this->User);
		parent::tearDown();
		
		ClassRegistry::flush();
	}


	public function testObject() {
		$this->User->Behaviors->attach('Tools.ChangePassword', array());
		$this->assertInstanceOf('ChangePasswordBehavior', $this->User->Behaviors->ChangePassword);
		$res = $this->User->Behaviors->attached('ChangePassword');
		$this->assertTrue($res);
	}

	/**
	 * make sure validation is triggered correctly
	 */
	public function testValidate() {
		$this->User->Behaviors->attach('Tools.ChangePassword', array());

		$this->User->create();
		$data = array(
			'pwd' => '1234',
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors); ob_flush();
		$this->assertFalse($is);
		$this->assertEquals(array('pwd_repeat'), array_keys($this->User->validationErrors));


		$this->User->create();
		$data = array(
			'pwd' => '1234',
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
		$this->User->Behaviors->attach('Tools.ChangePassword', array('confirm'=>false));
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
		$this->User->Behaviors->attach('Tools.ChangePassword');
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


		$this->User->Behaviors->detach('ChangePassword');
		$this->User->validate = array();
		
		$this->User->Behaviors->attach('Tools.ChangePassword', array('current'=>true));
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
		
		$this->User->Behaviors->attach('Tools.ChangePassword', array('allowEmpty'=>true, 'current'=>true));
		$this->User->create();
		$data = array(
			'user' => 'foo',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '',
		);
		$is = $this->User->save($data);
		
		debug($this->User->data);
		debug($this->User->validate);
		debug($this->User->validationErrors); ob_flush();
		
		$this->assertTrue(!empty($is));
	}

	/**
	 * test aliases for field names
	 */
	public function testDifferentFieldNames() {
		$this->User->Behaviors->attach('Tools.ChangePassword', array(
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
		$this->User->Behaviors->attach('Tools.ChangePassword', array(
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
	 * needs faking of pwd check...
	 */
	public function testValidateCurrent() {
		$this->assertFalse($this->User->Behaviors->attached('ChangePassword'));
		$this->User->create();
		$data = array('user'=>'xyz', 'password'=>Security::hash('some', null, true));
		$res = $this->User->save($data);
		$this->assertTrue(!empty($res));
		$uid = $this->User->id;
		
		# cake bug => attached behavior validation rules cannot be triggered
		//$this->tearDown();
		//$this->setUp();

		$this->User->Behaviors->attach('Tools.ChangePassword', array('current'=>true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '1234',
			'pwd_repeat' => '123456',
			//'pwd_current' => '',
		);
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->attached('ChangePassword'));
		debug($this->User->validate); ob_flush();
		$is = $this->User->save();
		//debug($this->User->validationErrors); ob_flush();
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
class AuthComponent {

	public function identify($request, $response) {
		$user = $request->data['User'];
		if ($user['id'] == '5' && $user['password'] == 'some') {
			return true;
		}
		return false;
	}

}


