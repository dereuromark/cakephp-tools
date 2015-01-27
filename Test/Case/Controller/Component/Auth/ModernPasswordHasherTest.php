<?php
/**
 * ModernPasswordHasher file
 *
 */
App::uses('ModernPasswordHasher', 'Tools.Controller/Component/Auth');
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
 * Test case for ModernPasswordHasher
 *
 */
class ModernPasswordHasherTest extends MyCakeTestCase {

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

		$this->Controller = new TestModernPasswordHasherController(new CakeRequest(), new CakeResponse());
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();

		// Modern pwd account
		$this->Controller->TestModernPasswordHasherUser->create();
		$user = array(
			'username' => 'itisme',
			'email' => '',
			'pwd' => 'secure123456'
		);
		$res = $this->Controller->TestModernPasswordHasherUser->save($user);
		$this->assertTrue((bool)$res);

		CakeSession::delete('Auth');
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testBasics() {
		$this->Controller->request->data = array(
			'TestModernPasswordHasherUser' => array(
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
			'TestModernPasswordHasherUser' => array(
				'username' => 'itisme',
				'password' => 'secure123456'
			),
		);
		$result = $this->Controller->Auth->login();
		$this->assertTrue($result);

		// This could be done in login() action after successfully logging in.
		$hash = $this->Controller->TestModernPasswordHasherUser->hash('secure123456');
		$this->assertFalse($this->Controller->TestModernPasswordHasherUser->needsRehash($hash));
	}

}

class TestModernPasswordHasherController extends Controller {

	public $uses = array('Tools.TestModernPasswordHasherUser');

	public $components = array('Auth');

	/**
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();

		$this->Auth->authenticate = array(
			'Form' => array(
				'passwordHasher' => 'Tools.Modern',
				'fields' => array(
					'username' => 'username',
					'password' => 'password'
				),
				'userModel' => 'Tools.TestModernPasswordHasherUser'
			)
		);
	}

}

class TestModernPasswordHasherUser extends Model {

	public $useTable = 'tools_auth_users';

	/**
	 * TestModernPasswordHasherUser::beforeSave()
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
		$passwordHasher = $this->_getPasswordHasher('Tools.Modern');
		return $passwordHasher->hash($pwd);
	}

	/**
	 * @param string $pwd
	 * @return bool Success
	 */
	public function needsRehash($pwd) {
		$passwordHasher = $this->_getPasswordHasher('Tools.Modern');
		return $passwordHasher->needsRehash($pwd);
	}

	/**
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
