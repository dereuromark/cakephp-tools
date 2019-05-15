<?php

namespace Tools\Test\TestCase\Controller\Component;

use App\Controller\UrlComponentTestController;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Tools\TestSuite\TestCase;

/**
 */
class UrlComponentTest extends TestCase {

	/**
	 * @var \Cake\Event\Event
	 */
	public $event;

	/**
	 * @var \App\Controller\UrlComponentTestController
	 */
	public $Controller;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->event = new Event('Controller.beforeFilter');
		$this->Controller = new UrlComponentTestController(new ServerRequest());

		Router::reload();
		Router::connect('/:controller', ['action' => 'index']);
		Router::connect('/:controller/:action/*');

		Configure::write('App.fullBaseUrl', 'http://localhost');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Controller);
	}

	/**
	 * @return void
	 */
	public function testDefaults() {
		$is = $this->Controller->Url->defaults();
		$expected = [
			'prefix' => false,
			'plugin' => false
		];
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testBuild() {
		$is = $this->Controller->Url->build(['action' => 'x']);
		$expected = '/x';
		$this->assertSame($expected, $is);

		$is = $this->Controller->Url->build(['action' => 'x'], ['fullBase' => true]);
		$expected = 'http://localhost/x';
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testResetArray() {
		$result = $this->Controller->Url->resetArray(['controller' => 'foobar', 'action' => 'test']);
		$expected = [
			'controller' => 'foobar',
			'action' => 'test',
			'prefix' => false,
			'plugin' => false,
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testCompleteArray() {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['x' => 'y']));

		$result = $this->Controller->Url->completeArray(['controller' => 'foobar', 'action' => 'test']);
		$expected = [
			'controller' => 'foobar',
			'action' => 'test',
			'?' => ['x' => 'y']
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildReset() {
		Router::connect('/:controller/:action/*');

		$result = $this->Controller->Url->buildReset(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/foobar/test';
		$this->assertSame($expected, $result);

		$request = $this->Controller->getRequest();
		$request = $request->withAttribute('here', '/admin/foo/bar/baz/test')
			->withParam('prefix', 'admin')
			->withParam('plugin', 'Foo');
		$this->Controller->setRequest($request);
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::plugin('Foo', function (RouteBuilder $routes) {
			$routes->fallbacks();
		});
		Router::prefix('admin', function (RouteBuilder $routes) {
			$routes->plugin('Foo', function (RouteBuilder $routes) {
				$routes->fallbacks();
			});
		});
		Router::pushRequest($this->Controller->getRequest());

		$result = $this->Controller->Url->build(['controller' => 'bar', 'action' => 'baz', 'x']);
		$expected = '/admin/foo/bar/baz/x';
		$this->assertSame($expected, $result);

		$result = $this->Controller->Url->buildReset(['controller' => 'bar', 'action' => 'baz', 'x']);
		$expected = '/bar/baz/x';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildComplete() {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['x' => 'y']));

		$result = $this->Controller->Url->buildComplete(['action' => 'test']);
		$expected = '/test?x=y';
		$this->assertSame($expected, $result);

		$result = $this->Controller->Url->buildComplete(['action' => 'test', '?' => ['a' => 'b']]);
		$expected = '/test?a=b&x=y';
		$this->assertSame($expected, $result);

		$expected = '/test?a=b&amp;x=y';
		$this->assertSame($expected, h($result));
	}

}
