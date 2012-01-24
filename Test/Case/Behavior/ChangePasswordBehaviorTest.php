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
		$this->User = ClassRegistry::init('User');
	}

	/**
	 * Tear-down method.  Resets environment state.
	 */
	public function tearDown() {
		$this->User->Behaviors->detach('ChangePassword');
		unset($this->User);
	}


	public function testObject() {
		$this->User->Behaviors->attach('Tools.ChangePassword', array());
		$this->assertIsA($this->User->Behaviors->ChangePassword, 'ChangePasswordBehavior');
		$res = $this->User->Behaviors->attached('ChangePassword');
		$this->assertTrue($res);
	}
	
	public function testValidate() {
		$this->User->Behaviors->attach('Tools.ChangePassword', array());
		
		
		$this->User->create();
		$data = array(
			'pwd' => '1234',
		);
		$this->User->set($data);
		//debug($this->User->data);
		$is = $this->User->save();
		debug($this->User->invalidFields());	
		//debug($this->User->validate);
		$this->assertFalse($is);
		
		
		$this->User->create();
		$data = array(
			'pwd' => '1234',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		//debug($this->User->data);
		$is = $this->User->save();
		debug($this->User->invalidFields());	
		//debug($this->User->validate);
		$this->assertFalse($is);
		

		
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
	 * needs faking of pwd check...
	 */
	public function testValidateCurrent() {
		$this->User->create();
		$data = array('username'=>'xyz', 'password'=>Security::hash('some', null, true));
		$res = $this->User->save($data);
		$uid = $this->User->id;
		debug($res);
		
		//App::import('Component', 'Tools.AuthExt');
		
		$this->User->Behaviors->attach('Tools.ChangePassword', array('current'=>true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '1234',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		//debug($this->User->data);
		$is = $this->User->save();
		debug($this->User->invalidFields());	
		//debug($this->User->validate);
		$this->assertFalse($is);
		
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somex',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		debug($this->User->invalidFields());	
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
		debug($this->User->invalidFields());	
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}
	
	public function testValidateNoConfirm() {
		$this->User->Behaviors->attach('Tools.ChangePassword', array('confirm'=>false));
		$this->User->create();
		$data = array(
			'pwd' => '123456',
		);
		$this->User->set($data);
		$is = $this->User->save();
		debug($is);
		$this->assertTrue(!empty($is));
	}
	
	public function testValidateNonEmptyToEmpty() {
		$this->User->Behaviors->attach('Tools.ChangePassword', array('nonEmptyToEmpty'=>false));
		$this->User->create();
		$data = array(
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->User->set($data);
		$is = $this->User->save();
		debug($this->User->invalidFields());
		debug($is);
		$this->assertFalse($is);	
		
		//TODO:
		$this->User->Behaviors->detach('ChangePassword');
		
		$this->User->Behaviors->attach('Tools.ChangePassword', array('nonEmptyToEmpty'=>true));
		$this->User->create();
		$data = array(
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->User->set($data);
		//debug($this->User->data);
		$is = $this->User->save();
		debug($this->User->invalidFields());	
		$this->assertFalse($is);	
	}
	
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
		debug($this->User->invalidFields());	
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


