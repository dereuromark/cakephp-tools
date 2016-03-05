<?php

namespace Tools\TestCase\View\Helper;

use Cake\View\View;
use Tools\TestSuite\TestCase;
use Tools\Utility\Time;
use Tools\View\Helper\TimeHelper;

/**
 * Datetime Test Case
 */
class TimeHelperTest extends TestCase {

	public function setUp() {
		parent::setUp();

		$this->Time = new TimeHelper(new View(null));
	}

	/**
	 * Test calling Utility.Number class
	 *
	 * @return void
	 */
	public function testParentCall() {
		$result = $this->Time->age((date('Y') - 15) . '-01-01');
		$this->assertSame(15, $result);
	}

	/**
	 * Test user age
	 *
	 * @return void
	 */
	public function testUserAge() {
		$res = $this->Time->userAge((date('Y') - 4) . '-01-01');
		$this->assertTrue($res >= 3 && $res <= 5);

		$res = $this->Time->userAge('2023-01-01');
		$this->assertSame('', $res);

		$res = $this->Time->userAge('1903-01-01');
		$this->assertSame('', $res);

		$res = $this->Time->userAge('1901-01-01');
		$this->assertSame('', $res);
	}

	/**
	 * Tests that calling a CakeTime method works.
	 *
	 * @return void
	 */
	public function testTimeAgoInWords() {
		$res = $this->Time->timeAgoInWords(date(FORMAT_DB_DATETIME, time() - 4 * DAY - 5 * HOUR));
		$this->debug($res);
	}

	/**
	 * DatetimeHelperTest::testPublished()
	 *
	 * @return void
	 */
	public function testPublished() {
		$result = $this->Time->published((new Time(date(FORMAT_DB_DATETIME)))->addSeconds(1));
		$expected = 'class="published today';
		$this->assertContains($expected, $result);

		$result = $this->Time->published((new Time(date(FORMAT_DB_DATETIME)))->addDays(1));
		$expected = 'class="published notyet';
		$this->assertContains($expected, $result);

		$result = $this->Time->published((new Time(date(FORMAT_DB_DATETIME)))->subDays(2));
		$expected = 'class="published already';
		$this->assertContains($expected, $result);
	}

	/**
	 * DatetimeHelperTest::testTimezones()
	 *
	 * @return void
	 */
	public function testTimezones() {
		$result = $this->Time->timezones();
		$this->assertTrue(!empty($result));
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Time);
	}

}
