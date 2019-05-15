<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\View\View;
use Tools\TestSuite\TestCase;
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
	public function testBuildReset() {
		Router::connect('/:controller/:action/*');

		$result = $this->Url->buildReset(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/foobar/test';
		$this->assertSame($expected, $result);

		$request = $this->Url->getView()->getRequest();
		$request = $request->withAttribute('here', '/admin/foobar/test')
			->withParam('prefix', 'admin');
		$this->Url->getView()->setRequest($request);
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::prefix('admin', function (RouteBuilder $routes) {
			$routes->fallbacks();
		});
		Router::pushRequest($this->Url->getView()->getRequest());

		$result = $this->Url->build(['prefix' => 'admin', 'controller' => 'foobar', 'action' => 'test']);
		$expected = '/admin/foobar/test';
		$this->assertSame($expected, $result);

		$result = $this->Url->build(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/admin/foobar/test';
		$this->assertSame($expected, $result);

		$result = $this->Url->buildReset(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/foobar/test';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildResetWithPlugin() {
		Router::connect('/:controller/:action/*');

		$result = $this->Url->buildReset(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/foobar/test';
		$this->assertSame($expected, $result);

		$request = $this->Url->getView()->getRequest();
		$request = $request->withAttribute('here', '/admin/foo/bar/baz/test')
			->withParam('prefix', 'admin')
			->withParam('plugin', 'Foo');
		$this->Url->getView()->setRequest($request);
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
		Router::pushRequest($this->Url->getView()->getRequest());

		$result = $this->Url->build(['controller' => 'bar', 'action' => 'baz', 'x']);
		$expected = '/admin/foo/bar/baz/x';
		$this->assertSame($expected, $result);

		$result = $this->Url->buildReset(['controller' => 'bar', 'action' => 'baz', 'x']);
		$expected = '/bar/baz/x';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildComplete() {
		$this->Url->getView()->setRequest($this->Url->getView()->getRequest()->withQueryParams(['x' => 'y']));

		$result = $this->Url->buildComplete(['action' => 'test']);
		$expected = '/test?x=y';
		$this->assertSame($expected, $result);

		$result = $this->Url->buildComplete(['action' => 'test', '?' => ['a' => 'b']]);
		$expected = '/test?a=b&amp;x=y';
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
