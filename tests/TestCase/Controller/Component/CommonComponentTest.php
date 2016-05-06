<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Tools\TestSuite\TestCase;

/**
 */
class CommonComponentTest extends TestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');
		Configure::write('App.fullBaseUrl', 'http://localhost');

		$this->request = new Request('/my_controller/foo');
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

		$config = $this->Controller->Test->config();
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
		$this->assertSame(302, $this->Controller->response->statusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoRedirect() {
		$is = $this->Controller->Common->autoRedirect(['action' => 'foo']);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/foo', $is['Location']);
		$this->assertSame(302, $this->Controller->response->statusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoRedirectReferer() {
		$this->request->env('HTTP_REFERER', 'http://localhost/my_controller/some-referer-action');

		$is = $this->Controller->Common->autoRedirect(['action' => 'foo'], true);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/my_controller/some-referer-action', $is['Location']);
		$this->assertSame(302, $this->Controller->response->statusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirect() {
		$is = $this->Controller->Common->autoPostRedirect(['action' => 'foo'], true);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/foo', $is['Location']);
		$this->assertSame(302, $this->Controller->response->statusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirectReferer() {
		$this->request->env('HTTP_REFERER', 'http://localhost/my_controller/allowed');

		$is = $this->Controller->Common->autoPostRedirect(['controller' => 'MyController', 'action' => 'foo'], true);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/my_controller/allowed', $is['Location']);
		$this->assertSame(302, $this->Controller->response->statusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirectRefererNotWhitelisted() {
		$this->request->env('HTTP_REFERER', 'http://localhost/my_controller/wrong');

		$is = $this->Controller->Common->autoPostRedirect(['controller' => 'MyController', 'action' => 'foo'], true);
		$is = $this->Controller->response->header();
		$this->assertSame('http://localhost/my_controller/foo', $is['Location']);
		$this->assertSame(302, $this->Controller->response->statusCode());
	}

}

/**
 * Use Controller instead of AppController to avoid conflicts
 */
class CommonComponentTestController extends Controller {

	/**
	 * @var string
	 */
	public $name = 'MyController';

	/**
	 * @var array
	 */
	public $components = ['Tools.Common'];

	/**
	 * @var array
	 */
	public $autoRedirectActions = ['allowed'];

}
