<?php
App::uses('ComponentCollection', 'Controller');
App::uses('AuthComponent', 'Controller/Component');
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');

class PasswordableBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.tools.tools_user', 'plugin.tools.role',
	);

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::delete('Passwordable');
		Configure::write('Passwordable.auth', 'AuthTest');

		$this->User = ClassRegistry::init('Tools.ToolsUser');

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
			'password' => Security::hash('somepwd', null, true),
			'role_id' => '1'
		);
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);

		Router::setRequestInfo(new CakeRequest(null, false));
	}

	/**
	 * PasswordableBehaviorTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->User->Behaviors->load('Tools.Passwordable', array());
		$this->assertInstanceOf('PasswordableBehavior', $this->User->Behaviors->Passwordable);
		$result = $this->User->Behaviors->loaded('Passwordable');
		$this->assertTrue($result);
	}

	/**
	 * Make sure validation is triggered correctly
	 *
	 * @return void
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
	 * Test that confirm false does not require confirmation
	 *
	 * @return void
	 */
	public function testValidateNoConfirm() {
		$this->User->Behaviors->load('Tools.Passwordable', array('confirm' => false));
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
	 *
	 * @return void
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
	 * Validation and update process gets skipped if no values are entered
	 *
	 * @return void
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
		$this->assertEquals(array('name', 'id'), array_keys($is[$this->User->alias]));

		$id = $this->User->id;
		$data = array(
			'id' => $id,
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$this->assertEquals(array('id'), array_keys($is[$this->User->alias]));
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
			'pwd_current' => '123456',
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
		$this->assertFalse($is);
		$this->assertEquals(array('pwd', 'pwd_repeat', 'pwd_current'), array_keys($this->User->validationErrors));

		$this->tearDown();
		$this->setUp();

		$this->User->Behaviors->load('Tools.Passwordable', array('require' => false, 'current' => true));
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
	 * Test aliases for field names
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
	 * Assert that allowSame false does not allow storing the same password as previously entered
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
			'passw_current' => 'something',
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		);
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => '5',
			'passw_current' => 'somepwd',
			'passw' => 'newpwd',
			'passw_repeat' => 'newpwd'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}

	/**
	 * Assert that allowSame false does not allow storing the same password as previously entered
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
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$id = $is[$this->User->alias]['id'];

		$this->User->create();
		$data = array(
			'id' => $id,
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse((bool)$is);

		$this->User->create();
		$data = array(
			'id' => $id,
			'passw' => 'newpwd',
			'passw_repeat' => 'newpwd'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
	}

	/**
	 * Assert that on edit it does not wrongly pass validation (require => false)
	 */
	public function testRequireFalse() {
		$this->User->Behaviors->load('Tools.Passwordable', array(
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'require' => false
		));
		$this->User->create();
		$data = array(
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$id = $is[$this->User->alias]['id'];

		$this->User->create();
		$data = array(
			'id' => $id,
			'passw' => 'somepwd2',
			'passw_repeat' => ''
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse((bool)$is);
		//debug($this->User->validationErrors);

		$this->User->create();
		$data = array(
			'id' => $id,
			'passw' => 'somepwd2',
			'passw_repeat' => 'somepwd2'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
	}

	/**
	 * Needs faking of pwd check...
	 */
	public function testValidateCurrent() {
		$this->assertFalse($this->User->Behaviors->loaded('Passwordable'));
		$this->User->create();
		$data = array(
			'name' => 'xyz',
			'password' => Security::hash('somepwd', null, true));
		$result = $this->User->save($data);
		$this->assertTrue(!empty($result));
		$uid = (string)$this->User->id;

		$this->User->Behaviors->load('Tools.Passwordable', array('current' => true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
			//'pwd_current' => '',
		);
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->loaded('Passwordable'));
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'name' => 'Yeah',
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		// Test whitelist setting - only "password" needs to gets auto-added
		$is = $this->User->save(null, true, array('id', 'pwd', 'pwd_repeat', 'pwd_current'));
		$this->assertTrue(!empty($is));

		$user = $this->User->get($uid);
		// The password is updated, the name not
		$this->assertSame($is['ToolsUser']['password'], $user['ToolsUser']['password']);
		$this->assertSame('xyz', $user['ToolsUser']['name']);

		// Proof that we manually need to add pwd, pwd_repeat etc due to a bug in CakePHP<=2.4 allowing behaviors to only modify saving,
		// not validating of additional whitelist fields. Validation for those will be just skipped, no matter what the behavior tries
		// to set.
		$this->User->create();
		$data = array(
			'id' => $uid,
			'name' => 'Yeah',
			'pwd_current' => '123', // Obviously wrong
			'pwd' => 'some', // Too short
			'pwd_repeat' => 'somex' // Don't match
		);
		$this->User->set($data);
		// Test whitelist setting - only "password" gets auto-added, pwd, pwd_repeat etc need to be added manually
		// NOTE that I had to remove the code for adding those fields from the behavior (as it was not functional)
		// So of course, this won't work now as expected. But feel free to try to add them in the behavior. Results will be the same.
		$is = $this->User->save(null, true, array('id', 'name'));

		if ((float)Configure::version() >= 2.5) {
			// Validation errors triggered - as expected
			$this->assertFalse($is);
			$this->assertSame(array('pwd', 'pwd_repeat', 'pwd_current'), array_keys($this->User->validationErrors));
			return;
		}

		// Save is successful
		$this->assertTrue(!empty($is));
		$user = $this->User->get($uid);

		$this->assertSame('Yeah', $user['ToolsUser']['name']);

		// The password is not updated, the name is
		$this->assertSame($is['ToolsUser']['password'], $user['ToolsUser']['password']);
		$this->assertSame('Yeah', $user['ToolsUser']['name']);
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
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd'
		);
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);
		$uid = (string)$this->User->id;

		$this->User->Behaviors->load('Tools.Passwordable', array('current' => true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
			//'pwd_current' => '',
		);
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->loaded('Passwordable'));
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwd',
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
		$this->User->Behaviors->load('Tools.Passwordable', array(
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
		));
		$this->User->create();
		$data = array(
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd'
		);
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);
		$uid = (string)$this->User->id;

		// Without the current password it will not continue
		$this->User->Behaviors->load('Tools.Passwordable', array('current' => true));
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
		);
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->loaded('Passwordable'));
		$result = $this->User->save();
		$this->assertFalse($result);

		// Without the correct current password it will not continue
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertFalse($result);

		// Now it will
		$this->User->create();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);
	}

	/**
	 * PasswordableBehaviorTest::testSettings()
	 *
	 * @return void
	 */
	public function testSettings() {
		// Pwd min and max length
		$this->User->Behaviors->load('Tools.Passwordable', array(
			'allowSame' => false,
			'current' => false,
			'minLength' => 3,
			'maxLength' => 6,
		));
		$this->User->create();
		$data = array(
			'pwd' => '123',
			'pwd_repeat' => '123'
		);
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);
		$uid = (string)$this->User->id;

		$this->User->create();
		$data = array(
			'pwd' => '12345678',
			'pwd_repeat' => '12345678'
		);
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertFalse($result);
		$expected = array(
			'pwd' => array(__('valErrBetweenCharacters %s %s', 3, 6)),
			'pwd_repeat' => array(__('valErrBetweenCharacters %s %s', 3, 6))
		);
		$this->assertEquals($expected, $this->User->validationErrors);
	}

}

/**
 * Test component
 */
class AuthTestComponent extends AuthComponent {
}

if (!class_exists('SimplePasswordHasher')) {
	class SimplePasswordHasher {
	}
}

class ComplexPasswordHasher extends SimplePasswordHasher {

}