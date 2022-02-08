<?php

namespace Tools\Test\TestCase\ErrorHandler;

use Shim\TestSuite\TestCase;
use Tools\Error\ErrorHandler;
use Tools\Error\ErrorLogger;

class ErrorHandlerTest extends TestCase {

	/**
	 * @var \Tools\Error\ErrorHandler
	 */
	protected $errorHandler;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->errorHandler = new ErrorHandler();
	}

	/**
	 * @return void
	 */
	public function testLogger(): void {
		$result = $this->errorHandler->getConfig('errorLogger');
		$this->assertSame(ErrorLogger::class, $result);
	}

}
