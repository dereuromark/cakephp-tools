<?php
/**
 * TinyAuthorizeTest file
 *
 */
App::uses('TinyAuthorize', 'Tools.Controller/Component/Auth');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('Controller', 'Controller');
App::uses('ComponentCollection', 'Controller');
App::uses('CakeRequest', 'Network');

/**
 * Test case for DirectAuthentication
 *
 */
class TinyAuthorizeTest extends MyCakeTestCase {

	public $fixtures = array('core.user', 'core.auth_user', 'plugin.tools.role');

	public $Collection;

	public $request;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->Collection = new ComponentCollection();

		$this->request = new CakeRequest(null, false);

		$aclData = <<<INI
[Users]
; add = public
edit = user
admin_index = admin
[Comments]
; index is public
add,edit,delete = user
* = admin
[Tags]
add = *
very_long_action_name_action = user
public_action = public
INI;
		file_put_contents(TMP . 'acl.ini', $aclData);
		$this->assertTrue(file_exists(TMP . 'acl.ini'));

		Configure::write('Role', array('user' => 1, 'moderator' => 2, 'admin' => 3, 'public' => -1));
	}

	public function tearDown() {
		unlink(TMP . 'acl.ini');

		parent::tearDown();
	}

	/**
	 * Test applying settings in the constructor
	 *
	 * @return void
	 */
	public function testConstructor() {
		$object = new TestTinyAuthorize($this->Collection, array(
			'aclModel' => 'AuthRole',
			'aclKey' => 'auth_role_id',
			'autoClearCache' => true,
		));
		$this->assertEquals('AuthRole', $object->settings['aclModel']);
		$this->assertEquals('auth_role_id', $object->settings['aclKey']);
	}

	/**
	 * @return void
	 */
	public function testGetAcl() {
		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));
		$res = $object->getAcl();

		$expected = array(
			'users' => array(
				'edit' => array(1),
				'admin_index' => array(3)
			),
			'comments' => array(
				'add' => array(1),
				'edit' => array(1),
				'delete' => array(1),
				'*' => array(3),
			),
			'tags' => array(
				'add' => array(1, 2, 3, -1),
				'very_long_action_name_action' => array(1),
				'public_action' => array(-1)
			),
		);
		$this->debug($res);
		$this->assertEquals($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodDisallowed() {
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'edit';

		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));
		$this->assertEquals('Role', $object->settings['aclModel']);
		$this->assertEquals('role_id', $object->settings['aclKey']);

		$user = array(
			'role_id' => 4,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = array(
			'role_id' => 3,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowed() {
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'edit';

		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		// single role_id field in users table
		$user = array(
			'role_id' => 1,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['action'] = 'admin_index';

		$user = array(
			'role_id' => 3,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWithLongActionNames() {
		$this->request->params['controller'] = 'tags';
		$this->request->params['action'] = 'very_long_action_name_action';

		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		// single role_id field in users table
		$user = array(
			'role_id' => 1,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$user = array(
			'role_id' => 3,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedMultiRole() {
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'admin_index';

		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		// flat list of roles
		$user = array(
			'Role' => array(1, 3),
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		// verbose role defition using the new 2.x contain param for Auth
		$user = array(
			'Role' => array(array('id' => 1, 'RoleUser' => array()), array('id' => 3, 'RoleUser' => array())),
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testBasicUserMethodAllowedWildcard() {
		$this->request->params['controller'] = 'tags';
		$this->request->params['action'] = 'public_action';

		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		$user = array(
			'role_id' => 6,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testUserMethodsAllowed() {
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'some_action';

		$object = new TestTinyAuthorize($this->Collection, array('allowUser' => true, 'autoClearCache' => true));

		$user = array(
			'role_id' => 1,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'admin_index';

		$object = new TestTinyAuthorize($this->Collection, array('allowUser' => true, 'autoClearCache' => true));

		$user = array(
			'role_id' => 1,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = array(
			'role_id' => 3,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * @return void
	 */
	public function testAdminMethodsAllowed() {
		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'some_action';
		$config = array('allowAdmin' => true, 'adminRole' => 3, 'autoClearCache' => true);

		$object = new TestTinyAuthorize($this->Collection, $config);

		$user = array(
			'role_id' => 1,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'admin_index';

		$object = new TestTinyAuthorize($this->Collection, $config);

		$user = array(
			'role_id' => 1,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$user = array(
			'role_id' => 3,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Should only be used in combination with Auth->allow() to mark those as public in the acl.ini, as well.
	 * Not necessary and certainly not recommended as acl.ini only.
	 *
	 * @return void
	 */
	public function testBasicUserMethodAllowedPublically() {
		$this->request->params['controller'] = 'tags';
		$this->request->params['action'] = 'add';

		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		$user = array(
			'role_id' => 2,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		$this->request->params['controller'] = 'comments';
		$this->request->params['action'] = 'foo';

		$user = array(
			'role_id' => 3,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * TinyAuthorizeTest::testWithRoleTable()
	 *
	 * @return void
	 */
	public function testWithRoleTable() {
		$User = ClassRegistry::init('User');
		$User->bindModel(array('belongsTo' => array('Role')), false);

		// We want the session to be used.
		Configure::delete('Role');

		$this->request->params['controller'] = 'users';
		$this->request->params['action'] = 'edit';

		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		// User role is 4 here, though. Also contains left joined Role date here just to check that it works, too.
		$user = array(
			'Role' => array(
				'id' => '4',
				'alias' => 'user',
			),
			'role_id' => 4,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);

		Configure::delete('Role');
		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		$user = array(
			'role_id' => 6,
		);
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->assertTrue((bool)(Configure::read('Role')));

		// Multi-role test - failure
		Configure::delete('Role');
		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		$user = array(
			'Role' => array(
				array('id' => 7, 'alias' => 'user'),
				array('id' => 8, 'alias' => 'partner'),
			)
		);
		$res = $object->authorize($user, $this->request);
		$this->assertFalse($res);

		$this->assertTrue((bool)(Configure::read('Role')));

		Configure::delete('Role');
		$object = new TestTinyAuthorize($this->Collection, array('autoClearCache' => true));

		// Multi-role test
		$user = array(
			'Role' => array(
				array('id' => 4, 'alias' => 'user'),
				array('id' => 6, 'alias' => 'partner'),
			)
		);
		$res = $object->authorize($user, $this->request);
		$this->assertTrue($res);
	}

	/**
	 * Tests superadmin role, allowed to all actions
	 *
	 * @return void
	 */
	public function testSuperadminRole() {
		$object = new TestTinyAuthorize($this->Collection, array(
			'autoClearCache' => true,
			'superadminRole' => 9
		));
		$res = $object->getAcl();
		$user = array(
			'role_id' => 9,
		);

		foreach ($object->getAcl() as $controller => $actions) {
			foreach ($actions as $action => $allowed) {
				$this->request->params['controller'] = $controller;
				$this->request->params['action'] = $action;

				$res = $object->authorize($user, $this->request);
				$this->assertTrue($res);
			}
		}
	}

}

class TestTinyAuthorize extends TinyAuthorize {

	public function matchArray() {
		return $this->_matchArray;
	}

	public function getAcl() {
		return $this->_getAcl();
	}

	protected function _getAcl($path = TMP) {
		return parent::_getAcl($path);
	}

}
