<?php

namespace Tools\Test\TestCase\Controller\Component;

use App\Controller\RefererRedirectComponentTestController;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Tools\Controller\Component\RefererRedirectComponent;
use Tools\TestSuite\TestCase;

class RefererRedirectComponentTest extends TestCase {

	/**
	 * @var \Cake\Event\Event
	 */
	public $event;

	/**
	 * @var \App\Controller\RefererRedirectComponentTestController
	 */
	public $Controller;

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

		$modifiedResponse = $refererRedirectComponent->beforeRedirect($this->event, ['action' => 'foo'], $response);

		$this->assertSame(['/somewhere-else'], $modifiedResponse->getHeader('Location'));
	}

}
