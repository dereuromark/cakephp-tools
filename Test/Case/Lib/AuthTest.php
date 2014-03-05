<?php
App::uses('Auth', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 */
class AuthTest extends MyCakeTestCase {

	public $fixtures = array('core.cake_session');

	public function setUp() {
		parent::setUp();

		ClassRegistry::init(array('table' => 'cake_sessions', 'class' => 'Session', 'alias' => 'Session'));
	}

	public function tearDown() {
		parent::tearDown();

		ClassRegistry::flush();

		CakeSession::delete('Auth');
	}

	/**
	 * AuthTest::testId()
	 *
	 * @return void
	 */
	public function testId() {
		$id = Auth::id();
		$this->assertNull($id);

		CakeSession::write('Auth.User.id', 1);
		$id = Auth::id();
		$this->assertEquals(1, $id);
	}

	/**
	 * AuthTest::testHasRole()
	 *
	 * @return void
	 */
	public function testHasRole() {
		$res = Auth::hasRole(1, array(2, 3, 6));
		$this->assertFalse($res);

		$res = Auth::hasRole(3, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRole(3, 1);
		$this->assertFalse($res);

		$res = Auth::hasRole(3, '3');
		$this->assertTrue($res);

		$res = Auth::hasRole(3, '');
		$this->assertFalse($res);
	}

	/**
	 * AuthTest::testHasRoleWithSession()
	 *
	 * @return void
	 */
	public function testHasRoleWithSession() {
		if (!defined('USER_ROLE_KEY')) {
			define('USER_ROLE_KEY', 'Role');
		}
		CakeSession::write('Auth.User.id', 1);
		$roles = array(
			array('id' => '1', 'name' => 'User', 'alias' => 'user'),
			array('id' => '2', 'name' => 'Moderator', 'alias' => 'moderator'),
			array('id' => '3', 'name' => 'Admin', 'alias' => 'admin'),
		);
		CakeSession::write('Auth.User.' . USER_ROLE_KEY, $roles);

		$res = Auth::hasRole(4);
		$this->assertFalse($res);

		$res = Auth::hasRole(3);
		$this->assertTrue($res);
	}

	/**
	 * AuthTest::testHasRoles()
	 *
	 * @return void
	 */
	public function testHasRoles() {
		$res = Auth::hasRoles(array(1, 3), true, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(array(3), true, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(3, true, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(array(), true, array(2, 3, 6));
		$this->assertFalse($res);

		$res = Auth::hasRoles(null, true, array(2, 3, 6));
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(2, 7), false, array(2, 3, 6));
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(2, 6), false, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(array(2, 6), true, array(2, 3, 6));
		$this->assertTrue($res);

		$res = Auth::hasRoles(array(9, 11), true, array());
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(9, 11), true, '');
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(2, 7), false, array());
		$this->assertFalse($res);

		$res = Auth::hasRoles(array(2, 7), false);
		$this->assertFalse($res);
	}

}
