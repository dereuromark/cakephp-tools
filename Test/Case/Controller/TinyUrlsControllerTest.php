<?php

App::uses('TinyUrlsController', 'Tools.Controller');

class TinyUrlsControllerTest extends ControllerTestCase {

	public $fixtures = array('core.cake_session', 'plugin.tools.user', 'plugin.tools.role', 'plugin.tools.tiny_url');

	public $TinyUrlsController;

	public function setUp() {
		parent::setUp();

		$this->TinyUrlsController = new TestTinyUrlsController(new CakeRequest, new CakeResponse);
	}

	public function tearDown() {
		CakeSession::delete('Auth.User');

		parent::tearDown();
	}

	/**
	 * TinyUrlsControllerTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->TinyUrlsController));
		$this->assertInstanceOf('TinyUrlsController', $this->TinyUrlsController);
	}

	/**
	 * QloginControllerTest::testGo()
	 *
	 * @return void
	 */
	public function testGo() {
		$this->TinyUrl = ClassRegistry::init('Tools.TinyUrl');

		$url = $this->TinyUrl->url(array('controller' => 'test', 'action' => 'foo', 'bar'), 1);
		$this->assertContains('/tools/tiny_urls/go?id=m', $url);

		$key = 'm';
		$this->TinyUrlsController->request['id'] = 'm';
		$this->TinyUrlsController->go();
		$this->assertTextContains('/test/foo/bar', $this->TinyUrlsController->redirectUrl);

		// Invalid id
		$this->expectException('NotFoundException');
		$key = 'm';
		$this->TinyUrlsController->request['id'] = 's';
		$this->TinyUrlsController->go();
	}

	/**
	 * QloginControllerTest::testAdminIndex()
	 *
	 * @return void
	 */
	public function testAdminIndex() {
		$user = array(
			'id' => 1,
			'role_id' => 1
		);
		CakeSession::write('Auth.User', $user);

		$url = Router::url(array('admin' => true, 'plugin' => 'tools', 'controller' => 'tiny_urls', 'action' => 'index'));
		$result = $this->testAction($url, array(
			'method' => 'get',
			'return' => 'contents'
		));
		$this->assertNotEmpty($result);
	}

	/**
	 * QloginControllerTest::testAdminIndex()
	 *
	 * @return void
	 */
	public function testAdminReset() {
		$user = array(
			'id' => 1,
			'role_id' => 1
		);
		CakeSession::write('Auth.User', $user);

		$url = Router::url(array('admin' => true, 'plugin' => 'tools', 'controller' => 'tiny_urls', 'action' => 'reset'));
		$result = $this->testAction($url, array(
			'return' => 'contents'
		));
		$this->assertNull($result);
		$this->assertTextContains('admin/tools/tiny_urls', $this->headers['Location']);
	}

}

class TestTinyUrlsController extends TinyUrlsController {

	public $redirectUrl = null;

	public function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}

}
