<?php

namespace Tools\Test\TestCase\I18n;

use Shim\TestSuite\TestCase;
use Tools\I18n\DateTimeHelper;

class DateTimeHelperTest extends TestCase {

	/**
	 * @return void
	 */
	public function testConstruct() {
		$value = [
			'year' => 2009,
			'month' => 12,
			'day' => 1,
			'hour' => 0,
			'minute' => 0,
			'second' => 0,
		];
		$result = DateTimeHelper::constructDatetime($value);
		$this->assertSame('2009-12-01 00:00:00', $result);

		$value = [
			'year' => 2009,
		];
		$result = DateTimeHelper::constructDatetime($value);
		$this->assertSame('', $result);
	}

}
