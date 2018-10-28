<?php

namespace Tools\Test\TestCase\Controller\Component;

use App\Controller\CommonComponentTestController;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Tools\TestSuite\TestCase;

/**
 */
class CommonComponentTest extends TestCase {

	/**
	 * @var \App\Controller\CommonComponentTestController
	 */
	public $Controller;

	/**
	 * @var \Cake\Http\ServerRequest
	 */
	public $request;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.fullBaseUrl', 'http://localhost');

		$this->request = new ServerRequest('/my_controller/foo');
		$this->request->params['controller'] = 'MyController';
		$this->request->params['action'] = 'foo';
		$this->Controller = new CommonComponentTestController($this->request);
		$this->Controller->startupProcess();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Controller->Common);
		unset($this->Controller);
	}

	/**
	 * @return void
	 */
	public function testLoadComponent() {
		$this->assertTrue(!isset($this->Controller->Apple));
		$this->Controller->Common->loadComponent('Apple');
		$this->assertTrue(isset($this->Controller->Apple));

		// with plugin
		$this->Controller->Session = null;
		$this->assertTrue(!isset($this->Controller->Session));
		$this->Controller->Common->loadComponent('Shim.Session', ['foo' => 'bar']);
		$this->Controller->components()->unload('Session');
		$this->Controller->Common->loadComponent('Shim.Session', ['foo' => 'baz']);
		$this->assertTrue(isset($this->Controller->Session));

		// with options
		$this->Controller->Test = null;
		$this->assertTrue(!isset($this->Controller->Test));
		$this->Controller->Common->loadComponent('Test', ['x' => 'z'], false);
		$this->assertTrue(isset($this->Controller->Test));
		$this->assertFalse($this->Controller->Test->isInit);
		$this->assertFalse($this->Controller->Test->isStartup);

		// with options
		$this->Controller->components()->unload('Test');
		$this->Controller->Test = null;
		$this->assertTrue(!isset($this->Controller->Test));
		$this->Controller->Common->loadComponent('Test', ['x' => 'y']);
		$this->assertTrue(isset($this->Controller->Test));
		$this->assertTrue($this->Controller->Test->isInit);
		$this->assertTrue($this->Controller->Test->isStartup);

		$config = $this->Controller->Test->getConfig();
		$this->assertEquals(['x' => 'y'], $config);
	}

	/**
	 * @return void
	 */
	public function testGetParams() {
		$is = $this->Controller->Common->getPassedParam('x');
		$this->assertNull($is);

		$is = $this->Controller->Common->getPassedParam('x', 'y');
		$this->assertSame('y', $is);
	}

	/**
	 * @return void
	 */
	public function testGetDefaultUrlParams() {
		$is = $this->Controller->Common->defaultUrlParams();
		$this->assertNotEmpty($is);
	}

	/**
	 * CommonComponentTest::testcurrentUrl()
	 *
	 * @return void
	 */
	public function testCurrentUrl() {
		$is = $this->Controller->Common->currentUrl();
		$this->assertTrue(is_array($is) && !empty($is));

		$is = $this->Controller->Common->currentUrl(true);
		$this->assertTrue(!is_array($is) && !empty($is));
	}

	/**
	 * @return void
	 */
	public function testIsForeignReferer() {
		$ref = 'http://www.spiegel.de';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertTrue($is);

		$ref = Configure::read('App.fullBaseUrl') . '/some/controller/action';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertFalse($is);

		$ref = '';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertFalse($is);
	}

	/**
	 * @return void
	 */
	public function testPostRedirect() {
		$is = $this->Controller->Common->postRedirect(['action' => 'foo']);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/foo', $is['Location']);
		$this->assertSame(302, $this->Controller->response->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoRedirect() {
		$is = $this->Controller->Common->autoRedirect(['action' => 'foo']);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/foo', $is['Location']);
		$this->assertSame(302, $this->Controller->response->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoRedirectReferer() {
		$url = 'http://localhost/my_controller/some-referer-action';
		$this->Controller->setRequest($this->request->withEnv('HTTP_REFERER', $url));

		$this->Controller->Common->autoRedirect(['action' => 'foo'], true);
		$headers = $this->Controller->response->getHeaders();
		$this->assertSame([$url], $headers['Location']);
		$this->assertSame(302, $this->Controller->response->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirect() {
		$this->Controller->Common->autoPostRedirect(['action' => 'foo'], true);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/foo', $is['Location']);
		$this->assertSame(302, $this->Controller->response->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirectReferer() {
		$url = 'http://localhost/my_controller/allowed';
		$this->Controller->setRequest($this->request->withEnv('HTTP_REFERER', $url));

		$this->Controller->Common->autoPostRedirect(['controller' => 'MyController', 'action' => 'foo'], true);
		$headers = $this->Controller->response->getHeaders();
		$this->assertSame([$url], $headers['Location']);
		$this->assertSame(302, $this->Controller->response->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testListActions() {
		$actions = $this->Controller->Common->listActions();
		$this->assertSame([], $actions);
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirectRefererNotWhitelisted() {
		$this->request->env('HTTP_REFERER', 'http://localhost/my_controller/wrong');

		$is = $this->Controller->Common->autoPostRedirect(['controller' => 'MyController', 'action' => 'foo'], true);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/my_controller/foo', $is['Location']);
		$this->assertSame(302, $this->Controller->response->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testGetSafeRedirectUrl() {
		$result = $this->Controller->Common->getSafeRedirectUrl(['action' => 'default']);
		$this->assertSame(['action' => 'default'], $result);

		$this->request = $this->request->withQueryParams(['redirect' => '/foo/bar']);
		$this->Controller->setRequest($this->request);

		$result = $this->Controller->Common->getSafeRedirectUrl(['action' => 'default']);
		$this->assertSame('/foo/bar', $result);

		$this->request = $this->request->withQueryParams(['redirect' => 'https://dangerous.url/foo/bar']);
		$this->Controller->setRequest($this->request);

		$result = $this->Controller->Common->getSafeRedirectUrl(['action' => 'default']);
		$this->assertSame(['action' => 'default'], $result);
	}

}
