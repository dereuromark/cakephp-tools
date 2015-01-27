<?php
/**
 * FallbackPasswordHasher file
 *
 */
App::uses('FallbackPasswordHasher', 'Tools.Controller/Component/Auth');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Model', 'Model');
App::uses('CakeSession', 'Model/Datasource');

if (!defined('PASSWORD_BCRYPT')) {
	require CakePlugin::path('Tools') . 'Lib/Bootstrap/Password.php';
}

/**
 * Test case for FallbackPasswordHasher
 *
 */
class FallbackPasswordHasherTest extends MyCakeTestCase {

	public $fixtures = ['plugin.tools.tools_auth_user'];

	public $Controller;

	public $request;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Controller = new TestFallbackPasswordHasherController(new CakeRequest(), new CakeResponse());
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();

		// Modern pwd account
		$this->Controller->TestFallbackPasswordHasherUser->create();
		$user = array(
			'username' => 'itisme',
			'email' => '',
			'pwd' => 'secure123456'
		);
		$res = $this->Controller->TestFallbackPasswordHasherUser->save($user);
		$this->assertTrue((bool)$res);

		// Old pwd account
		$this->Controller->TestFallbackPasswordHasherUser->create();
		$user = array(
			'username' => 'itwasme',
			'email' => '',
			'password' => Security::hash('123456', null, true)
		);
		$res = $this->Controller->TestFallbackPasswordHasherUser->save($user);
		$this->assertTrue((bool)$res);

		CakeSession::delete('Auth');

		//var_dump($this->Controller->TestFallbackPasswordHasherUser->find('all'));
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testBasics() {
		$this->Controller->request->data = array(
			'TestFallbackPasswordHasherUser' => array(
				'username' => 'itisme',
				'password' => 'xyz'
			),
		);
		$result = $this->Controller->Auth->login();
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testLogin() {
		$this->Controller->request->data = array(
			'TestFallbackPasswordHasherUser' => array(
				'username' => 'itisme',
				'password' => 'secure123456'
			),
		);
		$result = $this->Controller->Auth->login();
		$this->assertTrue($result);

		// This could be done in login() action after successfully logging in.
		$hash = $this->Controller->TestFallbackPasswordHasherUser->hash('secure123456');
		$this->assertFalse($this->Controller->TestFallbackPasswordHasherUser->needsRehash($hash));
	}

	/**
	 * @return void
	 */
	public function testLoginOld() {
		$this->Controller->request->data = array(
			'TestFallbackPasswordHasherUser' => array(
				'username' => 'itwasme',
				'password' => '123456'
			),
		);
		$result = $this->Controller->Auth->login();
		$this->assertTrue($result);

		// This could be done in login() action after successfully logging in.
		$hash = Security::hash('123456', null, true);
		$this->assertTrue($this->Controller->TestFallbackPasswordHasherUser->needsRehash($hash));
	}

}

class TestFallbackPasswordHasherController extends Controller {

	public $uses = array('Tools.TestFallbackPasswordHasherUser');

	public $components = array('Auth');

	public function beforeFilter() {
		parent::beforeFilter();

		$options = array(
			'className' => 'Tools.Fallback',
			'hashers' => array(
				'Tools.Modern', 'Simple'
				//'Tools.Modern' => array('userModel' => 'Tools.TestFallbackPasswordHasherUser'), 'Simple' => array('userModel' => 'Tools.TestFallbackPasswordHasherUser')
			)
		);
		$this->Auth->authenticate = array(
			'Form' => array(
				'passwordHasher' => $options,
				'fields' => array(
					'username' => 'username',
					'password' => 'password'
				),
				'userModel' => 'Tools.TestFallbackPasswordHasherUser'
			)
		);
	}

}

class TestFallbackPasswordHasherUser extends Model {

	public $useTable = 'tools_auth_users';

	/**
	 * TestFallbackPasswordHasherUser::beforeSave()
	 *
	 * @param array $options
	 * @return bool Success
	 */
	public function beforeSave($options = array()) {
		if (!empty($this->data[$this->alias]['pwd'])) {
			$this->data[$this->alias]['password'] = $this->hash($this->data[$this->alias]['pwd']);
		}
		return true;
	}

	/**
	 * @param string $pwd
	 * @return string Hash
	 */
	public function hash($pwd) {
		$options = array(
			'className' => 'Tools.Fallback',
			'hashers' => array(
				'Tools.Modern', 'Simple'
			)
		);
		$passwordHasher = $this->_getPasswordHasher($options);
		return $passwordHasher->hash($pwd);
	}

	/**
	 * @param string $pwd
	 * @return bool Success
	 */
	public function needsRehash($pwd) {
		$options = array(
			'className' => 'Tools.Fallback',
			'hashers' => array(
				'Tools.Modern', 'Simple'
			)
		);
		$passwordHasher = $this->_getPasswordHasher($options);
		return $passwordHasher->needsRehash($pwd);
	}

	/**
	 * PasswordableBehavior::_getPasswordHasher()
	 *
	 * @param mixed $hasher Name or options array.
	 * @return PasswordHasher
	 */
	protected function _getPasswordHasher($hasher) {
		$class = $hasher;
		$config = [];
		if (is_array($hasher)) {
			$class = $hasher['className'];
			unset($hasher['className']);
			$config = $hasher;
		}
		list($plugin, $class) = pluginSplit($class, true);
		$className = $class . 'PasswordHasher';
		App::uses($className, $plugin . 'Controller/Component/Auth');
		if (!class_exists($className)) {
			throw new CakeException(sprintf('Password hasher class "%s" was not found.', $class));
		}
		if (!is_subclass_of($className, 'AbstractPasswordHasher')) {
			throw new CakeException('Password hasher must extend AbstractPasswordHasher class.');
		}
		return new $className($config);
	}

}
