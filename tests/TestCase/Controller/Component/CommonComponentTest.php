<?php
namespace Tools\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Shim\Controller\Component\Component;
use Cake\Controller\Component\CommonComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Routing\DispatcherFactory;
use Tools\TestSuite\TestCase;

/**
 */
class CommonComponentTest extends TestCase {

	//public $fixtures = array('core.sessions', 'plugin.tools.tools_users', 'plugin.tools.roles');

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Controller = new CommonComponentTestController(new Request('/test'));
		$this->Controller->startupProcess();
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Controller->Common);
		unset($this->Controller);
	}

	/**
	 * CommonComponentTest::testLoadComponent()
	 *
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
		$this->Controller->Common->loadComponent('Shim.Session',['foo' => 'baz']);
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
	 * CommonComponentTest::testGetParams()
	 *
	 * @return void
	 */
	public function testGetParams() {
		$is = $this->Controller->Common->getPassedParam('x');
		$this->assertNull($is);

		$is = $this->Controller->Common->getPassedParam('x', 'y');
		$this->assertSame('y', $is);
	}

	/**
	 * CommonComponentTest::testGetDefaultUrlParams()
	 *
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
	 * CommonComponentTest::testIsForeignReferer()
	 *
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
	 * CommonComponentTest::testAutoRedirect()
	 *
	 * @return void
	 */
	public function testPostRedirect() {
		$is = $this->Controller->Common->postRedirect(array('action' => 'foo'));
		$is = $this->Controller->response->header();
		$this->assertSame('/foo', $is['Location']);
		$this->assertSame(302, $this->Controller->response->statusCode());
	}

	/**
	 * CommonComponentTest::testAutoRedirect()
	 *
	 * @return void
	 */
	public function testAutoRedirect() {
		$is = $this->Controller->Common->autoRedirect(array('action' => 'foo'));
		$is = $this->Controller->response->header();
		$this->assertSame('/foo', $is['Location']);
		$this->assertSame(200, $this->Controller->response->statusCode());
	}

	/**
	 * CommonComponentTest::testAutoRedirect()
	 *
	 * @return void
	 */
	public function testAutoRedirectReferer() {
		$is = $this->Controller->Common->autoRedirect(array('action' => 'foo'), true);
		$is = $this->Controller->response->header();
		$this->assertSame('/foo', $is['Location']);
		$this->assertSame(200, $this->Controller->response->statusCode());
	}

}

/**
 * Use Controller instead of AppController to avoid conflicts
 */
class CommonComponentTestController extends Controller {

	public $components = ['Tools.Common'];

}
