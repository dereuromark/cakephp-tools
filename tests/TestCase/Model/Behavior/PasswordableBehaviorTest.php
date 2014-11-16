<?php

namespace Tools\Model\Behavior;

use Cake\TestSuite\TestCase;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Core\Configure;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\Routing\Router;
use Cake\Network\Request;
use Cake\Auth\PasswordHasherFactory;

class PasswordableBehaviorTest extends TestCase {

	public $fixtures = array(
		'plugin.tools.tools_users', 'plugin.tools.roles',
	);

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		Configure::delete('Passwordable');
		Configure::write('Passwordable.auth', 'AuthTest');

		$this->Users = TableRegistry::get('ToolsUsers');
		/*
		if (isset($this->Users->validate['pwd'])) {
			unset($this->Users->validate['pwd']);
		}
		if (isset($this->Users->validate['pwd_repeat'])) {
			unset($this->Users->validate['pwd_repeat']);
		}
		if (isset($this->Users->validate['pwd_current'])) {
			unset($this->Users->validate['pwd_current']);
		}
		if (isset($this->Users->order)) {
			unset($this->Users->order);
		}
		*/

		$this->hasher = PasswordHasherFactory::build('Default');
		$user = $this->Users->newEntity();
		$data = array(
			'id' => '5',
			'name' => 'admin',
			'password' => $this->hasher->hash('somepwd'),
			'role_id' => '1'
		);
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		//$this->assertTrue();

		Router::setRequestInfo(new Request());
	}

	public function tearDown() {
		TableRegistry::clear();

		parent::tearDown();
	}

	/**
	 * Make sure validation is triggered correctly
	 *
	 * @return void
	 */
	public function testValidate() {
		$this->Users->addBehavior('Tools.Passwordable', array());

		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => '123456',
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		//debug($user->errors());
		$this->assertFalse($is);
		$this->assertEquals(array('pwd_repeat'), array_keys($user->errors()));

		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => '1234ab',
			'pwd_repeat' => '123456'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		//debug($user->errors());
		$this->assertFalse($is);
		$this->assertEquals(array('validateIdentical' => __d('tools', 'valErrPwdNotMatch')), $user->errors()['pwd_repeat']);

		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->Users->patchEntity($user, $data);
		//debug($this->Users->validate);
		$is = $this->Users->validate($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Test that confirm false does not require confirmation
	 *
	 * @return void
	 */
	public function testValidateNoConfirm() {
		$this->Users->addBehavior('Tools.Passwordable', array('confirm' => false));
		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => '123456',
		);
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
		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);
		$this->assertEquals(array('pwd', 'pwd_repeat'), array_keys($user->errors()));
	}

	/**
	 * Validation and update process gets skipped if no values are entered
	 *
	 * @return void
	 */
	public function testValidateNotRequired() {
		$this->Users->addBehavior('Tools.Passwordable', array('require' => false));
		$user = $this->Users->newEntity();
		$data = array(
			'name' => 'foo', // we need at least one field besides the password on CREATE
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		$this->assertEquals(array('name', 'id'), $is->visibleProperties());

		$id = $user->id;
		$user = $this->Users->newEntity();
		$data = array(
			'id' => $id,
			'pwd' => '',
			'pwd_repeat' => ''
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		$this->assertEquals(array('id'), $is->visibleProperties());
	}

	/**
	 * PasswordableBehaviorTest::testValidateEmptyWithCurrentPassword()
	 *
	 * @return void
	 */
	public function testValidateEmptyWithCurrentPassword() {
		$this->Users->addBehavior('Tools.Passwordable', array('current' => true));
		$user = $this->Users->newEntity();
		$data = array(
			'id' => '123',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '123456',
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		//debug($user->errors());
		$this->assertFalse($is);
		$this->assertEquals(array('pwd', 'pwd_repeat', 'pwd_current'), array_keys($user->errors()));

		$this->tearDown();
		$this->setUp();

		$this->Users->removeBehavior('Passwordable');
		$this->Users->addBehavior('Tools.Passwordable', array('require' => false, 'current' => true));
		$user = $this->Users->newEntity();
		$data = array(
			'name' => 'foo',
			'pwd' => '',
			'pwd_repeat' => '',
			'pwd_current' => '',
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Test aliases for field names
	 */
	public function testDifferentFieldNames() {
		$this->Users->addBehavior('Tools.Passwordable', array(
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'formFieldCurrent' => 'passw_current',
		));
		$user = $this->Users->newEntity();
		$data = array(
			'passw' => '123456',
			'passw_repeat' => '123456'
		);
		$this->Users->patchEntity($user, $data);
		//debug($this->Users->data);
		$is = $this->Users->save($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Assert that allowSame false does not allow storing the same password as previously entered
	 */
	public function testNotSame() {
		$this->Users->addBehavior('Tools.Passwordable', array(
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'formFieldCurrent' => 'passw_current',
			'allowSame' => false,
			'current' => true,
		));
		$user = $this->Users->newEntity();
		$data = array(
			'id' => '5',
			'passw_current' => 'something',
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		//debug($user->errors());
		$this->assertFalse($is);

		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => '5',
			'passw_current' => 'somepwd',
			'passw' => 'newpwd',
			'passw_repeat' => 'newpwd'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Assert that allowSame false does not allow storing the same password as previously entered
	 */
	public function testNotSameWithoutCurrentField() {
		$this->Users->addBehavior('Tools.Passwordable', array(
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'allowSame' => false,
			'current' => false
		));
		$user = $this->Users->newEntity();
		$data = array(
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		$id = $is['id'];

		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => $id,
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse((bool)$is);

		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => $id,
			'passw' => 'newpwd',
			'passw_repeat' => 'newpwd'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
	}

	/**
	 * Assert that on edit it does not wrongly pass validation (require => false)
	 */
	public function testRequireFalse() {
		$this->Users->addBehavior('Tools.Passwordable', array(
			'formField' => 'passw',
			'formFieldRepeat' => 'passw_repeat',
			'require' => false
		));
		$user = $this->Users->newEntity();
		$data = array(
			'passw' => 'somepwd',
			'passw_repeat' => 'somepwd'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
		$id = $is['id'];

		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => $id,
			'passw' => 'somepwd2',
			'passw_repeat' => ''
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse((bool)$is);
		//debug($user->errors());

		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => $id,
			'passw' => 'somepwd2',
			'passw_repeat' => 'somepwd2'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue((bool)$is);
	}

	/**
	 * Needs faking of pwd check...
	 */
	public function testValidateCurrent() {
		$this->assertFalse($this->Users->behaviors()->loaded('Passwordable'));
		$user = $this->Users->newEntity();
		$data = array(
			'name' => 'xyz',
			'password' => $this->hasher->hash('somepwd'));
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue(!empty($result));
		$uid = (string)$user->id;

		$this->Users->removeBehavior('Passwordable');
		$this->Users->addBehavior('Tools.Passwordable', array('current' => true));
		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
			//'pwd_current' => '',
		);
		$this->Users->patchEntity($user, $data);
		$this->assertTrue($this->Users->behaviors()->loaded('Passwordable'));
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => $uid,
			'name' => 'Yeah',
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$user->accessible('*', false); // Mark all properties as protected
		$user->accessible(array('id', 'pwd', 'pwd_repeat', 'pwd_current'), true);
		$this->Users->patchEntity($user, $data);
		// Test whitelist setting - only "password" needs to gets auto-added
		$options = array('validate' => true, 'fieldList' => array('id', 'pwd', 'pwd_repeat', 'pwd_current'));

		$is = $this->Users->save($user, $options);
		$this->assertTrue(!empty($is));

		//$this->skipIf(true, 'FIXME: whitelisting fieldList');

		$user = $this->Users->get($uid);
		// The password is updated, the name not
		$this->assertSame($is['password'], $user['password']);
		$this->assertSame('xyz', $user['name']);

		// Proof that we manually need to add pwd, pwd_repeat etc due to a bug in CakePHP<=2.4 allowing behaviors to only modify saving,
		// not validating of additional whitelist fields. Validation for those will be just skipped, no matter what the behavior tries
		// to set.
		$user = $this->Users->newEntity([], ['markNew' => false]);
		$data = array(
			'id' => $uid,
			'name' => 'Yeah',
			'pwd_current' => '123', // Obviously wrong
			'pwd' => 'some', // Too short
			'pwd_repeat' => 'somex' // Don't match
		);
		$user->accessible('*', false); // Mark all properties as protected
		$user->accessible(array('id', 'name'), true);
		$this->Users->patchEntity($user, $data);
		// Test whitelist setting - only "password" gets auto-added, pwd, pwd_repeat etc need to be added manually
		// NOTE that I had to remove the code for adding those fields from the behavior (as it was not functional)
		// So of course, this won't work now as expected. But feel free to try to add them in the behavior. Results will be the same.
		$options = array('validate' => true, 'fieldList' => array('id', 'name'));
		$is = $this->Users->save($user, $options);

		// Validation errors triggered - as expected
		$this->assertFalse($is);
		$this->assertSame(array('pwd', 'pwd_repeat', 'pwd_current'), array_keys($user->errors()));
	}

	/**
	 * Test cake2.4 passwordHasher feature
	 *
	 * @return void
	 */
	public function testPasswordHasher() {
		$this->skipIf((float)Configure::version() < 2.4, 'Needs 2.4 and above');

		$this->Users->addBehavior('Tools.Passwordable', array(
			'formField' => 'pwd',
			'formFieldRepeat' => 'pwd_repeat',
			'allowSame' => false,
			'current' => false,
			'passwordHasher' => 'Complex',
		));
		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd'
		);
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);
		$uid = (string)$user->id;

		$this->Users->removeBehavior('Passwordable');
		$this->Users->addBehavior('Tools.Passwordable', array('current' => true));
		$user = $this->Users->newEntity();
		$data = array(
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
			//'pwd_current' => '',
		);
		$this->Users->patchEntity($user, $data);
		$this->assertTrue($this->Users->behaviors()->loaded('Passwordable'));
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$user = $this->Users->newEntity();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertFalse($is);

		$user = $this->Users->newEntity();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->Users->patchEntity($user, $data);
		$is = $this->Users->save($user);
		$this->assertTrue(!empty($is));
	}

	/**
	 * PasswordableBehaviorTest::testBlowfish()
	 *
	 * @return void
	 */
	public function testBlowfish() {
		$this->Users->addBehavior('Tools.Passwordable', array(
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
		));

		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => 'somepwd',
			'pwd_repeat' => 'somepwd'
		);
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);
		$uid = (string)$user->id;

		$this->Users->removeBehavior('Passwordable');
		$this->Users->addBehavior('Tools.Passwordable', array('current' => true));

		// Without the current password it will not continue
		$user = $this->Users->newEntity();
		$data = array(
			'id' => $uid,
			'pwd' => '123456',
			'pwd_repeat' => '12345678',
		);
		$this->Users->patchEntity($user, $data);
		$this->assertTrue($this->Users->behaviors()->loaded('Passwordable'));
		$result = $this->Users->save($user);
		$this->assertFalse($result);

		// Without the correct current password it will not continue
		$user = $this->Users->newEntity();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwdx',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertFalse($result);

		// Now it will
		$user = $this->Users->newEntity();
		$data = array(
			'id' => $uid,
			'pwd_current' => 'somepwd',
			'pwd' => '123456',
			'pwd_repeat' => '123456'
		);
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
		$this->Users->addBehavior('Tools.Passwordable', array(
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
			'passwordHasher' => 'Default'
		));

		$hash =  password_hash('foobar', PASSWORD_BCRYPT);
		$result = $this->Users->needsPasswordRehash($hash);
		$this->assertFalse($result);

		$hash =  sha1('foobar');
		$result = $this->Users->needsPasswordRehash($hash);
		$this->assertTrue($result);
	}

	/**
	 * Tests needsPasswordRehash()
	 *
	 * @return void
	 */
	public function testNeedsPasswordRehashWithNotSupportedHasher() {
		$this->Users->addBehavior('Tools.Passwordable', array(
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
		));

		$hash =  password_hash('foobar', PASSWORD_BCRYPT);
		$result = $this->Users->needsPasswordRehash($hash);
		$this->assertFalse($result);

		$this->Users->removeBehavior('Passwordable');
		$this->Users->addBehavior('Tools.Passwordable', array(
			'allowSame' => false,
			'current' => false,
			'authType' => 'Blowfish',
			'passwordHasher' => 'Default'
		));

		$hash =  password_hash('foobar', PASSWORD_BCRYPT);
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
		$this->Users->addBehavior('Tools.Passwordable', array(
			'allowSame' => false,
			'current' => false,
			'minLength' => 3,
			'maxLength' => 6,
		));
		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => '123',
			'pwd_repeat' => '123'
		);
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertTrue((bool)$result);

		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => '12345678',
			'pwd_repeat' => '12345678'
		);
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user);
		$this->assertFalse($result);
		$expected = array(
			'pwd' => array('between' => __d('tools', 'valErrBetweenCharacters {0} {1}', 3, 6)),
			'pwd_repeat' => array('between' =>__d('tools', 'valErrBetweenCharacters {0} {1}', 3, 6))
		);
		$this->assertEquals($expected, $user->errors());
	}

	/**
	 * Test that validate false also works.
	 *
	 * @return void
	 */
	public function testSaveWithValidateFalse() {
		$this->Users->addBehavior('Tools.Passwordable');
		$user = $this->Users->newEntity();
		$data = array(
			'pwd' => '123',
		);
		$this->Users->patchEntity($user, $data);
		$result = $this->Users->save($user, array('validate' => false));
		$this->assertTrue((bool)$result);

		$uid = (string)$user->id;
		$hash = $user['password'];

		$data = array(
			'id' => $uid,
			'pwd' => '1234'
		);
		$this->Users->patchEntity($user, $data);
		$result2 = $this->Users->save($user, array('validate' => false));
		$this->assertTrue((bool)$result2);
		$hash2 = $user['password'];
		$this->assertTrue($hash !== $hash2);
	}

}
