<?php

App::uses('QloginController', 'Tools.Controller');
App::uses('ComponentCollection', 'Controller');

Configure::write('Routing.prefixes', array('admin'));

class QloginControllerTest extends ControllerTestCase {

	public $fixtures = ['plugin.tools.code_key', 'plugin.tools.token', 'core.cake_session', 'plugin.tools.user', 'plugin.tools.role'];

	public $QloginController;

	public function setUp() {
		parent::setUp();

		$this->QloginController = new TestQloginController(new CakeRequest(), new CakeResponse());
		$this->QloginController->constructClasses();
		$this->QloginController->startupProcess();

		$Auth = $this->getMock('AuthComponent', ['login'], [new ComponentCollection()]);
		$Auth->expects($this->any())
			->method('login')
			->will($this->returnValue(true));
		$this->QloginController->Auth = $Auth;
	}

	public function tearDown() {
		CakeSession::delete('Auth.User');

		parent::tearDown();
	}

	/**
	 * QloginControllerTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->QloginController));
		$this->assertInstanceOf('QloginController', $this->QloginController);
	}

	/**
	 * QloginControllerTest::testGo()
	 *
	 * @return void
	 */
	public function testGo() {
		$this->Qlogin = ClassRegistry::init('Tools.Qlogin');

		$key = $this->Qlogin->generate(['controller' => 'test', 'action' => 'foo', 'bar'], 1);
		$res = $this->Qlogin->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$this->QloginController->go($key);
		$this->assertContains('/test/foo/bar', $this->QloginController->redirectUrl);
	}

	/**
	 * QloginControllerTest::_testGoDeprecated()
	 *
	 * @return void
	 */
	public function _testGoDeprecated() {
		Configure::write('Qlogin.generator', 'CodeKey');
		$this->Qlogin = ClassRegistry::init('Tools.Qlogin');

		$key = $this->Qlogin->generate(['controller' => 'test', 'action' => 'foo', 'bar'], 1);
		$res = $this->Qlogin->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$this->QloginController->go($key);
		debug($this->QloginController->redirectUrl);
	}

	/**
	 * QloginControllerTest::testAdminIndex()
	 *
	 * @return void
	 */
	public function testAdminIndex() {
		$this->skipIf(true, 'FIXME');

		$user = [
			'id' => 1,
			'role_id' => 1
		];
		CakeSession::write('Auth.User', $user);

		$url = Router::url(['admin' => true, 'plugin' => 'tools', 'controller' => 'qlogin', 'action' => 'index']);
		$result = $this->testAction($url, [
			'method' => 'get',
			'return' => 'contents'
		]);
		$this->assertNotEmpty($result);
	}

	/**
	 * QloginControllerTest::testAdminIndex()
	 *
	 * @return void
	 */
	public function testAdminReset() {
		$user = [
			'id' => 1,
			'role_id' => 1
		];
		CakeSession::write('Auth.User', $user);

		$url = Router::url(['admin' => true, 'plugin' => 'tools', 'controller' => 'qlogin', 'action' => 'reset']);
		$result = $this->testAction($url, [
			'return' => 'contents'
		]);
		$this->assertNull($result);
		$this->assertTextContains('admin/tools/qlogin', $this->headers['Location']);
	}

}

class TestQloginController extends QloginController {

	public $uses = ['Tools.Qlogin'];

	public $redirectUrl = null;

	public function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}

}
