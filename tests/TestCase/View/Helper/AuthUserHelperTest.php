<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\View\View;
use Tools\TestSuite\TestCase;
use Tools\View\Helper\AuthUserHelper;

/**
 * AuthUserHelper class
 */
class AuthUserHelperTest extends TestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = ['core.sessions'];

	/**
	 * @var \Tools\View\Helper\AuthUserHelper
	 */
	public $AuthUser;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->request = $this->getMock('Cake\Network\Request', ['cookie']);
		$this->view = new View($this->request);
		$this->AuthUser = new AuthUserHelper($this->view);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * testSessionReadWrite method
	 *
	 * @return void
	 * @expectedException RuntimeException
	 */
	public function testEmptyAuthSessionDueToMissing() {
		$this->AuthUser->id();
	}

	/**
	 * AuthUserHelperTest::testEmptyAuthSession()
	 *
	 * @return void
	 */
	public function testEmptyAuthSession() {
		$this->view->viewVars['authUser'] = [];
		$this->assertNull($this->AuthUser->id());

		$this->assertFalse($this->AuthUser->isMe(null));
		$this->assertFalse($this->AuthUser->isMe(''));
		$this->assertFalse($this->AuthUser->isMe(0));
		$this->assertFalse($this->AuthUser->isMe(1));
	}

	/**
	 * AuthUserHelperTest::testId()
	 *
	 * @return void
	 */
	public function testId() {
		$this->view->viewVars['authUser'] = ['id' => '1'];

		$this->assertSame('1', $this->AuthUser->id());
	}


	/**
	 * AuthUserHelperTest::testId()
	 *
	 * @return void
	 */
	public function testIsMe() {
		$this->view->viewVars['authUser'] = ['id' => '1'];

		$this->assertFalse($this->AuthUser->isMe(null));
		$this->assertFalse($this->AuthUser->isMe(''));
		$this->assertFalse($this->AuthUser->isMe(0));

		$this->assertTrue($this->AuthUser->isMe('1'));
		$this->assertTrue($this->AuthUser->isMe(1));
	}

	/**
	 * AuthUserHelperTest::testUser()
	 *
	 * @return void
	 */
	public function testUser() {
		$this->view->viewVars['authUser'] = ['id' => '1', 'username' => 'foo'];

		$this->assertSame(['id' => '1', 'username' => 'foo'], $this->AuthUser->user());
		$this->assertSame('foo', $this->AuthUser->user('username'));
		$this->assertNull($this->AuthUser->user('foofoo'));
	}

	/**
	 * AuthUserHelperTest::testRoles()
	 *
	 * @return void
	 */
	public function testRoles() {
		$this->view->viewVars['authUser'] = ['id' => '1', 'Roles' => ['1', '2']];

		$this->assertSame(['1', '2'], $this->AuthUser->roles());
	}

	/**
	 * AuthUserHelperTest::testRolesDeep()
	 *
	 * @return void
	 */
	public function testRolesDeep() {
		$this->view->viewVars['authUser'] = ['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]];

		$this->assertSame(['1', '2'], $this->AuthUser->roles());
	}

	/**
	 * AuthUserHelperTest::testHasRole()
	 *
	 * @return void
	 */
	public function testHasRole() {
		$this->view->viewVars['authUser'] = ['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]];

		$this->assertTrue($this->AuthUser->hasRole(2));
		$this->assertTrue($this->AuthUser->hasRole('2'));
		$this->assertFalse($this->AuthUser->hasRole(3));

		$this->assertTrue($this->AuthUser->hasRole(3, [1, 3]));
		$this->assertFalse($this->AuthUser->hasRole(3, [2, 4]));
	}

	/**
	 * AuthUserHelperTest::testHasRoles()
	 *
	 * @return void
	 */
	public function testHasRoles() {
		$this->view->viewVars['authUser'] = ['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]];

		$this->assertTrue($this->AuthUser->hasRoles([2]));
		$this->assertTrue($this->AuthUser->hasRoles('2'));
		$this->assertFalse($this->AuthUser->hasRoles([3, 4]));
		$this->assertTrue($this->AuthUser->hasRoles([1, 2], false));

		$this->assertTrue($this->AuthUser->hasRoles([1, 6], [1, 3, 5]));
		$this->assertFalse($this->AuthUser->hasRoles([3, 4], [2, 4]));

		$this->assertFalse($this->AuthUser->hasRoles([1, 3, 5], false, [1, 3]));
		$this->assertTrue($this->AuthUser->hasRoles([1, 3, 5], false, [1, 3, 5]));
	}

}
