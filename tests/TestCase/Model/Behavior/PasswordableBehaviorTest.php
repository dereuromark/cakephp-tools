<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\Auth\PasswordHasherFactory;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Shim\TestSuite\TestCase;

class PasswordableBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Tools.ToolsUsers',
		'plugin.Tools.Roles',
	];

	/**
	 * @var \TestApp\Model\Table\ToolsUsersTable
	 */
	protected $Users;

	/**
	 * @var \Cake\Auth\AbstractPasswordHasher
	 */
	protected $hasher;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::delete('Passwordable');
		Configure::write('Passwordable.auth', 'AuthTest');

		$this->Users = TableRegistry::getTableLocator()->get('ToolsUsers');

		$this->hasher = PasswordHasherFactory::build('Default');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		TableRegistry::clear();

		parent::tearDown();
	}

	/**
	 * Make sure validation is triggered correctly
	 *
	 * @return void
	 */
	public function testValidate() {
		$this->Users->addBehavior('Tools.Passwordable', []);

		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);
		$this->assertEquals(['pwd_repeat'], array_keys((array)$user->getErrors()));

		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '1234ab',
			'pwd_repeat' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);
		$this->assertEquals(['validateIdentical' => __d('tools', 'valErrPwdNotMatch')], $user->getErrors()['pwd_repeat']);

		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '123456',
			'pwd_repeat' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Test that confirm false does not require confirmation
	 *
	 * @return void
	 */
	public function testValidateNoConfirm() {
		$this->Users->addBehavior('Tools.Passwordable', ['confirm' => false]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		//debug($is);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Trigger validation and update process if no values are entered but are required
	 *
	 * @return void
	 */
	public function testValidateRequired() {
		$this->Users->addBehavior('Tools.Passwordable');
		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '',
			'pwd_repeat' => '',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);
		$this->assertEquals(['pwd', 'pwd_repeat'], array_keys((array)$user->getErrors()));
	}

	/**
	 * Validation and update process gets skipped if no values are entered
	 *
	 * @return void
	 */
	public function testValidateNotRequired() {
		$this->Users->addBehavior('Tools.Passwordable', ['require' => false]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'name' => 'foo', // we need at least one field besides the password on CREATE
			'pwd' => '',
			'pwd_repeat' => '',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		if (version_compare(Configure::version(), '3.6.0', '<')) {
			$fields = ['name', 'created', 'modified', 'id'];
		} else {
			$fields = ['name', 'id'];
		}
		$this->assertEquals($fields, $is->getVisible());

		$id = $user->id;
		$user = $this->Users->newEmptyEntity();
		$data = [
			'id' => $id,
			'pwd' => '',
			'pwd_repeat' => '',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		if (version_compare(Configure::version(), '3.6.0', '<')) {
			$fields = ['id', 'modified'];
		} else {
			$fields = ['id'];
		}
		$this->assertEquals($fields, $is->getVisible());
	}

	/**
	 * PasswordableBehaviorTest::testValidateEmptyWithCurrentPassword()
	 *
	 * @return void
	 */
	public function testValidateEmptyWithCurrentPassword() {
		$this->Users->addBehavior('Tools.Passwordable', ['current' => true]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'id' => '123',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);
		$this->assertEquals(['pwd', 'pwd_repeat', 'pwd_current'], array_keys((array)$user->getErrors()));

		$this->tearDown();
		$this->setUp();

		$this->Users->addBehavior('Tools.Passwordable', ['require' => false, 'current' => true]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'name' => 'foo',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Test aliases for field names
	 *
	 * @return void
	 */
	public function testDifferentFieldNames() {
		$this->Users->addBehavior('Tools.Passwordable', [
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'formFieldCurrent' => 'passw_current',
		]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'passw' => '123456',
			'passw_repeat' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		//debug($this->Users->data);
		$is = $this->Users->save($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Assert that allowSame false does not allow storing the same password as previously entered
	 *
	 * @return void
	 */
	public function testNotSame() {
		$user = $this->Users->newEmptyEntity();
		$data = [
			'name' => 'admin',
			'password' => $this->hasher->hash('somepwd'),
			'role_id' => '1',
		];
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);
		$userCopy = clone($user);

		$this->Users->addBehavior('Tools.Passwordable', [
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'formFieldCurrent' => 'passw_current',
			'allowSame' => false,
			'current' => true,
		]);

		$user = clone($userCopy);
		$data = [
			'passw_current' => 'something',
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$user = clone($userCopy);
		$data = [
			'passw_current' => 'somepwd',
			'passw' => 'newpwd',
			'passw_repeat' => 'newpwd',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Assert that allowSame false does not allow storing the same password as previously entered
	 *
	 * @return void
	 */
	public function testNotSameWithoutCurrentField() {
		$this->Users->addBehavior('Tools.Passwordable', [
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'allowSame' => false,
			'current' => false,
		]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		$userCopy = clone($user);
		$uid = $user->id;

		$user = clone($userCopy);
		$data = [
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse((bool)$is);

		$user = clone($userCopy);
		$data = [
			'passw' => 'newpwd',
			'passw_repeat' => 'newpwd',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
	}

	/**
	 * Assert that on edit it does not wrongly pass validation (require => false)
	 *
	 * @return void
	 */
	public function testRequireFalse() {
		$this->Users->addBehavior('Tools.Passwordable', [
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'require' => false,
		]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		$userCopy = clone($user);

		$user = clone($userCopy);
		$data = [
			'passw' => '',
			'passw_repeat' => '',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		//debug($user->getErrors());

		$user = clone($userCopy);
		$data = [
			'passw' => 'somepwd2',
			'passw_repeat' => '',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse((bool)$is);

		$user = clone($userCopy);
		$data = [
			'passw' => 'somepwd2',
			'passw_repeat' => 'somepwd2',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
	}

	/**
	 * Needs faking of pwd check...
	 *
	 * @return void
	 */
	public function testValidateCurrent() {
		$this->assertFalse($this->Users->behaviors()->has('Passwordable'));
		$user = $this->Users->newEmptyEntity();
		$data = [
			'name' => 'xyz',
			'password' => $this->hasher->hash('somepwd')];
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue(!empty($result));
		$userCopy = clone($user);
		$uid = $user->id;

		$this->Users->addBehavior('Tools.Passwordable', ['current' => true]);
		$user = clone($userCopy);
		$data = [
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
		];
		$this->Users->patchEntity($user, $data);
		$this->assertTrue($this->Users->behaviors()->has('Passwordable'));
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$user = clone($userCopy);
		$data = [
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456',
		];
		$user = $this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$user = clone($userCopy);
		$data = [
			'name' => 'Yeah',
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456',
		];
		$user->setAccess('*', false); // Mark all properties as protected
		$user->setAccess(['id'], true); // Allow id to be accessible by default
		$user = $this->Users->patchEntity($user, $data, ['fields' => ['id']]);

		$this->assertSame($userCopy['password'], $user['password']);
		$this->assertTrue($user->isDirty('pwd'));

		$options = ['validate' => true];
		$this->Users->saveOrFail($user, $options);

		$user = $this->Users->get($uid);

		// The password is updated, the name not
		$this->assertNotSame($userCopy['password'], $user['password']);
		$this->assertSame('xyz', $user['name']);

		// Proof that we manually need to add pwd, pwd_repeat etc due to a bug in CakePHP<=2.4 allowing behaviors to only modify saving,
		// not validating of additional whitelist fields. Validation for those will be just skipped, no matter what the behavior tries
		// to set.
		$user = clone($userCopy);
		$data = [
			'name' => 'Yeah',
			'pwd_current' => '123', // Obviously wrong
			'pwd' => 'some', // Too short
			'pwd_repeat' => 'somex', // Don't match
		];
		$user->setAccess('*', false); // Mark all properties as protected
		$user->setAccess(['id', 'name'], true);
		$this->Users->patchEntity($user, $data, ['fields' => ['id', 'name']]);
		// Test whitelist setting - only "password" gets auto-added, pwd, pwd_repeat etc need to be added manually
		// NOTE that I had to remove the code for adding those fields from the behavior (as it was not functional)
		// So of course, this won't work now as expected. But feel free to try to add them in the behavior. Results will be the same.
		$options = ['validate' => true];
		$is = $this->Users->save($user, $options);

		// Validation errors triggered - as expected
		$this->assertFalse($is);
		$this->assertSame(['pwd', 'pwd_repeat', 'pwd_current'], array_keys((array)$user->getErrors()));
	}

	/**
	 * Needs faking of pwd check...
	 *
	 * @return void
	 */
	public function testValidateCurrentOptional() {
		$this->assertFalse($this->Users->behaviors()->has('Passwordable'));
		$user = $this->Users->newEmptyEntity();
		$data = [
			'name' => 'xyz',
			'password' => $this->hasher->hash('somepwd')];
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue(!empty($result));
		$userCopy = clone($user);

		$this->Users->addBehavior('Tools.Passwordable', ['current' => true, 'require' => false]);
		$user = clone($userCopy);
		$data = [
			'name' => 'Yeah',
			'current' => '',
			'pwd' => '',
			'pwd_repeat' => '',
		];
		$this->Users->patchEntity($user, $data);
		$this->assertTrue($this->Users->behaviors()->has('Passwordable'));
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);

		$user = clone($userCopy);
		$data = [
			'name' => 'Yeah',
			'pwd_current' => '',
			'pwd' => '123456',
			'pwd_repeat' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$user = clone($userCopy);
		$data = [
			'name' => 'Yeah',
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456',
		];
		$user->setAccess('*', false); // Mark all properties as protected
		$user->setAccess(['id'], true); // Allow id to be accessible by default
		$user = $this->Users->patchEntity($user, $data, ['fields' => ['id']]);

		$this->assertTrue($user->isDirty('pwd'));
		$this->assertSame($userCopy['password'], $user['password']);

		$user = $this->Users->saveOrFail($user);
		$this->assertNotSame($userCopy['password'], $user['password']);
		$this->assertFalse($user->isDirty('pwd'));
	}

	/**
	 * @return void
	 */
	public function testPatchWithFieldList() {
		$this->Users->addBehavior('Tools.Passwordable', [
			'formField' => 'pwd',
			'formFieldRepeat' => 'pwd_repeat',
			'current' => false,
			'passwordHasher' => 'Complex',
		]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd',
		];
		$user->setAccess('*', false); // Mark all properties as protected
		$user->setAccess(['id'], true);
		$this->Users->patchEntity($user, $data, ['fields' => ['id']]);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);
	}

	/**
	 * @return void
	 */
	public function testPatchWithoutFieldList() {
		$this->Users->addBehavior('Tools.Passwordable', [
			'formField' => 'pwd',
			'formFieldRepeat' => 'pwd_repeat',
			'current' => false,
			'passwordHasher' => 'Complex',
			'forceFieldList' => true,
		]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'name' => 'x',
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd',
		];
		$user->setAccess('*', false); // Mark all properties as protected
		$user->setAccess(['id'], true);
		$user = $this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);

		$savedUser = $this->Users->get($user->id);

		$this->assertSame($data['name'], $savedUser->name);
		$this->assertSame($user->password, $savedUser->password);
	}

	/**
	 * PasswordableBehaviorTest::testBlowfish()
	 *
	 * @return void
	 */
	public function testBlowfish() {
		$this->Users->addBehavior('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
		]);

		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd',
		];
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);
		$userCopy = clone($user);
		$uid = (string)$user->id;

		$this->Users->removeBehavior('Passwordable');
		$this->Users->addBehavior('Tools.Passwordable', ['current' => true]);

		// Without the current password it will not continue
		$data = [
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
		];
		$this->Users->patchEntity($user, $data);
		$this->assertTrue($this->Users->behaviors()->has('Passwordable'));
		$result = $this->Users->save($user);
		$this->assertFalse($result);

		// Without the correct current password it will not continue
		$user = clone($userCopy);
		$data = [
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertFalse($result);

		// Now it will
		$user = clone($userCopy);
		$data = [
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456',
		];
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);
	}

	/**
	 * Tests needsPasswordRehash()
	 *
	 * @return void
	 */
	public function testNeedsPasswordRehash() {
		$this->Users->addBehavior('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
			'passwordHasher' => 'Default',
		]);

		$hash = password_hash('foobar', PASSWORD_BCRYPT);
		$result = $this->Users->needsPasswordRehash($hash);
		$this->assertFalse($result);

		$hash = sha1('foobar');
		$result = $this->Users->needsPasswordRehash($hash);
		$this->assertTrue($result);
	}

	/**
	 * Tests needsPasswordRehash()
	 *
	 * @return void
	 */
	public function testNeedsPasswordRehashWithNotSupportedHasher() {
		$this->Users->addBehavior('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
		]);

		$hash = password_hash('foobar', PASSWORD_BCRYPT);
		$result = $this->Users->needsPasswordRehash($hash);
		$this->assertFalse($result);

		$this->Users->removeBehavior('Passwordable');
		$this->Users->addBehavior('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
			'passwordHasher' => 'Default',
		]);

		$hash = password_hash('foobar', PASSWORD_BCRYPT);
		$result = $this->Users->needsPasswordRehash($hash);
		$this->assertFalse($result);
	}

	/**
	 * PasswordableBehaviorTest::testSettings()
	 *
	 * @return void
	 */
	public function testSettings() {
		// Pwd min and max length
		$this->Users->addBehavior('Tools.Passwordable', [
			'allowSame' => false,
			'current' => false,
			'minLength' => 3,
			'maxLength' => 6,
		]);
		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '123',
			'pwd_repeat' => '123',
		];
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);

		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '12345678',
			'pwd_repeat' => '12345678',
		];
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertFalse($result);
		$expected = [
			'pwd' => ['between' => __d('tools', 'valErrBetweenCharacters {0} {1}', 3, 6)],
		];
		$this->assertEquals($expected, $user->getErrors());
	}

	/**
	 * Test that validate false also works.
	 *
	 * @return void
	 */
	public function testSaveWithValidateFalse() {
		$this->Users->addBehavior('Tools.Passwordable');
		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '123',
		];
		$this->Users->patchEntity($user, $data, ['validate' => false]);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);

		$uid = (string)$user->id;
		$hash = $user['password'];

		$data = [
			'pwd' => '1234',
		];
		$this->Users->patchEntity($user, $data, ['validate' => false]);
		$result2 = $this->Users->save($user);
		$this->assertTrue((bool)$result2);
		$hash2 = $user['password'];
		$this->assertTrue($hash !== $hash2);
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
			],
		];
		$this->Users->addBehavior('Tools.Passwordable', [
			'customValidation' => $rules]);

		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => '%123456',
			'pwd_repeat' => '%123456',
		];
		$this->Users->patchEntity($user, $data);

		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$result = $user->getErrors();
		$expected = ['pwd' => ['validateCustom' => 'Foo Bar']];
		$this->assertSame($expected, $result);

		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => 'abc123',
			'pwd_repeat' => 'abc123',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$result = $user->getErrors();
		$expected = ['pwd' => ['validateCustomExt' => 'Foo Bar Ext']];
		$this->assertSame($expected, $result);

		$user = $this->Users->newEmptyEntity();
		$data = [
			'pwd' => 'abcdef',
			'pwd_repeat' => 'abcdef',
		];
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
	}

}
