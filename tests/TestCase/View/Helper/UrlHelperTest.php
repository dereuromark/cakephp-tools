<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\View\View;
use Tools\TestSuite\TestCase;
use Tools\View\Helper\UrlHelper;

/**
 * Datetime Test Case
 */
class UrlHelperTest extends TestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Url = new UrlHelper(new View(null));
		$this->Url->request = new Request();
		$this->Url->request->webroot = '';
	}

	/**
	 * Tests
	 *
	 * @return void
	 */
	public function testReset() {
		Router::connect('/:controller/:action/*');

		$result = $this->Url->reset(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/foobar/test';
		$this->assertSame($expected, $result);

		$this->Url->request->here = '/admin/foobar/test';
		$this->Url->request->params['prefix'] = 'admin';
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::prefix('admin', function ($routes) {
			$routes->fallbacks();
		});
		Router::pushRequest($this->Url->request);

		$result = $this->Url->build(['prefix' => 'admin', 'controller' => 'foobar', 'action' => 'test']);
		$expected = '/admin/foobar/test';
		$this->assertSame($expected, $result);

		$result = $this->Url->build(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/admin/foobar/test';
		$this->assertSame($expected, $result);

		$result = $this->Url->reset(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/foobar/test';
		$this->assertSame($expected, $result);
	}

	/**
	 * Tests
	 *
	 * @return void
	 */
	public function testResetWithPlugin() {
		Router::connect('/:controller/:action/*');

		$result = $this->Url->reset(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/foobar/test';
		$this->assertSame($expected, $result);

		$this->Url->request->here = '/admin/foo/bar/baz/test';
		$this->Url->request->params['prefix'] = 'admin';
		$this->Url->request->params['plugin'] = 'Foo';
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::plugin('Foo', function ($routes) {
			$routes->fallbacks();
		});
		Router::prefix('admin', function ($routes) {
			$routes->plugin('Foo', function ($routes) {
				$routes->fallbacks();
			});
		});
		Plugin::routes();
		Router::pushRequest($this->Url->request);

		$result = $this->Url->build(['controller' => 'bar', 'action' => 'baz', 'x']);
		$expected = '/admin/foo/bar/baz/x';
		$this->assertSame($expected, $result);

		$result = $this->Url->reset(['controller' => 'bar', 'action' => 'baz', 'x']);
		$expected = '/bar/baz/x';
		$this->assertSame($expected, $result);
	}

	/**
	 * Tests
	 *
	 * @return void
	 */
	public function testComplete() {
		$this->Url->request->query['x'] = 'y';

		$result = $this->Url->complete(['action' => 'test']);
		$expected = '/test?x=y';
		$this->assertSame($expected, $result);

		$result = $this->Url->complete(['action' => 'test', '?' => ['a' => 'b']]);
		$expected = '/test?a=b&amp;x=y';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Url);
	}

}
