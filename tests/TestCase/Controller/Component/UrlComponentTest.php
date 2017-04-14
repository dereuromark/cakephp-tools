<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Routing\Router;
use App\Controller\UrlComponentTestController;
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
	public function setUp() {
		parent::setUp();

		$this->event = new Event('Controller.beforeFilter');
		$this->Controller = new UrlComponentTestController(new Request());

		Configure::write('App.fullBaseUrl', 'http://localhost');
	}

	/**
	 * @return void
	 */
	public function tearDown() {
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
		$this->Controller->Url->request->query['x'] = 'y';

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

		$this->Controller->Url->request->here = '/admin/foo/bar/baz/test';
		$this->Controller->Url->request->params['prefix'] = 'admin';
		$this->Controller->Url->request->params['plugin'] = 'Foo';
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
		Router::pushRequest($this->Controller->Url->request);

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
		$this->Controller->Url->request->query['x'] = 'y';

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
