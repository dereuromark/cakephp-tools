<?php

namespace Tools\Test\TestCase\Auth;

use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Tools\Auth\MultiColumnAuthenticate;

class MultiColumnAuthenticateTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Tools.MultiColumnUsers',
	];

	/**
	 * @var \Tools\Auth\MultiColumnAuthenticate
	 */
	protected $auth;

	/**
	 * @var \Cake\Http\Response
	 */
	protected $response;

	/**
	 * @var \Cake\Controller\ComponentRegistry
	 */
	protected $registry;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->registry = $this->getMockBuilder('Cake\Controller\ComponentRegistry')->getMock();
		$this->auth = new MultiColumnAuthenticate($this->registry, [
			'fields' => ['username' => 'user_name', 'password' => 'password'],
			'userModel' => 'MultiColumnUsers',
			'columns' => ['user_name', 'email'],
		]);

		$password = password_hash('password', PASSWORD_DEFAULT);
		$MultiColumnUsers = TableRegistry::getTableLocator()->get('MultiColumnUsers');
		$MultiColumnUsers->updateAll(['password' => $password], []);

		$this->response = $this->getMockBuilder('Cake\Http\Response')->getMock();
	}

	/**
	 * @return void
	 */
	public function testAuthenticateEmailOrUsername() {
		$request = new ServerRequest(['url' => 'posts/index']);
		$expected = [
			'id' => 1,
			'user_name' => 'mariano',
			'email' => 'mariano@example.com',
			'token' => '12345',
			'created' => new Time('2007-03-17 01:16:23'),
			'updated' => new Time('2007-03-17 01:18:31'),
		];

		$request = $request->withData('user_name', 'mariano')->withData('password', 'password');
		$result = $this->auth->authenticate($request, $this->response);
		$this->assertEquals($expected, $result);

		$request = $request->withData('user_name', 'mariano@example.com')->withData('password', 'password');
		$result = $this->auth->authenticate($request, $this->response);
		$this->assertEquals($expected, $result);
	}
	/**
	 * @return void
	 */
	public function testAuthenticateNoUsername() {
		$request = new ServerRequest(['url' => 'posts/index']);
		$request = $request->withData('password', 'foobar');
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

	/**
	 * @return void
	 */
	public function testAuthenticateNoPassword() {
		$request = new ServerRequest(['url' => 'posts/index']);
		$request = $request->withData('user_name', 'mariano');
		$this->assertFalse($this->auth->authenticate($request, $this->response));

		$request = $request->withData('user_name', 'mariano@example.com');
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

	/**
	 * @return void
	 */
	public function testAuthenticateInjection() {
		$request = new ServerRequest(['url' => 'posts/index']);
		$request = $request->withData('user_name', '> 1')->withData('password', "' OR 1 = 1");
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

	/**
	 * test scope failure.
	 *
	 * @return void
	 */
	public function testAuthenticateScopeFail() {
		$this->auth->setConfig('scope', ['user_name' => 'nate']);
		$request = new ServerRequest(['url' => 'posts/index']);
		$request = $request->withData('user_name', 'mariano')->withData('password', 'password');

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

}
