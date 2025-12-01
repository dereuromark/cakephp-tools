<?php

namespace Tools\Test\TestCase\Utility;

use PHPUnit\Framework\Attributes\CoversClass;
use Shim\TestSuite\TestCase;
use Tools\Utility\Clock;

#[CoversClass(Clock::class)]
class ClockTest extends TestCase {

	/**
	 * @return void
	 */
	public function testTime() {
		Clock::startClock();
		time_nanosleep(0, 200000000);
		$res = Clock::returnElapsedTime();
		$this->assertTrue(round($res, 1) === 0.2);

		time_nanosleep(0, 100000000);
		$res = Clock::returnElapsedTime(8, true);
		$this->assertTrue(round($res, 1) === 0.3);

		time_nanosleep(0, 100000000);
		$res = Clock::returnElapsedTime();
		$this->assertTrue(round($res, 1) === 0.1);
	}

}
