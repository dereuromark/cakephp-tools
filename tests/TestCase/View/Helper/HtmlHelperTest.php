<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\View\Helper\HtmlHelper;

/**
 * Datetime Test Case
 */
class HtmlHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\HtmlHelper
	 */
	protected $Html;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Router::reload();
		Router::connect('/:controller', ['action' => 'index']);
		Router::connect('/:controller/:action/*');

		$this->Html = new HtmlHelper(new View(null));
		$this->Html->getView()->setRequest(new ServerRequest(['webroot' => '']));
	}

	/**
	 * HtmlHelperTest::testImageFromBlob()
	 *
	 * @return void
	 */
	public function testImageFromBlob() {
		$folder = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS;
		$content = file_get_contents($folder . 'hotel.png');
		$is = $this->Html->imageFromBlob($content);
		$this->assertTrue(!empty($is));
	}

	/**
	 * Tests
	 *
	 * @return void
	 */
	public function testLinkReset() {
		$result = $this->Html->linkReset('Foo', ['controller' => 'FooBar', 'action' => 'test']);
		$expected = '<a href="/foo-bar/test">Foo</a>';
		$this->assertEquals($expected, $result);

		$request = $this->Html->getView()->getRequest();
		$request = $request->withAttribute('here', '/admin/foo-bar/test')
			->withParam('prefix', 'Admin');
		$this->Html->getView()->setRequest($request);

		Router::prefix('Admin', function (RouteBuilder $routes) {
			$routes->fallbacks(DashedRoute::class);
		});
		Router::setRequest($request);

		$result = $this->Html->link('Foo', ['prefix' => 'Admin', 'controller' => 'FooBar', 'action' => 'test']);
		$expected = '<a href="/admin/foo-bar/test">Foo</a>';
		$this->assertEquals($expected, $result);

		$result = $this->Html->link('Foo', ['controller' => 'FooBar', 'action' => 'test']);
		$expected = '<a href="/admin/foo-bar/test">Foo</a>';
		$this->assertEquals($expected, $result);

		$result = $this->Html->linkReset('Foo', ['controller' => 'FooBar', 'action' => 'test']);
		$expected = '<a href="/foo-bar/test">Foo</a>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * Tests
	 *
	 * @return void
	 */
	public function testLinkComplete() {
		$this->Html->getView()->setRequest($this->Html->getView()->getRequest()->withQueryParams(['x' => 'y']));

		$result = $this->Html->linkComplete('Foo', ['controller' => 'FooBar', 'action' => 'test']);
		$expected = '<a href="/foo-bar/test?x=y">Foo</a>';
		$this->assertEquals($expected, $result);

		$result = $this->Html->linkComplete('Foo', ['controller' => 'FooBar', 'action' => 'test', '?' => ['a' => 'b']]);
		$expected = '<a href="/foo-bar/test?a=b&amp;x=y">Foo</a>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Html);
	}

}
