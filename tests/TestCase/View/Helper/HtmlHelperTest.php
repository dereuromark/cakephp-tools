<?php

namespace Tools\TestCase\View\Helper;

use Cake\Core\Plugin;
use Cake\Network\Request;
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

	public function setUp() {
		parent::setUp();

		$this->Html = new HtmlHelper(new View(null));
		$this->Html->request = new Request();
		$this->Html->request->webroot = '';
		$this->Html->Url->request = $this->Html->request;
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
	public function testResetLink() {
		Router::connect('/:controller/:action/*');

		$result = $this->Html->resetLink('Foo', ['controller' => 'foobar', 'action' => 'test']);
		$expected = '<a href="/foobar/test">Foo</a>';
		$this->assertEquals($expected, $result);

		$this->Html->request->here = '/admin/foobar/test';
		$this->Html->request->params['admin'] = true;
		$this->Html->request->params['prefix'] = 'admin';
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::prefix('admin', function ($routes) {
			$routes->connect('/:controller/:action/*');
		});

		$result = $this->Html->link('Foo', ['prefix' => 'admin', 'controller' => 'foobar', 'action' => 'test']);
		$expected = '<a href="/admin/foobar/test">Foo</a>';
		$this->assertEquals($expected, $result);

		$result = $this->Html->link('Foo', ['controller' => 'foobar', 'action' => 'test']);
		$expected = '<a href="/admin/foobar/test">Foo</a>';
		//debug($result);
		//$this->assertEquals($expected, $result);

		$result = $this->Html->resetLink('Foo', ['controller' => 'foobar', 'action' => 'test']);
		$expected = '<a href="/foobar/test">Foo</a>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * Tests
	 *
	 * @return void
	 */
	public function testCompleteLink() {
		$this->Html->request->query['x'] = 'y';

		$result = $this->Html->completeLink('Foo', ['action' => 'test']);
		$expected = '<a href="/test?x=y">Foo</a>';
		$this->assertEquals($expected, $result);

		$result = $this->Html->completeLink('Foo', ['action' => 'test', '?' => ['a' => 'b']]);
		$expected = '<a href="/test?a=b&amp;x=y">Foo</a>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Html);
	}

}
