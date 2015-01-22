<?php
/**
 * DirectAuthenticateTest file
 *
 */

App::uses('AuthComponent', 'Controller/Component');
App::uses('DirectAuthenticate', 'Tools.Controller/Component/Auth');
App::uses('AppModel', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * Test case for DirectAuthentication
 *
 */
class DirectAuthenticateTest extends CakeTestCase {

	public $fixtures = ['core.user', 'core.auth_user'];

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->Collection = $this->getMock('ComponentCollection');
		$this->auth = new DirectAuthenticate($this->Collection, [
			'fields' => ['username' => 'user'],
			'userModel' => 'User'
		]);
		$User = ClassRegistry::init('User');
		$User->belongsTo = [];
		$this->response = $this->getMock('CakeResponse');
	}

	/**
	 * Test applying settings in the constructor
	 *
	 * @return void
	 */
	public function testConstructor() {
		$object = new DirectAuthenticate($this->Collection, [
			'userModel' => 'AuthUser',
			'fields' => ['username' => 'user']
		]);
		$this->assertEquals('AuthUser', $object->settings['userModel']);
		$this->assertEquals(['username' => 'user', 'password' => 'password'], $object->settings['fields']);
	}

	/**
	 * Test the authenticate method
	 *
	 * @return void
	 */
	public function testAuthenticateNoData() {
		$request = new CakeRequest('posts/index', false);
		$request->data = [];
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

	/**
	 * Test the authenticate method
	 *
	 * @return void
	 */
	public function testAuthenticateNoUsername() {
		$request = new CakeRequest('posts/index', false);
		$request->data = ['User' => ['x' => 'foobar']];
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

	/**
	 * Test authenticate password is false method
	 *
	 * @return void
	 */
	public function testAuthenticateUsernameDoesNotExist() {
		$request = new CakeRequest('posts/index', false);
		$request->data = [
			'User' => [
				'user' => 'foo',
		]];
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

	/**
	 * Test the authenticate method
	 *
	 * @return void
	 */
	public function testAuthenticateInjection() {
		$request = new CakeRequest('posts/index', false);
		$request->data = [
			'User' => [
				'user' => "> 1 ' OR 1 = 1",
		]];
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

	/**
	 * Test authenticate success
	 *
	 * @return void
	 */
	public function testAuthenticateSuccess() {
		$request = new CakeRequest('posts/index', false);
		$request->data = ['User' => [
			'user' => 'mariano',
		]];
		$result = $this->auth->authenticate($request, $this->response);
		//debug($result);
		$expected = [
			'id' => 1,
			'user' => 'mariano',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		];
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test scope failure.
	 *
	 * @return void
	 */
	public function testAuthenticateScopeFail() {
		$this->auth->settings['scope'] = ['user' => 'nate'];
		$request = new CakeRequest('posts/index', false);
		$request->data = ['User' => [
			'user' => 'mariano',
		]];

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

	/**
	 * Test a model in a plugin.
	 *
	 * @return void
	 */
	public function testPluginModel() {
		Cache::delete('object_map', '_cake_core_');
		App::build([
			'Plugin' => [CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS],
		], App::RESET);
		CakePlugin::load('TestPlugin');

		$PluginModel = ClassRegistry::init('TestPlugin.TestPluginAuthUser');
		$user['id'] = 1;
		$user['username'] = 'gwoo';
		$PluginModel->save($user, ['validate' => false]);

		$this->auth->settings['userModel'] = 'TestPlugin.TestPluginAuthUser';
		$this->auth->settings['fields']['username'] = 'username';

		$request = new CakeRequest('posts/index', false);
		$request->data = ['TestPluginAuthUser' => [
			'username' => 'gwoo',

		]];

		$result = $this->auth->authenticate($request, $this->response);
		$expected = [
			'id' => 1,
			'username' => 'gwoo',
			'created' => '2007-03-17 01:16:23'
		];
		$this->assertEquals(static::date(), $result['updated']);
		unset($result['updated']);
		$this->assertEquals($expected, $result);
		CakePlugin::unload();
	}

}
