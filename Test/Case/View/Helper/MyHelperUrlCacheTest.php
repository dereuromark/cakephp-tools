<?php

App::uses('View', 'View');
App::uses('Controller', 'Controller');
App::uses('Cache', 'Cache');
App::uses('MyHelper', 'Tools.View/Helper');
App::uses('CakeRequest', 'Network');

class MyHelperUrlCacheTest extends CakeTestCase {

	public $HtmlHelper = null;

	public function setUp() {
		parent::setUp();

		Configure::write('UrlCache.active', true);
		Configure::write('UrlCache.pageFiles', true);
		Configure::write('UrlCache.verbosePrefixes', true);
		Configure::write('Routing.prefixes', ['admin']);
		Router::reload();

		$this->HtmlHelper = new MyHelper(new View(new Controller(new CakeRequest('/test', false))));
		$this->HtmlHelper->beforeRender('foo');
	}

	public function tearDown() {
		Cache::delete(UrlCacheManager::$cacheKey, '_cake_core_');
		Cache::delete(UrlCacheManager::$cachePageKey, '_cake_core_');
		Configure::delete('UrlCache');

		parent::tearDown();
	}

	public function testInstance() {
		$this->assertTrue(is_a($this->HtmlHelper, 'MyHelper'));
	}

	public function testSettings() {
		$settings = Configure::read('UrlCache');
		$this->assertTrue($settings['active']);
	}

	public function testUrlRelative() {
		$url = $this->HtmlHelper->url(['controller' => 'posts']);
		$this->assertEquals($url, '/posts');
		$this->assertEquals(['779e416667ffd33e75ac19e0952abb47' => '/posts'], UrlCacheManager::$cache);

		$this->HtmlHelper->afterLayout('foo');
		$cache = Cache::read(UrlCacheManager::$cacheKey, '_cake_core_');
		$this->assertEquals(['779e416667ffd33e75ac19e0952abb47' => '/posts'], $cache);
	}

	public function testUrlFull() {
		$url = $this->HtmlHelper->url(['controller' => 'posts'], true);
		$this->assertPattern('/http:\/\/(.*)\/posts/', $url);
		$this->assertEquals(['8f45f5c31d138d700742b01ccb673e1e'], array_keys(UrlCacheManager::$cache));
		$this->assertPattern('/http:\/\/(.*)\/posts/', UrlCacheManager::$cache['8f45f5c31d138d700742b01ccb673e1e']);

		$this->HtmlHelper->afterLayout('foo');
		$cache = Cache::read(UrlCacheManager::$cacheKey, '_cake_core_');
		$this->assertEquals(['8f45f5c31d138d700742b01ccb673e1e'], array_keys($cache));
		$this->assertPattern('/http:\/\/(.*)\/posts/', $cache['8f45f5c31d138d700742b01ccb673e1e']);
	}

	public function testUrlRelativeAndFull() {
		$this->HtmlHelper->url(['controller' => 'posts']);
		$this->HtmlHelper->url(['controller' => 'posts'], true);

		$this->assertEquals(['779e416667ffd33e75ac19e0952abb47', '8f45f5c31d138d700742b01ccb673e1e'], array_keys(UrlCacheManager::$cache));

		$this->HtmlHelper->afterLayout('foo');
		$cache = Cache::read(UrlCacheManager::$cacheKey, '_cake_core_');
		$this->assertEquals(['779e416667ffd33e75ac19e0952abb47', '8f45f5c31d138d700742b01ccb673e1e'], array_keys($cache));
	}

	public function testUrlWithParams() {
		$this->HtmlHelper->url(['controller' => 'posts'], true);
		$this->HtmlHelper->url(['controller' => 'posts', 'action' => 'view', '3']);

		$this->assertEquals(['8f45f5c31d138d700742b01ccb673e1e'], array_keys(UrlCacheManager::$cache));
		$this->assertEquals(['e2ff5470228f80f98b2be7ddbcab340d'], array_keys(UrlCacheManager::$cachePage));

		$this->HtmlHelper->afterLayout('foo');
		$cache = Cache::read(UrlCacheManager::$cacheKey, '_cake_core_');
		$this->assertEquals(['8f45f5c31d138d700742b01ccb673e1e'], array_keys($cache));
		$cache = Cache::read(UrlCacheManager::$cachePageKey, '_cake_core_');
		$this->assertEquals(['e2ff5470228f80f98b2be7ddbcab340d'], array_keys($cache));
	}

	public function testUrlWithDifferentOrders() {
		$this->HtmlHelper->url(['plugin' => 'tools', 'controller' => 'posts', 'action' => 'view']);
		$this->HtmlHelper->url(['action' => 'view', 'controller' => 'posts', 'plugin' => 'tools']);
		$this->assertEquals(['0ba1e9d8ab27a564450be9aa93bd4a1c'], array_keys(UrlCacheManager::$cache));

		$this->HtmlHelper->url(['plugin' => 'tools', 'controller' => 'posts', 'action' => 'view', '3']);
		$this->HtmlHelper->url(['action' => 'view', 'controller' => 'posts', 'plugin' => 'tools', '3']);
		$this->assertEquals(['f5509ea5e31562b541948d89b4d65700'], array_keys(UrlCacheManager::$cachePage));

		$this->HtmlHelper->afterLayout('foo');
		$cache = Cache::read(UrlCacheManager::$cacheKey, '_cake_core_');
		$this->assertEquals(['0ba1e9d8ab27a564450be9aa93bd4a1c'], array_keys($cache));
		$cache = Cache::read(UrlCacheManager::$cachePageKey, '_cake_core_');
		$this->assertEquals(['f5509ea5e31562b541948d89b4d65700'], array_keys($cache));
	}

	public function testUrlWithDifferentValues() {
		$url = $this->HtmlHelper->url(['plugin' => false, 'controller' => 'posts', 'action' => 'details', 1]);
		$url2 = $this->HtmlHelper->url(['plugin' => null, 'controller' => 'posts', 'action' => 'details', '1']);
		$url3 = $this->HtmlHelper->url(['plugin' => '', 'controller' => 'posts', 'action' => 'details', true]);
		$this->assertEquals($url, $url2);
		$this->assertEquals($url2, $url3);
		$this->assertEquals(['f0fdde123fe5958781cfad4284ee59c4'], array_keys(UrlCacheManager::$cachePage));

		$this->HtmlHelper->afterLayout('foo');
		$cache = Cache::read(UrlCacheManager::$cachePageKey, '_cake_core_');
		$this->assertEquals(['f0fdde123fe5958781cfad4284ee59c4'], array_keys($cache));
	}

	public function testGlobalCache() {
		$res = UrlCacheManager::get(['controller' => 'posts', 'action' => 'index'], false);
		$this->assertEquals(false, $res);

		$url = $this->HtmlHelper->url(['controller' => 'posts', 'action' => 'index']);
		$this->HtmlHelper->afterLayout('foo');

		# on second page the same url will not trigger a miss but a hit
		$this->HtmlHelper->beforeRender('foo');
		$res = UrlCacheManager::get(['controller' => 'posts', 'action' => 'index'], false);
		$this->assertEquals($url, $res);

		$this->HtmlHelper->afterLayout('foo');
	}

	public function testUrlWithVerbosePrefixes() {
		$url = $this->HtmlHelper->url(['plugin' => 'tools', 'controller' => 'posts', 'action' => 'view', 'admin' => true]);
		$url2 = $this->HtmlHelper->url(['action' => 'view', 'controller' => 'posts', 'plugin' => 'tools', 'admin' => true]);
		$this->assertEquals(['3016e3546edf1c2152905da5a42ea13f'], array_keys(UrlCacheManager::$cache));
		$this->assertEquals('/admin/tools/posts/view', $url);
		$this->assertEquals($url, $url2);

		$this->HtmlHelper->url(['plugin' => 'tools', 'controller' => 'posts', 'action' => 'view', 'admin' => true, 'some' => 'param']);
		$this->HtmlHelper->url(['action' => 'view', 'controller' => 'posts', 'plugin' => 'tools', 'some' => 'param', 'admin' => true]);
		$this->assertEquals(['25a924190d6e0ed09127d1904a286a95'], array_keys(UrlCacheManager::$cachePage));

		$this->HtmlHelper->afterLayout('foo');
	}

	public function testUrlWithoutVerbosePrefixes() {
		$this->skipIf(true, 'doesnt work yet');

		Configure::delete('UrlCache');
		Configure::write('UrlCache.active', true);
		Configure::write('UrlCache.pageFiles', true);
		Configure::write('UrlCache.verbosePrefixes', false);
		UrlCacheManager::$paramFields = ['controller', 'plugin', 'action', 'prefix'];
		$this->HtmlHelper->beforeRender('foo');

		$url = $this->HtmlHelper->url(['plugin' => 'tools', 'controller' => 'posts', 'action' => 'view', 'prefix' => 'admin']);
		$this->assertEquals(['41d5d7eb9442adbe76e6c7ebbb02ecc7'], array_keys(UrlCacheManager::$cache));
		$this->assertEquals('/admin/tools/posts/view', $url);

		$url = $this->HtmlHelper->url(['plugin' => 'tools', 'controller' => 'posts', 'action' => 'view', 'prefix' => 'admin', 'some' => 'param']);
		$this->HtmlHelper->url(['action' => 'view', 'controller' => 'posts', 'plugin' => 'tools', 'some' => 'param', 'prefix' => 'admin']);
		$this->assertEquals('/admin/tools/posts/view/some:param', $url);
		$this->assertEquals(['6a7091b88c8132ebb5461851808d318a'], array_keys(UrlCacheManager::$cachePage));

		$this->HtmlHelper->afterLayout('foo');
	}

/**
*
*/
	public function testPaginationUrlsWithQueryString() {
		$urlArray = ['controller' => 'posts', 'action' => 'list_posts', '?' => ['page' => 2]];
		$this->HtmlHelper->beforeRender('foo');
		$url = $this->HtmlHelper->url($urlArray);
		$this->assertEquals(['e8383c43f33fcf11621240f22814603b'], array_keys(UrlCacheManager::$cachePage));
		$this->assertEquals('/posts/list_posts?page=2', $url);
		
		$this->HtmlHelper->afterLayout('foo');
	}
}

