<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Shim\TestSuite\TestCase;
use TestApp\Controller\RefererRedirectComponentTestController;
use Tools\Controller\Component\RefererRedirectComponent;

class RefererRedirectComponentTest extends TestCase {

	/**
	 * @var \Cake\Event\Event
	 */
	protected $event;

	/**
	 * @var \TestApp\Controller\RefererRedirectComponentTestController
	 */
	protected $Controller;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$serverRequest = new ServerRequest();
		$serverRequest = $serverRequest->withQueryParams(['ref' => '/somewhere-else']);

		$this->event = new Event('Controller.beforeFilter');
		$this->Controller = new RefererRedirectComponentTestController($serverRequest);

		Configure::write('App.fullBaseUrl', 'http://localhost');
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
	public function testBeforeRedirect() {
		$response = new Response();

		$componentRegistry = new ComponentRegistry($this->Controller);
		$refererRedirectComponent = new RefererRedirectComponent($componentRegistry);

		$refererRedirectComponent->beforeRedirect($this->event, ['action' => 'foo'], $response);
		/** @var \Cake\Http\Response $modifiedResponse */
		$modifiedResponse = $this->event->getResult();

		$this->assertSame(['/somewhere-else'], $modifiedResponse->getHeader('Location'));
	}

	#[DataProvider('unsafeRefererProvider')]
	public function testRejectsExternalAndAmbiguousReferers(mixed $referer): void {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['ref' => $referer]));
		$component = new RefererRedirectComponent(new ComponentRegistry($this->Controller));
		$component->beforeRedirect($this->event, '/fallback', new Response());
		$this->assertNull($this->event->getResult());
	}

	public static function unsafeRefererProvider(): array {
		return [
			['https://example.com/'],
			['//example.com/'],
			['/\\example.com/'],
			['/path\\segment'],
			["/\texample.com/"],
			["/path\r\nLocation: https://example.com"],
			[123],
		];
	}

}
