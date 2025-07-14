<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Shim\TestSuite\TestCase;
use TestApp\Controller\CommonComponentTestController;
use Tools\Controller\Component\CommonComponent;

class CommonComponentTest extends TestCase {

	/**
	 * @var \TestApp\Controller\CommonComponentTestController
	 */
	protected $Controller;

	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected $request;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('App.fullBaseUrl', 'http://localhost');

		$this->loadRoutes();

		$this->request = new ServerRequest(['url' => '/my-controller/foo']);
		$this->request = $this->request->withParam('controller', 'MyController')
			->withParam('action', 'foo');
		$this->Controller = new CommonComponentTestController($this->request);
		$this->Controller->startupProcess();
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
	public function testGetParams() {
		$is = $this->Controller->Common->getPassedParam('x');
		$this->assertNull($is);

		$is = $this->Controller->Common->getPassedParam('x', 'y');
		$this->assertSame('y', $is);
	}

	/**
	 * @return void
	 */
	public function testCurrentUrl() {
		$is = $this->Controller->Common->currentUrl();
		$this->assertTrue(is_array($is) && !empty($is));

		$is = $this->Controller->Common->currentUrl(true);
		$this->assertTrue(!is_array($is) && !empty($is));
	}

	/**
	 * @return void
	 */
	public function testIsForeignReferer() {
		$ref = 'http://www.spiegel.de';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertTrue($is);

		$ref = Configure::read('App.fullBaseUrl') . '/some/controller/action';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertFalse($is);

		$ref = '';
		$is = $this->Controller->Common->isForeignReferer($ref);
		$this->assertFalse($is);

		$is = $this->Controller->Common->isForeignReferer();
		$this->assertFalse($is);
	}

	/**
	 * @return void
	 */
	public function testPostRedirect() {
		$this->Controller->Common->postRedirect(['controller' => 'MyController', 'action' => 'foo']);
		$is = $this->Controller->getResponse()->getHeaderLine('Location');
		$this->assertSame('http://localhost/my-controller/foo', $is);
		$this->assertSame(302, $this->Controller->getResponse()->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoRedirect() {
		$this->Controller->Common->autoRedirect(['controller' => 'MyController', 'action' => 'foo']);
		$is = $this->Controller->getResponse()->getHeaderLine('Location');
		$this->assertSame('http://localhost/my-controller/foo', $is);
		$this->assertSame(302, $this->Controller->getResponse()->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoRedirectReferer() {
		$url = 'http://localhost/my-controller/some-referer-action';
		$this->Controller->setRequest($this->Controller->getRequest()->withEnv('HTTP_REFERER', $url));

		$this->Controller->Common->autoRedirect(['controller' => 'MyController', 'action' => 'foo'], true);
		$is = $this->Controller->getResponse()->getHeaderLine('Location');
		$this->assertSame($url, $is);
		$this->assertSame(302, $this->Controller->getResponse()->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirect() {
		$this->Controller->Common->autoPostRedirect(['controller' => 'MyController', 'action' => 'foo'], true);
		$is = $this->Controller->getResponse()->getHeaderLine('Location');
		$this->assertSame('http://localhost/my-controller/foo', $is);
		$this->assertSame(302, $this->Controller->getResponse()->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirectReferer() {
		$url = 'http://localhost/my-controller/allowed';
		$this->Controller->setRequest($this->Controller->getRequest()->withEnv('HTTP_REFERER', $url));

		$this->Controller->Common->autoPostRedirect(['controller' => 'MyController', 'action' => 'foo'], true);
		$is = $this->Controller->getResponse()->getHeaderLine('Location');
		$this->assertSame($url, $is);
		$this->assertSame(302, $this->Controller->getResponse()->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testListActions() {
		$actions = $this->Controller->Common->listActions();
		$this->assertSame([], $actions);
	}

	/**
	 * @return void
	 */
	public function testAutoPostRedirectRefererNotWhitelisted() {
		$this->Controller->setRequest($this->Controller->getRequest()->withEnv('HTTP_REFERER', 'http://localhost/my-controller/wrong'));

		$this->Controller->Common->autoPostRedirect(['controller' => 'MyController', 'action' => 'foo'], true);

		$is = $this->Controller->getResponse()->getHeaderLine('Location');
		$this->assertSame('http://localhost/my-controller/foo', $is);
		$this->assertSame(302, $this->Controller->getResponse()->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testGetSafeRedirectUrl() {
		$result = $this->Controller->Common->getSafeRedirectUrl(['action' => 'default']);
		$this->assertSame(['action' => 'default'], $result);

		$this->request = $this->request->withQueryParams(['redirect' => '/foo/bar']);
		$this->Controller->setRequest($this->request);

		$result = $this->Controller->Common->getSafeRedirectUrl(['action' => 'default']);
		$this->assertSame('/foo/bar', $result);

		$this->request = $this->request->withQueryParams(['redirect' => 'https://dangerous.url/foo/bar']);
		$this->Controller->setRequest($this->request);

		$result = $this->Controller->Common->getSafeRedirectUrl(['action' => 'default']);
		$this->assertSame(['action' => 'default'], $result);
	}

	/**
	 * @return void
	 */
	public function testIsPosted() {
		$this->Controller->setRequest($this->Controller->getRequest()->withMethod('POST'));
		$this->assertTrue($this->Controller->Common->isPosted());
		$this->Controller->setRequest($this->Controller->getRequest()->withMethod('PUT'));
		$this->assertTrue($this->Controller->Common->isPosted());
		$this->Controller->setRequest($this->Controller->getRequest()->withMethod('PATCH'));
		$this->assertTrue($this->Controller->Common->isPosted());
	}

	/**
	 * @return void
	 */
	public function testDefaultUrlParams() {
		$result = CommonComponent::defaultUrlParams();
		$expected = [
			'plugin' => false,
			'prefix' => false,
		];
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testForceCache() {
		$this->Controller->Common->forceCache();
		$cache_control = $this->Controller->getResponse()->getHeaderLine('Cache-Control');
		$this->assertEquals('public, max-age=' . HOUR, $cache_control);
	}

	/**
	 * @return void
	 */
	public function testTrimQuery() {
		Configure::write('DataPreparation.notrim', false);
		$request = $this->Controller->getRequest();
		$request = $request->withQueryParams([
			'a' => [
				'b' => [
					' c ',
				],
			],
			' d ',
			' e',
			'f ',
		]);
		$this->Controller->setRequest($request);
		$this->Controller->Common->startup(new Event('Test'));
		$query = $this->Controller->getRequest()->getQuery();
		$expected = [
			'a' => [
				'b' => [
					'c',
				],
			],
			'd',
			'e',
			'f',
		];
		$this->assertSame($expected, $query);
	}

	/**
	 * @return void
	 */
	public function testTrimPass() {
		Configure::write('DataPreparation.notrim', false);
		$request = $this->Controller->getRequest();
		$request = $request->withParam('pass', [
			'a' => [
				'b' => [
					' c ',
				],
			],
			' d ',
			' e',
			'f ',
		]);
		$this->Controller->setRequest($request);
		$this->Controller->Common->startup(new Event('Test'));
		$pass = $this->Controller->getRequest()->getParam('pass');
		$expected = [
			'a' => [
				'b' => [
					'c',
				],
			],
			'd',
			'e',
			'f',
		];
		$this->assertSame($expected, $pass);
	}

	/**
	 * @return void
	 */
	public function testTrimData() {
		Configure::write('DataPreparation.notrim', false);
		$request = $this->Controller->getRequest();
		$request = $request->withData('data', [
			'a' => [
				'b' => [
					' c ',
				],
			],
			' d ',
			' e',
			'f ',
		]);
		$this->Controller->setRequest($request);
		$this->Controller->Common->startup(new Event('Test'));
		$pass = $this->Controller->getRequest()->getData('data');
		$expected = [
			'a' => [
				'b' => [
					'c',
				],
			],
			'd',
			'e',
			'f',
		];
		$this->assertSame($expected, $pass);
	}

	/**
	 * Test allowing no extensions in URL.
	 *
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testAllowExtensionsNone(): void {
		$this->Controller->Common->allowExtensions('');
	}

	/**
	 * Test allowing a single extension in URL that was not used.
	 *
	 * @return void
	 */
	public function testAllowExtensionsFail(): void {
		$this->expectException(NotFoundException::class);

		$this->Controller->Common->allowExtensions('csv');
	}

	/**
	 * Test allowing the extension in URL that was used.
	 *
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testAllowExtensions(): void {
		$request = $this->Controller->getRequest();
		$request = $request->withParam('_ext', 'csv');
		$this->Controller->setRequest($request);
		$this->Controller->Common->allowExtensions('csv');
	}

	/**
	 * Test allowing multiple extensions in URL, of which none was used.
	 *
	 * @return void
	 */
	public function testAllowExtensionsWrongOnes(): void {
		$request = $this->Controller->getRequest();
		$request = $request->withParam('_ext', 'csv');
		$this->Controller->setRequest($request);

		$this->expectException(NotFoundException::class);

		$this->Controller->Common->allowExtensions(['pdf', 'xml']);
	}

	/**
	 * Test allowing more than one extension in URL, of which one was used.
	 *
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testAllowExtensionsSingleMatch(): void {
		$request = $this->Controller->getRequest();
		$request = $request->withParam('_ext', 'csv');
		$this->Controller->setRequest($request);
		$this->Controller->Common->allowExtensions('csv', 'xml');
	}

}
