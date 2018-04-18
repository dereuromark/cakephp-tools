<?php

namespace Tools\Test\TestCase\Error\Middleware;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Network\Exception\NotFoundException;
use Tools\Error\Middleware\ErrorHandlerMiddleware;
use Tools\TestSuite\TestCase;
use Tools\TestSuite\ToolsTestTrait;

class ErrorHandlerMiddlewareTest extends TestCase {

	use ToolsTestTrait;

	/**
	 * @var \Tools\Error\Middleware\ErrorHandlerMiddleware
	 */
	protected $errorHandlerMiddleware;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.fullBaseUrl', 'http://foo.bar');

		$this->errorHandlerMiddleware = new ErrorHandlerMiddleware();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->errorHandlerMiddleware);

		Configure::delete('App.fullBaseUrl');
	}

	/**
	 * @return void
	 */
	public function test404() {
		$parameters = [
			new NotFoundException(),
			new ServerRequest(),
		];
		$result = $this->invokeMethod($this->errorHandlerMiddleware, 'is404', $parameters);
		$this->assertTrue($result);

		$request = new ServerRequest(['url' => 'http://foo.bar', 'environment' => ['HTTP_REFERER' => 'http://foo.bar/baz']]);
		$parameters = [
			new NotFoundException(),
			$request,
		];
		$result = $this->invokeMethod($this->errorHandlerMiddleware, 'is404', $parameters);
		$this->assertFalse($result);
	}

}
