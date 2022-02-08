<?php

namespace Tools\Test\TestCase\ErrorHandler;

use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use Shim\TestSuite\TestCase;
use Shim\TestSuite\TestTrait;
use Tools\Error\ErrorLogger;

class ErrorLoggerTest extends TestCase {

	use TestTrait;

	/**
	 * @var \Tools\Error\ErrorHandler
	 */
	protected $errorLogger;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->errorLogger = new ErrorLogger();
	}

	/**
	 * @return void
	 */
	public function testIs404(): void {
		$exception = new NotFoundException();
		$result = $this->invokeMethod($this->errorLogger, 'is404', [$exception]);

		$this->assertTrue($result);

		$exception = new InternalErrorException();
		$result = $this->invokeMethod($this->errorLogger, 'is404', [$exception]);
		$this->assertFalse($result);
	}

}
