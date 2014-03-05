<?php
App::uses('AuthExtComponent', 'Tools.Controller/Component');
App::uses('Controller', 'Controller');
App::uses('AppModel', 'Model');

/**
 * Test case for AuthExt
 */
class AuthExtComponentTest extends CakeTestCase {

	public $fixtures = array('plugin.tools.tools_user', 'plugin.tools.role', 'core.cake_session');

	public function setUp() {
		parent::setUp();

		Configure::delete('Auth');

		$this->Controller = new AuthExtTestController(new CakeRequest, new CakeResponse);
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();

		$this->Controller->User->belongsTo = array(
			'Role' => array(
				'className' => 'Tools.Role'
			)
		);
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Controller->TestAuthExt);
		unset($this->Controller);
	}

	/**
	 * AuthExtComponentTest::testBasics()
	 *
	 * @return void
	 */
	public function testBasics() {
		$res = $this->Controller->TestAuthExt->allow();

		$is = $this->Controller->TestAuthExt->getModel();
		$this->assertTrue(is_object($is) && $is->name === 'User');
	}

	/**
	 * AuthExtComponentTest::_testCompleteAuth()
	 *
	 * @return void
	 */
	public function _testCompleteAuth() {
		$is = $this->Controller->TestAuthExt->completeAuth(1);
		debug($is);
		$this->assertTrue(!empty($is));

		$is = $this->Controller->TestAuthExt->completeAuth(111);
		echo returns($is);
		$this->assertFalse($is);
	}

}

class TestAuthExtComponent extends AuthExtComponent {
}

class AuthExtTestController extends Controller {

	public $uses = array('User');

	/**
	 * Components property
	 *
	 * @var array
	 */
	public $components = array('Session', 'TestAuthExt' => array('userModel' => 'AuthUser', 'parentModelAlias' => 'Role'));

	/**
	 * Failed property
	 *
	 * @var boolean
	 */
	public $failed = false;

	/**
	 * Used for keeping track of headers in test
	 *
	 * @var array
	 */
	public $testHeaders = array();

	/**
	 * Fail method
	 *
	 * @return void
	 */
	public function fail() {
		$this->failed = true;
	}

	/**
	 * Redirect method
	 *
	 * @param mixed $option
	 * @param mixed $code
	 * @param mixed $exit
	 * @return void
	 */
	public function redirect($url, $status = null, $exit = true) {
		return $status;
	}

	/**
	 * Conveinence method for header()
	 *
	 * @param string $status
	 * @return void
	 */
	public function header($status) {
		$this->testHeaders[] = $status;
	}

}

class AuthUser extends AppModel {

	public $name = 'User';

	public $alias = 'User';

	public $useTable = 'tools_users';

}
