<?php

App::uses('DatetimeHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');

/**
 * Datetime Test Case
 *
 */
class DatetimeHelperTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->Datetime = new DatetimeHelper(new View(null));
	}

	/**
	 * Test user age
	 *
	 * @return void
	 */
	public function testUserAge() {
		$res = $this->Datetime->userAge('2010-01-01');
		$this->assertTrue($res >= 2);
	}

	/**
	 * Test cweek
	 *
	 * @return void
	 */
	public function testLengthOfTime() {
		$this->assertEquals('6 ' . __('Minutes') . ', 40 ' . __('Seconds'), $this->Datetime->lengthOfTime(400));

		$res = $this->Datetime->lengthOfTime(400, 'i');
		//pr($res);
		$this->assertEquals('6 ' . __('Minutes'), $res);

		$res = $this->Datetime->lengthOfTime(6 * DAY);
		//pr($res);
		$this->assertEquals('6 ' . __('Days') . ', 0 ' . __('Hours'), $res);

		//TODO: more
	}

	/**
	 * DatetimeHelperTest::testRelLengthOfTime()
	 *
	 * @return void
	 */
	public function testRelLengthOfTime() {
		$res = $this->Datetime->relLengthOfTime(date(FORMAT_DB_DATETIME, time() - 3600));
		//pr($res);
		$this->assertTrue(!empty($res));

		$res = $this->Datetime->relLengthOfTime(date(FORMAT_DB_DATETIME, time() - 4 * DAY - 5 * HOUR), null, array('plural' => 'n'));
		//pr($res);
		//$this->assertEquals($res, 'Vor 4 Tagen, 5 '.__('Hours'));
		$this->assertEquals(__('%s ago', '4 ' . __('Days') . ', ' . '5 ' . __('Hours')), $res);

		$res = $this->Datetime->relLengthOfTime(date(FORMAT_DB_DATETIME, time() + 4 * DAY + 5 * HOUR), null, array('plural' => 'n'));
		//pr($res);
		$this->assertEquals(__('In %s', '4 ' . __('Days') . ', ' . '5 ' . __('Hours')), $res);

		$res = $this->Datetime->relLengthOfTime(date(FORMAT_DB_DATETIME, time()), null, array('plural' => 'n'));
		//pr($res);
		$this->assertEquals($res, __('justNow'));
	}

	// Cake internal function...

	public function testTimeAgoInWords() {
		//echo $this->_header(__FUNCTION__);

		$res = $this->Datetime->timeAgoInWords(date(FORMAT_DB_DATETIME, time() - 4 * DAY - 5 * HOUR));
		//pr($res);
	}

	public function testIsInRange() {
		//echo $this->_header(__FUNCTION__);

		$day = date(FORMAT_DB_DATETIME, time() + 10 * DAY);

		$this->assertTrue($this->Datetime->isInRange($day, 11 * DAY));
		$this->assertTrue($this->Datetime->isInRange($day, 10 * DAY));
		$this->assertFalse($this->Datetime->isInRange($day, 9 * DAY));

		$day = date(FORMAT_DB_DATETIME, time() - 78 * DAY);
		$this->assertTrue($this->Datetime->isInRange($day, 79 * DAY));
		$this->assertTrue($this->Datetime->isInRange($day, 78 * DAY));
		$this->assertFalse($this->Datetime->isInRange($day, 77 * DAY));

		#TODO: more

	}

	/**
	 * Test cweek
	 *
	 * @return void
	 */
	public function testCweek() {

		$year = 2008;
		$month = 12;
		$day = 29;
		$date = mktime(0, 0, 0, $month, $day, $year);
		$this->assertEquals('01/' . $year, $this->Datetime->cweek($year . '-' . $month . '-' . $day));

		$year = 2009;
		$month = 1;
		$day = 1;
		$date = mktime(0, 0, 0, $month, $day, $year);
		$this->assertEquals('01/' . $year, $this->Datetime->cweek($year . '-' . $month . '-' . $day));

		$year = 2009;
		$month = 1;
		$day = 9;
		$date = mktime(0, 0, 0, $month, $day, $year);
		$this->assertEquals('02/' . $year, $this->Datetime->cweek($year . '-' . $month . '-' . $day . ' 00:00:00'));

		$year = 2009;
		$month = 12;
		$day = 26;
		$date = mktime(0, 0, 0, $month, $day, $year);
		$this->assertEquals('52/' . $year, $this->Datetime->cweek($year . '-' . $month . '-' . $day));
	}

	/**
	 * Test age
	 *
	 * @return void
	 */
	public function testAge() {
		list($year, $month, $day) = explode('-', date('Y-m-d'));
		$this->assertEquals('0', $this->Datetime->age($year . '-' . $month . '-' . $day, null));

		list($year, $month, $day) = explode('-', date('Y-m-d', strtotime('-10 years')));
		$this->assertEquals('10', $this->Datetime->age($year . '-' . $month . '-' . $day, null));

		list($year, $month, $day) = explode('-', date('Y-m-d', strtotime('-10 years +1 day')));
		$this->assertEquals('9', $this->Datetime->age($year . '-' . $month . '-' . $day, null));

		list($year, $month, $day) = explode('-', date('Y-m-d', strtotime('-10 years -1 day')));
		$this->assertEquals('10', $this->Datetime->age($year . '-' . $month . '-' . $day, null));

		// jahresübertritt
		list($year, $month, $day) = explode('-', '2005-12-01');
		list($yearE, $monthE, $dayE) = explode('-', '2008-02-29');
		$this->assertEquals('2', $this->Datetime->age($year . '-' . $month . '-' . $day, $yearE . '-' . $monthE . '-' . $dayE));

		list($year, $month, $day) = explode('-', '2002-01-29');
		list($yearE, $monthE, $dayE) = explode('-', '2008-12-02');
		$this->assertEquals('6', $this->Datetime->age($year . '-' . $month . '-' . $day, $yearE . '-' . $monthE . '-' . $dayE));

		// schaltjahr
		list($year, $month, $day) = explode('-', '2005-02-29');
		list($yearE, $monthE, $dayE) = explode('-', '2008-03-01');
		$this->assertEquals('3', $this->Datetime->age($year . '-' . $month . '-' . $day, $yearE . '-' . $monthE . '-' . $dayE));

		list($year, $month, $day) = explode('-', '2005-03-01');
		list($yearE, $monthE, $dayE) = explode('-', '2008-02-29');
		$this->assertEquals('2', $this->Datetime->age($year . '-' . $month . '-' . $day, $yearE . '-' . $monthE . '-' . $dayE));

		#zukunft
		list($yearE, $monthE, $dayE) = explode('-', date('Y-m-d', strtotime('+10 years -1 day')));
		$this->assertEquals('9', $this->Datetime->age(null, $yearE . '-' . $monthE . '-' . $dayE));

		list($yearE, $monthE, $dayE) = explode('-', date('Y-m-d', strtotime('+10 years +1 day')));
		$this->assertEquals('10', $this->Datetime->age(null, $yearE . '-' . $monthE . '-' . $dayE));
		$birthday = '1985-04-08';

		$relativeDate = '2010-04-07';
		$this->assertEquals('24', $this->Datetime->age($birthday, $relativeDate));

		$relativeDate = '2010-04-08';
		$this->assertEquals('25', $this->Datetime->age($birthday, $relativeDate));

		$relativeDate = '2010-04-09';
		$this->assertEquals('25', $this->Datetime->age($birthday, $relativeDate));
	}

	/**
	 * Test IsInTheFuture
	 *
	 * @return void
	 */

	public function testIsInTheFuture() {
		$testDate = date(FORMAT_DB_DATE, time() + 2 * DAY);
		$is = $this->Datetime->isInTheFuture($testDate);
		$this->assertTrue($is);

		$testDate = date(FORMAT_DB_DATETIME, time() - 1 * MINUTE);
		$is = $this->Datetime->isInTheFuture($testDate);
		$this->assertFalse($is);
	}

	/**
	 * Test IsNotTodayAndInTheFuture
	 *
	 * @return void
	 */

	public function testIsNotTodayAndInTheFuture() {
		$testDate = date(FORMAT_DB_DATE, time());
		$is = $this->Datetime->isNotTodayAndInTheFuture($testDate);
		$this->assertFalse($is);

		$testDate = date(FORMAT_DB_DATETIME, time() + 1 * DAY);
		$is = $this->Datetime->isNotTodayAndInTheFuture($testDate);
		$this->assertTrue($is);
	}

	/**
	 * Test IsDayAfterTomorrow
	 *
	 * @return void
	 */

	public function testIsDayAfterTomorrow() {
		$testDate = date(FORMAT_DB_DATE, time() + 2 * DAY);
		$is = $this->Datetime->isDayAfterTomorrow($testDate);
		$this->assertTrue($is);

		$testDate = date(FORMAT_DB_DATETIME, time() - 1 * MINUTE);
		$is = $this->Datetime->isDayAfterTomorrow($testDate);
		$this->assertFalse($is);
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Datetime);
	}

}
