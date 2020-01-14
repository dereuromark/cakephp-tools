<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\View\Helper\UrlHelper;

/**
 * Datetime Test Case
 */
class UrlHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\UrlHelper
	 */
	protected $Url;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Router::reload();
		Router::connect('/:controller', ['action' => 'index']);
		Router::connect('/:controller/:action/*');

		$this->Url = new UrlHelper(new View(null));
		$this->Url->getView()->setRequest(new ServerRequest(['webroot' => '']));
	}

	/**
	 * @return void
	 */
	public function testResetArray() {
		$result = $this->Url->resetArray(['action' => 'fooBar']);
		$expected = [
			'prefix' => false,
			'plugin' => false,
			'action' => 'fooBar',
		];
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testCompleteArray() {
		$result = $this->Url->completeArray(['action' => 'fooBar']);
		$expected = [
			'action' => 'fooBar',
			'?' => [],
		];
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildReset() {
		$result = $this->Url->buildReset(['controller' => 'FooBar', 'action' => 'test']);
		$expected = '/foo-bar/test';
		$this->assertSame($expected, $result);

		$request = $this->Url->getView()->getRequest();
		$request = $request->withAttribute('here', '/admin/foo-bar/test')
			->withParam('prefix', 'Admin');
		$this->Url->getView()->setRequest($request);

		Router::prefix('Admin', function (RouteBuilder $routes) {
			$routes->fallbacks();
		});
		Router::setRequest($this->Url->getView()->getRequest());

		$result = $this->Url->build(['prefix' => 'Admin', 'controller' => 'FooBar', 'action' => 'test']);
		$expected = '/admin/foo-bar/test';
		$this->assertSame($expected, $result);

		$result = $this->Url->build(['controller' => 'FooBar', 'action' => 'test']);
		$expected = '/admin/foo-bar/test';
		$this->assertSame($expected, $result);

		$result = $this->Url->buildReset(['controller' => 'FooBar', 'action' => 'test']);
		$expected = '/foo-bar/test';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildResetWithPlugin() {
		$result = $this->Url->buildReset(['controller' => 'FooBar', 'action' => 'test']);
		$expected = '/foo-bar/test';
		$this->assertSame($expected, $result);

		$request = $this->Url->getView()->getRequest();
		$request = $request->withAttribute('here', '/admin/foo/bar/baz/test')
			->withParam('prefix', 'Admin')
			->withParam('plugin', 'Foo');
		$this->Url->getView()->setRequest($request);
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
		Router::setRequest($this->Url->getView()->getRequest());

		$result = $this->Url->build(['controller' => 'Bar', 'action' => 'baz', 'x']);
		$expected = '/admin/foo/bar/baz/x';
		$this->assertSame($expected, $result);

		$result = $this->Url->buildReset(['controller' => 'Bar', 'action' => 'baz', 'x']);
		$expected = '/bar/baz/x';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildComplete() {
		$this->Url->getView()->setRequest($this->Url->getView()->getRequest()->withQueryParams(['x' => 'y']));

		$result = $this->Url->buildComplete(['controller' => 'FooBar', 'action' => 'test']);
		$expected = '/foo-bar/test?x=y';
		$this->assertSame($expected, $result);

		$result = $this->Url->buildComplete(['controller' => 'FooBar', 'action' => 'test', '?' => ['a' => 'b']]);
		$expected = '/foo-bar/test?a=b&amp;x=y';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Url);
	}

}
