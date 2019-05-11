<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\View\View;
use Tools\TestSuite\TestCase;
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
		Router::connect('/:controller/:action/*');

		$result = $this->Html->linkReset('Foo', ['controller' => 'foobar', 'action' => 'test']);
		$expected = '<a href="/foobar/test">Foo</a>';
		$this->assertEquals($expected, $result);

		$request = $this->Html->getView()->getRequest();
		$request = $request->withAttribute('here', '/admin/foobar/test')
			->withParam('admin', true)
			->withParam('prefix', 'admin');
		$this->Html->getView()->setRequest($request);
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::prefix('admin', function (RouteBuilder $routes) {
			$routes->connect('/:controller/:action/*');
		});

		$result = $this->Html->link('Foo', ['prefix' => 'admin', 'controller' => 'foobar', 'action' => 'test']);
		$expected = '<a href="/admin/foobar/test">Foo</a>';
		$this->assertEquals($expected, $result);

		$result = $this->Html->link('Foo', ['controller' => 'foobar', 'action' => 'test']);
		$expected = '<a href="/admin/foobar/test">Foo</a>';
		//debug($result);
		//$this->assertEquals($expected, $result);

		$result = $this->Html->linkReset('Foo', ['controller' => 'foobar', 'action' => 'test']);
		$expected = '<a href="/foobar/test">Foo</a>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * Tests
	 *
	 * @return void
	 */
	public function testLinkComplete() {
		$this->Html->getView()->setRequest($this->Html->getView()->getRequest()->withQueryParams(['x' => 'y']));

		$result = $this->Html->linkComplete('Foo', ['action' => 'test']);
		$expected = '<a href="/test?x=y">Foo</a>';
		$this->assertEquals($expected, $result);

		$result = $this->Html->linkComplete('Foo', ['action' => 'test', '?' => ['a' => 'b']]);
		$expected = '<a href="/test?a=b&amp;x=y">Foo</a>';
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
