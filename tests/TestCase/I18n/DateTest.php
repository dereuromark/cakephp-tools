<?php

namespace Tools\Test\TestCase\I18n;

use Shim\TestSuite\TestCase;
use Tools\I18n\Date;

class DateTest extends TestCase {

	/**
	 * @var \Tools\I18n\Date
	 */
	protected Date $Time;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->Time = new Date();

		parent::setUp();
	}

	/**
	 * TimeTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->Time));
		$this->assertInstanceOf(Date::class, $this->Time);
	}

	/**
	 * @return void
	 */
	public function testDate() {
		$from = '2012-12-31';
		$Date = new Date($from);
		$this->assertSame($from, $Date->format(FORMAT_DB_DATE));

		$from = ['year' => 2012, 'month' => 12, 'day' => 31];
		$this->assertSame('2012-12-31', $Date->format(FORMAT_DB_DATE));
	}

}
