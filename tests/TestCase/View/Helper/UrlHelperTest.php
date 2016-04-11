<?php

namespace Tools\TestCase\View\Helper;

use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\View\View;
use Tools\TestSuite\TestCase;
use Tools\View\Helper\UrlHelper;

/**
 * Datetime Test Case
 */
class UrlHelperTest extends TestCase {

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
		$this->assertEquals($expected, $result);

		$this->Url->request->here = '/admin/foobar/test';
		$this->Url->request->params['admin'] = true;
		$this->Url->request->params['prefix'] = 'admin';
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::prefix('admin', function ($routes) {
			$routes->connect('/:controller/:action/*');
		});

		$result = $this->Url->build(['prefix' => 'admin', 'controller' => 'foobar', 'action' => 'test']);
		$expected = '/admin/foobar/test';
		$this->assertEquals($expected, $result);

		$result = $this->Url->build(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/admin/foobar/test';
		//debug($result);
		//$this->assertEquals($expected, $result);

		$result = $this->Url->reset(['controller' => 'foobar', 'action' => 'test']);
		$expected = '/foobar/test';
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $result);

		$result = $this->Url->complete(['action' => 'test', '?' => ['a' => 'b']]);
		$expected = '/test?a=b&amp;x=y';
		$this->assertEquals($expected, $result);
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Url);
	}

}
