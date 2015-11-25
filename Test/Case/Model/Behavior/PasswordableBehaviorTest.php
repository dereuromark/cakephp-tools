<?php
App::uses('ComponentCollection', 'Controller');
App::uses('AuthComponent', 'Controller/Component');
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');

if (!function_exists('password_hash')) {
	require_once CakePlugin::path('Shim') . 'Lib/Bootstrap/Password.php';
}

class PasswordableBehaviorTest extends CakeTestCase {

	public $fixtures = [
		'plugin.tools.tools_user', 'plugin.tools.role',
	];

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('Config.language', 'deu');

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
		$data = [
			'id' => '5',
			'name' => 'admin',
			'password' => Security::hash('somepwd', null, true),
			'role_id' => '1'
		];
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
		$this->User->Behaviors->load('Tools.Passwordable', []);
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
		$this->User->Behaviors->load('Tools.Passwordable', []);

		$this->User->create();
		$data = [
			'pwd' => '123456',
		];
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
		$this->assertFalse($is);
		$this->assertEquals(['pwd_repeat'], array_keys($this->User->validationErrors));

		$this->User->create();
		$data = [
			'pwd' => '1234ab',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
		$this->assertFalse($is);
		$this->assertEquals([__d('tools', 'valErrPwdNotMatch')], $this->User->validationErrors['pwd_repeat']);

		$this->User->create();
		$data = [
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
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
		$this->User->Behaviors->load('Tools.Passwordable', ['confirm' => false]);
		$this->User->create();
		$data = [
			'pwd' => '123456',
		];
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
		$data = [
			'pwd' => '',
			'pwd_repeat' => ''
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse($is);
		$this->assertEquals(['pwd', 'pwd_repeat'], array_keys($this->User->validationErrors));
	}

	/**
	 * Validation and update process gets skipped if no values are entered
	 *
	 * @return void
	 */
	public function testValidateNotRequired() {
		$this->User->Behaviors->load('Tools.Passwordable', ['require' => false]);
		$this->User->create();
		$data = [
			'name' => 'foo', // we need at least one field besides the password on CREATE
			'pwd' => '',
			'pwd_repeat' => ''
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$this->assertEquals(['name', 'id'], array_keys($is[$this->User->alias]));

		$id = $this->User->id;
		$data = [
			'id' => $id,
			'pwd' => '',
			'pwd_repeat' => ''
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$this->assertEquals(['id'], array_keys($is[$this->User->alias]));
	}

	/**
	 * PasswordableBehaviorTest::testValidateEmptyWithCurrentPassword()
	 *
	 * @return void
	 */
	public function testValidateEmptyWithCurrentPassword() {
		$this->User->Behaviors->load('Tools.Passwordable', ['current' => true]);
		$this->User->create();
		$data = [
			'id' => '123',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '123456',
		];
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
		$this->assertFalse($is);
		$this->assertEquals(['pwd', 'pwd_repeat', 'pwd_current'], array_keys($this->User->validationErrors));

		$this->tearDown();
		$this->setUp();

		$this->User->Behaviors->load('Tools.Passwordable', ['require' => false, 'current' => true]);
		$this->User->create();
		$data = [
			'name' => 'foo',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '',
		];
		$is = $this->User->save($data);
		$this->assertTrue(!empty($is));
	}

	/**
	 * PasswordableBehaviorTest::testValidateCustomRule()
	 *
	 * @return void
	 */
	public function testValidateCustomRule() {
		$rules = [
			'validateCustom' => [
				'rule' => ['custom', '#^[a-z0-9]+$#'], // Just a test example, never use this regexp!
				'message' => 'Foo Bar',
				'last' => true,
			],
			'validateCustomExt' => [
				'rule' => ['custom', '#^[a-z]+$#'], // Just a test example, never use this regexp!
				'message' => 'Foo Bar Ext',
				'last' => true,
			]
		];
		$this->User->Behaviors->load('Tools.Passwordable', [
			'customValidation' => $rules]);

		$this->User->create();
		$data = [
			'pwd' => '%123456',
			'pwd_repeat' => '%123456'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse($is);

		$result = $this->User->validationErrors;
		$expected = ['pwd' => ['Foo Bar']];
		$this->assertSame($expected, $result);

		$this->User->create();
		$data = [
			'pwd' => 'abc123',
			'pwd_repeat' => 'abc123'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse($is);

		$result = $this->User->validationErrors;
		$expected = ['pwd' => ['Foo Bar Ext']];
		$this->assertSame($expected, $result);

		$this->User->create();
		$data = [
			'pwd' => 'abcdef',
			'pwd_repeat' => 'abcdef'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
	}

	/**
	 * Test aliases for field names
	 */
	public function testDifferentFieldNames() {
		$this->User->Behaviors->load('Tools.Passwordable', [
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'formFieldCurrent' => 'passw_current',
		]);
		$this->User->create();
		$data = [
			'passw' => '123456',
			'passw_repeat' => '123456'
		];
		$this->User->set($data);
		//debug($this->User->data);
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}

	/**
	 * Assert that allowSame false does not allow storing the same password as previously entered
	 */
	public function testNotSame() {
		$this->User->Behaviors->load('Tools.Passwordable', [
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'formFieldCurrent' => 'passw_current',
			'allowSame' => false,
			'current' => true,
		]);
		$this->User->create();
		$data = [
			'id' => '5',
			'passw_current' => 'something',
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		];
		$this->User->set($data);
		$is = $this->User->save();
		//debug($this->User->validationErrors);
		$this->assertFalse($is);

		$this->User->create();
		$data = [
			'id' => '5',
			'passw_current' => 'somepwd',
			'passw' => 'newpwd',
			'passw_repeat' => 'newpwd'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue(!empty($is));
	}

	/**
	 * Assert that allowSame false does not allow storing the same password as previously entered
	 */
	public function testNotSameWithoutCurrentField() {
		$this->User->Behaviors->load('Tools.Passwordable', [
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'allowSame' => false,
			'current' => false
		]);
		$this->User->create();
		$data = [
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$id = $is[$this->User->alias]['id'];

		$this->User->create();
		$data = [
			'id' => $id,
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse((bool)$is);

		$this->User->create();
		$data = [
			'id' => $id,
			'passw' => 'newpwd',
			'passw_repeat' => 'newpwd'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
	}

	/**
	 * Assert that on edit it does not wrongly pass validation (require => false)
	 */
	public function testRequireFalse() {
		$this->User->Behaviors->load('Tools.Passwordable', [
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'require' => false
		]);
		$this->User->create();
		$data = [
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertTrue((bool)$is);
		$id = $is[$this->User->alias]['id'];

		$this->User->create();
		$data = [
			'id' => $id,
			'passw' => 'somepwd2',
			'passw_repeat' => ''
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse((bool)$is);
		//debug($this->User->validationErrors);

		$this->User->create();
		$data = [
			'id' => $id,
			'passw' => 'somepwd2',
			'passw_repeat' => 'somepwd2'
		];
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
		$data = [
			'name' => 'xyz',
			'password' => Security::hash('somepwd', null, true)];
		$result = $this->User->save($data);
		$this->assertTrue(!empty($result));
		$uid = (string)$this->User->id;

		$this->User->Behaviors->load('Tools.Passwordable', ['current' => true]);
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
			//'pwd_current' => '',
		];
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->loaded('Passwordable'));
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = [
			'id' => $uid,
			'name' => 'Yeah',
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		// Test whitelist setting - only "password" needs to gets auto-added
		$options = ['validate' => true, 'fieldList' => ['id', 'pwd', 'pwd_repeat', 'pwd_current']];
		$is = $this->User->save(null, $options);
		$this->assertTrue(!empty($is));

		$user = $this->User->findById($uid);
		// The password is updated, the name not
		$this->assertSame($is['ToolsUser']['password'], $user['ToolsUser']['password']);
		$this->assertSame('xyz', $user['ToolsUser']['name']);

		// Proof that we manually need to add pwd, pwd_repeat etc due to a bug in CakePHP<=2.4 allowing behaviors to only modify saving,
		// not validating of additional whitelist fields. Validation for those will be just skipped, no matter what the behavior tries
		// to set.
		$this->User->create();
		$data = [
			'id' => $uid,
			'name' => 'Yeah',
			'pwd_current' => '123', // Obviously wrong
			'pwd' => 'some', // Too short
			'pwd_repeat' => 'somex' // Don't match
		];
		$this->User->set($data);
		// Test whitelist setting - only "password" gets auto-added, pwd, pwd_repeat etc need to be added manually
		// NOTE that I had to remove the code for adding those fields from the behavior (as it was not functional)
		// So of course, this won't work now as expected. But feel free to try to add them in the behavior. Results will be the same.
		$options = ['validate' => true, 'fieldList' => ['id', 'name']];
		$is = $this->User->save(null, $options);

		if ((float)Configure::version() >= 2.5) {
			// Validation errors triggered - as expected
			$this->assertFalse($is);
			$this->assertSame(['pwd', 'pwd_repeat', 'pwd_current'], array_keys($this->User->validationErrors));
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

		$this->User->Behaviors->load('Tools.Passwordable', [
			'formField' => 'pwd',
			'formFieldRepeat' => 'pwd_repeat',
			'allowSame' => false,
			'current' => false,
			'passwordHasher' => 'Complex',
		]);
		$this->User->create();
		$data = [
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);
		$uid = (string)$this->User->id;

		$this->User->Behaviors->load('Tools.Passwordable', ['current' => true]);
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
			//'pwd_current' => '',
		];
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->loaded('Passwordable'));
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$is = $this->User->save();
		$this->assertFalse($is);

		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
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
		$this->User->Behaviors->load('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
		]);

		$this->User->create();
		$data = [
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);
		$uid = (string)$this->User->id;

		$this->User->Behaviors->load('Tools.Passwordable', ['current' => true]);

		// Without the current password it will not continue
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
		];
		$this->User->set($data);
		$this->assertTrue($this->User->Behaviors->loaded('Passwordable'));
		$result = $this->User->save();
		$this->assertFalse($result);

		// Without the correct current password it will not continue
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertFalse($result);

		// Now it will
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);
	}

	/**
	 * Tests that passwords prior to PHP5.5 and/or password_hash() are still working
	 * if Tools.Modern is being used.
	 *
	 * @return void
	 */
	public function testBlowfishWithBC() {
		$this->skipIf(!function_exists('password_hash'), 'password_hash() is not available.');

		$oldHash = Security::hash('foobar', 'blowfish', false);
		$newHash = password_hash('foobar', PASSWORD_BCRYPT);

		$this->User->Behaviors->load('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
			'passwordHasher' => 'Shim.Modern'
		]);
		$this->User->create();
		$data = [
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);
		$uid = (string)$this->User->id;

		// Same pwd is not allowed
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertFalse($result);

		$this->User->Behaviors->load('Tools.Passwordable', ['current' => true]);

		// Without the correct current password it will not continue
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'somepwdxyz',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertFalse($result);

		// Now it will
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);

		// Lets set a BC password (without password_hash() method but Security class)
		$data = [
			'id' => $uid,
			'password' => $oldHash,
		];
		$result = $this->User->save($data, ['validate' => false]);
		$this->assertTrue((bool)$result);

		// Now it will still work
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'foobar',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);

		// Lets set an invalid BC password (without password_hash() method but Security class)
		$data = [
			'id' => $uid,
			'password' => $oldHash . 'x',
		];
		$result = $this->User->save($data, ['validate' => false]);
		$this->assertTrue((bool)$result);

		// Now it will still work
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'foobar',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertFalse($result);

		// Lets set a valid BC password (without password_hash() method but Security class)
		// But the provided pwd is incorrect
		$data = [
			'id' => $uid,
			'password' => $oldHash,
		];
		$result = $this->User->save($data, ['validate' => false]);
		$this->assertTrue((bool)$result);

		// Now it will still work
		$this->User->create();
		$data = [
			'id' => $uid,
			'pwd_current' => 'foobarx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertFalse($result);
	}

	/**
	 * Tests needsPasswordRehash()
	 *
	 * @return void
	 */
	public function testNeedsPasswordRehash() {
		$this->skipIf(!function_exists('password_hash'), 'password_hash() is not available.');

		$this->User->Behaviors->load('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
			'passwordHasher' => 'Shim.Modern'
		]);

		$hash =  password_hash('foobar', PASSWORD_BCRYPT);
		$result = $this->User->needsPasswordRehash($hash);
		$this->assertFalse($result);

		$hash =  sha1('foobar');
		$result = $this->User->needsPasswordRehash($hash);
		$this->assertTrue($result);
	}

	/**
	 * Tests needsPasswordRehash()
	 *
	 * @return void
	 */
	public function testNeedsPasswordRehashWithNotSupportedHasher() {
		$this->User->Behaviors->load('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
		]);

		$hash =  password_hash('foobar', PASSWORD_BCRYPT);
		$result = $this->User->needsPasswordRehash($hash);
		$this->assertFalse($result);

		$this->User->Behaviors->load('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
			'passwordHasher' => 'Simple'
		]);

		$hash =  password_hash('foobar', PASSWORD_BCRYPT);
		$result = $this->User->needsPasswordRehash($hash);
		$this->assertFalse($result);
	}

	/**
	 * PasswordableBehaviorTest::testSettings()
	 *
	 * @return void
	 */
	public function testSettings() {
		// Pwd min and max length
		$this->User->Behaviors->load('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'minLength' => 3,
			'maxLength' => 6,
		]);
		$this->User->create();
		$data = [
			'pwd' => '123',
			'pwd_repeat' => '123'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertTrue((bool)$result);

		$this->User->create();
		$data = [
			'pwd' => '12345678',
			'pwd_repeat' => '12345678'
		];
		$this->User->set($data);
		$result = $this->User->save();
		$this->assertFalse($result);
		$expected = [
			'pwd' => [__d('tools', 'valErrBetweenCharacters %s %s', 3, 6)]
		];
		$this->assertEquals($expected, $this->User->validationErrors);
	}

	/**
	 * Test that validate false also works.
	 *
	 * @return void
	 */
	public function testSaveWithValidateFalse() {
		$this->User->Behaviors->load('Tools.Passwordable');
		$this->User->create();
		$data = [
			'pwd' => '123',
		];
		$this->User->set($data);
		$result = $this->User->save(null, ['validate' => false]);
		$this->assertTrue((bool)$result);

		$uid = (string)$this->User->id;

		$data = [
			'id' => $uid,
			'pwd' => '1234'
		];
		$this->User->set($data);
		$result2 = $this->User->save(null, ['validate' => false]);
		$this->assertTrue((bool)$result2);

		$this->assertTrue($result['ToolsUser']['password'] !== $result2['ToolsUser']['password']);
	}

}

/**
 * Test component
 */
class AuthTestComponent extends AuthComponent {
}

class ComplexPasswordHasher extends SimplePasswordHasher {

}