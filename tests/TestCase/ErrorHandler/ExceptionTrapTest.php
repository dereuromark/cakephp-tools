<?php

namespace Tools\Test\TestCase\ErrorHandler;

use Shim\TestSuite\TestCase;
use Tools\Error\ErrorLogger;
use Tools\Error\ExceptionTrap;

class ExceptionTrapTest extends TestCase {

	/**
	 * @var \Tools\Error\ExceptionTrap
	 */
	protected ExceptionTrap $exceptionTrap;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->exceptionTrap = new ExceptionTrap();
	}

	/**
	 * @return void
	 */
	public function testLogger(): void {
		$result = $this->exceptionTrap->getConfig('logger');
		$this->assertSame(ErrorLogger::class, $result);
	}

}
