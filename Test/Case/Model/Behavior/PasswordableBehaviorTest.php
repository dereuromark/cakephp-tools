<?php
App::uses('ComponentCollection', 'Controller');
App::uses('AuthComponent', 'Controller/Component');
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');

class PasswordableBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.tools.tools_user', 'plugin.tools.role',
	);

	/**
	 * setUp method
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('Passwordable.auth', 'AuthTest');

		$this->User = ClassRegistry::init('ToolsUser');

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

		$this->User->create();
		$data = array(
			'id' => '5',
			'name' => 'admin',
			'password' => Security::hash('some', null, true),
			'role_id' => '1'
		);
		$this->User->set($data);
		$res = $this->User->save();
		$this->assertTrue((bool)$res);

		Router::setRequestInfo(new CakeRequest(null, false));
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
		//debug($this->User->validationErrors);
		$this->assertFalse($is);
		$this->assertEquals(array('pwd_repeat'), array_keys($this->User->validationErrors));


		$this->User->create();
		$data = array(
			'pwd' => '1234ab',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
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
		//debug($is);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Trigger validation and update process if no values are entered but are required
	 */
	public function testValidateRequired() {
		$this->User->Behaviors->load('Tools.Passwordable');
		$this->User->create();
		$data = array(
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse($is);
		$this->assertEquals(array('pwd', 'pwd_repeat'), array_keys($this->User->validationErrors));
	}

	/**
	 * validation and update process gets skipped if no values are entered
	 */
	public function testValidateNotRequired() {
		$this->User->Behaviors->load('Tools.Passwordable', array('require' => false));
		$this->User->create();
		$data = array(
			'name' => 'foo', // we need at least one field besides the password on CREATE
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$this->assertEquals(array('name', 'id'), array_keys($is['ToolsUser']));

		$id = $this->User->id;
		$data = array(
			'id' => $id,
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$this->assertEquals(array('id'), array_keys($is['ToolsUser']));
	}

	/**
	 * PasswordableBehaviorTest::testValidateEmptyWithCurrentPassword()
	 *
	 * @return void
	 */
	public function testValidateEmptyWithCurrentPassword() {
		$this->User->Behaviors->load('Tools.Passwordable', array('current' => true));
		$this->User->create();
		$data = array(
			'id' => '123',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '123',
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
		$this->assertFalse($is);
		$this->assertEquals(array('pwd', 'pwd_repeat', 'pwd_current'), array_keys($this->User->validationErrors));

		$this->tearDown();
		$this->setUp();

		$this->User->Behaviors->load('Tools.Passwordable', array('require' => false, 'current'=>true));
		$this->User->create();
		$data = array(
			'name' => 'foo',
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
			'current' => true,
			//'userModel' => 'ToolsUser'
		));
		$this->User->create();
		$data = array(
			'id' => '5',
			'passw_current' => 'some',
			'passw' => 'some',
			'passw_repeat' => 'some'
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => '5',
			'passw_current' => 'some',
			'passw' => 'new',
			'passw_repeat' => 'new'
		);
		$this->User->set($data);
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
		$id = $is[$this->User->alias]['id'];

		$this->User->create();
		$data = array(
			'id' => $id,
			'passw' => 'some',
			'passw_repeat' => 'some'
		);
		$this->User->set($data);
		$is = $this->User->save();
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
		$data = array(
			'name' => 'xyz',
			'password' => Security::hash('some', null, true));
		$res = $this->User->save($data);
		$this->assertTrue(!empty($res));
		$uid = (String)$this->User->id;

		$this->User->Behaviors->load('Tools.Passwordable', array('current' => true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '1234',
			'pwd_repeat' => '123456',
			//'pwd_current' => '',
		);
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->attached('Passwordable'));
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somex',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
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
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}

	/**
	 * Test cake2.4 passwordHasher feature
	 *
	 * @return void
	 */
	public function testPasswordHasher() {
		$this->skipIf((float)Configure::version() < 2.4, 'Needs 2.4 and above');

		$this->User->Behaviors->load('Tools.Passwordable', array(
			'formField' => 'pwd',
			'formFieldRepeat' => 'pwd_repeat',
			'allowSame' => false,
			'current' => false,
			'passwordHasher' => 'Complex',
		));
		$this->User->create();
		$data = array(
			'pwd' => 'some',
			'pwd_repeat' => 'some'
		);
		$this->User->set($data);
		$res = $this->User->save();
		$this->assertTrue((bool)$res);
		$uid = (String)$this->User->id;

		$this->User->Behaviors->load('Tools.Passwordable', array('current' => true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '1234',
			'pwd_repeat' => '123456',
			//'pwd_current' => '',
		);
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->attached('Passwordable'));
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somex',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
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
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}

	/**
	 * PasswordableBehaviorTest::testBlowfish()
	 *
	 * @return void
	 */
	public function testBlowfish() {
		//Configure::write('Security.salt', 'Cf1f11ePArKlBJomM0F6aJ');
		/*
		$this->assertFalse($this->User->Behaviors->attached('Passwordable'));
		$this->User->create();
		$data = array(
			'name' => 'xyz',
			'password' => Security::hash('some', 'blowfish'));
		$res = $this->User->save($data);
		$this->assertTrue(!empty($res));
		$uid = (String)$this->User->id;
		*/

		$this->User->Behaviors->load('Tools.Passwordable', array(
			'formField' => 'pwd',
			'formFieldRepeat' => 'pwd_repeat',
			'allowSame' => false,
			'current' => false,
			//'userModel' => 'ToolsUser',
			'authType' => 'Blowfish',
		));
		$this->User->create();
		$data = array(
			'pwd' => 'some',
			'pwd_repeat' => 'some'
		);
		$this->User->set($data);
		$res = $this->User->save();
		$this->assertTrue((bool)$res);
		$uid = (String)$this->User->id;

		$this->User->Behaviors->load('Tools.Passwordable', array('current' => true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '1234',
			'pwd_repeat' => '123456',
			//'pwd_current' => '',
		);
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->attached('Passwordable'));
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somex',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
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
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}

}

/**
 * 2011-11-03 ms
 */
class AuthTestComponent extends AuthComponent {
}

if (!class_exists('SimplePasswordHasher')) {
	class SimplePasswordHasher {
	}
}
class ComplexPasswordHasher extends SimplePasswordHasher {

}