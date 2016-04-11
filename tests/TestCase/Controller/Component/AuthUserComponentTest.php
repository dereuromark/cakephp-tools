<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Network\Request;
use Tools\Controller\Component\AuthUserComponent;
use Tools\TestSuite\TestCase;

/**
 * AuthUserComponent class
 */
class AuthUserComponentTest extends TestCase {

	/**
	 * fixtures
	 *
	 * @var array
	 */
	public $fixtures = ['core.sessions'];

	/**
	 * @var \Tools\Controller\Component\AuthUserComponent
	 */
	public $AuthUser;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$controller = new Controller(new Request());
		$this->ComponentRegistry = new ComponentRegistry($controller);
		$this->AuthUser = new AuthUserComponent($this->ComponentRegistry);
		$this->AuthUser->Auth = $this->getMock('Cake\Controller\Component\AuthComponent', ['user'], [$this->ComponentRegistry]);
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
	 */
	public function testEmptyAuthSession() {
		$this->assertNull($this->AuthUser->id());

		$this->assertFalse($this->AuthUser->isMe(null));
		$this->assertFalse($this->AuthUser->isMe(''));
		$this->assertFalse($this->AuthUser->isMe(0));
		$this->assertFalse($this->AuthUser->isMe(1));
	}

	/**
	 * AuthUserComponentTest::testId()
	 *
	 * @return void
	 */
	public function testId() {
		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1']));

		$this->assertSame('1', $this->AuthUser->id());
	}

	/**
	 * AuthUserComponentTest::testId()
	 *
	 * @return void
	 */
	public function testIsMe() {
		$this->AuthUser->Auth->expects($this->any())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1']));

		$this->assertFalse($this->AuthUser->isMe(null));
		$this->assertFalse($this->AuthUser->isMe(''));
		$this->assertFalse($this->AuthUser->isMe(0));

		$this->assertTrue($this->AuthUser->isMe('1'));
		$this->assertTrue($this->AuthUser->isMe(1));
	}

	/**
	 * AuthUserComponentTest::testUser()
	 *
	 * @return void
	 */
	public function testUser() {
		$this->AuthUser->Auth->expects($this->any())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'username' => 'foo']));

		$this->assertSame(['id' => '1', 'username' => 'foo'], $this->AuthUser->user());
		$this->assertSame('foo', $this->AuthUser->user('username'));
		$this->assertNull($this->AuthUser->user('foofoo'));
	}

	/**
	 * AuthUserComponentTest::testRoles()
	 *
	 * @return void
	 */
	public function testRoles() {
		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'Roles' => ['1', '2']]));

		$this->assertSame(['1', '2'], $this->AuthUser->roles());
	}

	/**
	 * AuthUserComponentTest::testRolesDeep()
	 *
	 * @return void
	 */
	public function testRolesDeep() {
		$this->AuthUser->Auth->expects($this->once())
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]]));

		$this->assertSame(['1', '2'], $this->AuthUser->roles());
	}

	/**
	 * AuthUserComponentTest::testHasRole()
	 *
	 * @return void
	 */
	public function testHasRole() {
		$this->AuthUser->Auth->expects($this->exactly(3))
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]]));

		$this->assertTrue($this->AuthUser->hasRole(2));
		$this->assertTrue($this->AuthUser->hasRole('2'));
		$this->assertFalse($this->AuthUser->hasRole(3));

		$this->assertTrue($this->AuthUser->hasRole(3, [1, 3]));
		$this->assertFalse($this->AuthUser->hasRole(3, [2, 4]));
	}

	/**
	 * AuthUserComponentTest::testHasRoles()
	 *
	 * @return void
	 */
	public function testHasRoles() {
		$this->AuthUser->Auth->expects($this->exactly(6))
			->method('user')
			->with(null)
			->will($this->returnValue(['id' => '1', 'Roles' => [['id' => '1'], ['id' => '2']]]));

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
