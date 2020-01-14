<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Shim\TestSuite\TestCase;
use TestApp\Controller\UrlComponentTestController;

class UrlComponentTest extends TestCase {

	/**
	 * @var \Cake\Event\Event
	 */
	protected $event;

	/**
	 * @var \TestApp\Controller\UrlComponentTestController
	 */
	protected $Controller;

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
			'plugin' => false,
		];
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testBuild() {
		$is = $this->Controller->Url->build(['controller' => 'MyController', 'action' => 'x']);
		$expected = '/my-controller/x';
		$this->assertSame($expected, $is);

		$is = $this->Controller->Url->build(['controller' => 'MyController', 'action' => 'x'], ['fullBase' => true]);
		$expected = 'http://localhost/my-controller/x';
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testResetArray() {
		$result = $this->Controller->Url->resetArray(['controller' => 'FooBar', 'action' => 'test']);
		$expected = [
			'controller' => 'FooBar',
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

		$result = $this->Controller->Url->completeArray(['controller' => 'FooBar', 'action' => 'test']);
		$expected = [
			'controller' => 'FooBar',
			'action' => 'test',
			'?' => ['x' => 'y'],
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildReset() {
		$result = $this->Controller->Url->buildReset(['controller' => 'FooBar', 'action' => 'test']);
		$expected = '/foo-bar/test';
		$this->assertSame($expected, $result);

		$request = $this->Controller->getRequest();
		$request = $request->withAttribute('here', '/admin/foo/bar/baz/test')
			->withParam('prefix', 'Admin')
			->withParam('plugin', 'Foo');
		$this->Controller->setRequest($request);

		Router::reload();
		Router::defaultRouteClass(DashedRoute::class);
		Router::connect('/:controller/:action/*');
		Router::plugin('Foo', function (RouteBuilder $routes) {
			$routes->fallbacks(DashedRoute::class);
		});
		Router::prefix('Admin', function (RouteBuilder $routes) {
			$routes->plugin('Foo', function (RouteBuilder $routes) {
				$routes->fallbacks(DashedRoute::class);
			});
		});
		Router::setRequest($this->Controller->getRequest());

		$result = $this->Controller->Url->build(['controller' => 'Bar', 'action' => 'baz', 'x']);
		$expected = '/admin/foo/bar/baz/x';
		$this->assertSame($expected, $result);

		$result = $this->Controller->Url->buildReset(['controller' => 'Bar', 'action' => 'baz', 'x']);
		$expected = '/bar/baz/x';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildComplete() {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['x' => 'y']));

		$result = $this->Controller->Url->buildComplete(['controller' => 'MyController', 'action' => 'test']);
		$expected = '/my-controller/test?x=y';
		$this->assertSame($expected, $result);

		$result = $this->Controller->Url->buildComplete(['controller' => 'MyController', 'action' => 'test', '?' => ['a' => 'b']]);
		$expected = '/my-controller/test?a=b&x=y';
		$this->assertSame($expected, $result);

		$expected = '/my-controller/test?a=b&amp;x=y';
		$this->assertSame($expected, h($result));
	}

}
