<?php

namespace Tools\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Error\Debugger;
use DateTime;
use Shim\TestSuite\TestCase;
use Tools\Utility\Time;

class TimeTest extends TestCase {

	/**
	 * @var \Tools\Utility\Time
	 */
	protected $Time;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->Time = new Time();

		parent::setUp();
	}

	/**
	 * TimeTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->Time));
		$this->assertInstanceOf('Tools\Utility\Time', $this->Time);
	}

	/**
	 * Currently only works with timezoned localized values, not with UTC!!!
	 *
	 * @return void
	 */
	public function testIncrementDate() {
		$timezone = Configure::read('Config.timezone');
		//$timezone = Date$this->Time->timezone();
		Configure::write('Config.timezone', 'Europe/Berlin');
		$phpTimezone = date_default_timezone_get();
		date_default_timezone_set('Europe/Berlin');

		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, 0, 0);
		$this->assertSame($from, $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, 0, 1);
		$this->assertSame('2013-01-31', $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, 0, 2);
		$this->assertSame('2013-02-28', $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, 0, 4);
		$this->assertSame('2013-04-30', $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, 1, 0);
		$this->assertSame('2013-12-31', $Date->format(FORMAT_DB_DATE));

		// from leap year
		$from = '2008-02-29';
		$Date = $this->Time->incrementDate($from, 1, 0);
		$this->assertSame('2009-02-28', $Date->format(FORMAT_DB_DATE));

		// into leap year
		$from = '2007-02-28';
		$Date = $this->Time->incrementDate($from, 1, 0);
		$this->assertSame('2008-02-29', $Date->format(FORMAT_DB_DATE));

		// other direction
		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, 0, -1);
		$this->assertSame('2012-11-30', $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, -1, -1);
		$this->assertSame('2011-11-30', $Date->format(FORMAT_DB_DATE));

		// including days
		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, 0, 1, 1);
		$this->assertSame('2013-02-01', $Date->format(FORMAT_DB_DATE));

		// including days
		$from = '2012-12-31';
		$Date = $this->Time->incrementDate($from, 0, 1, 5);
		$this->assertSame('2013-02-05', $Date->format(FORMAT_DB_DATE));

		Configure::write('Config.timezone', $timezone);
		date_default_timezone_set($phpTimezone);
	}

	/**
	 * @return void
	 */
	public function testNiceDate() {
		$res = setlocale(LC_TIME, 'de_DE.UTF-8', 'deu_deu');
		//$this->assertTrue(!empty($res));

		$values = [
			['2009-12-01 00:00:00', FORMAT_NICE_YMD, '01.12.2009'],
			['2009-12-01 00:00:00', FORMAT_NICE_M_FULL, 'December'],
		];
		foreach ($values as $v) {
			$result = $this->Time->niceDate($v[0], $v[1]);
			$this->assertEquals($v[2], $result);
		}

		$date = '2009-12-01 00:00:00';
		$format = FORMAT_NICE_YMD;
		$result = $this->Time->niceDate($date, $format, ['oclock' => true]);
		$expected = '01.12.2009';
		$this->assertEquals($expected, $result);

		$date = '2009-12-01 00:00:00';
		$format = FORMAT_NICE_YMDHM;
		$result = $this->Time->niceDate($date, $format, ['oclock' => true]);
		$expected = '01.12.2009, 00:00 ' . __d('tools', 'o\'clock');
		$this->assertEquals($expected, $result);

		$date = time();
		$format = FORMAT_NICE_YMDHM;
		$result = $this->Time->niceDate($date, $format, ['oclock' => true]);
		$this->assertNotEmpty($result);
	}

	/**
	 * TimeTest::testNiceTime()
	 *
	 * @return void
	 */
	public function testNiceTime() {
		$result = $this->Time->niceTime('22:11:18');
		$expected = '22:11';
		$this->assertEquals($expected, $result);

		$result = $this->Time->niceTime('2014-11-12 22:11:18');
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test that input as date only (YYYY-MM-DD) does not suddendly return a
	 * different date on output due to timezone differences.
	 * Here the timezone should not apply since we only input date and only output
	 * date (time itself is irrelevant).
	 *
	 * @return void
	 */
	public function testDateWithTimezone() {
		$res = setlocale(LC_TIME, 'de_DE.UTF-8', 'deu_deu');
		//$this->assertTrue(!empty($res));
		Configure::write('Config.timezone', 'America/Anchorage');

		$ret = $this->Time->niceDate('2009-12-01');
		$this->assertEquals('01.12.2009', $ret);

		$ret = $this->Time->localDate('2009-12-01');
		$this->assertEquals('01.12.2009', $ret);
	}

	/**
	 * TimeTest::testParseLocalizedDate()
	 *
	 * @return void
	 */
	public function testParseLocalizedDate() {
		$ret = $this->Time->parseLocalizedDate('15-Feb-2009', 'j-M-Y', 'start');
		//$this->debug($ret);
		$this->assertEquals('2009-02-15 00:00:00', $ret);

		// problem when not passing months or days as well - no way of knowing how exact the date was
		$ret = $this->Time->parseLocalizedDate('2009', 'Y', 'start');
		//pr($ret);
		//$this->assertEquals($ret, '2009-01-01 00:00:00');
		$ret = $this->Time->parseLocalizedDate('Feb 2009', 'M Y', 'start');
		//pr($ret);
		//$this->assertEquals($ret, '2009-02-01 00:00:00');

		$values = [
			[__d('tools', 'Today'), [date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y')))]],
			['2010', ['2010-01-01 00:00:00', '2010-12-31 23:59:59']],
			['23.02.2011', ['2011-02-23 00:00:00', '2011-02-23 23:59:59']],
			['22/02/2011', ['2011-02-22 00:00:00', '2011-02-22 23:59:59']],
			['3/2/11', ['2011-02-03 00:00:00', '2011-02-03 23:59:59']],
			//array('2/12/9', array('2009-12-02 00:00:00', '2009-12-02 23:59:59')),
			//array('12/2009', array('2009-12-01 00:00:00', '2009-12-31 23:59:59')),
		];
		foreach ($values as $v) {
			$ret = $this->Time->parseLocalizedDate($v[0], null, 'start');
			//pr($ret);
			$this->assertEquals($v[1][0], $ret);

			$ret = $this->Time->parseLocalizedDate($v[0], null, 'end');
			//pr($ret);
			$this->assertEquals($v[1][1], $ret);
		}
	}

	/**
	 * @return void
	 */
	public function testLocalDate() {
		//$this->skipIf(PHP_SAPI === 'cli', 'for now');
		$res = setlocale(LC_TIME, ['de_DE.UTF-8', 'deu_deu']);
		$this->assertTrue(!empty($res), 'Result: ' . Debugger::exportVar($res, true));

		$values = [
			['2009-12-01 00:00:00', FORMAT_LOCAL_YMD, '01.12.2009'],
			['2009-12-01 00:00:00', FORMAT_LOCAL_M_FULL, 'Dezember'],
		];
		foreach ($values as $v) {
			$ret = $this->Time->localDate($v[0], $v[1]);
			//$this->debug($ret);
			$this->assertEquals($v[2], $ret);
		}

		$date = '2009-12-01 00:00:00';
		$format = FORMAT_LOCAL_YMD;
		$result = $this->Time->localDate($date, $format, ['oclock' => true]);
		$expected = '01.12.2009';
		$this->assertEquals($expected, $result);

		$date = '2009-12-01 00:00:00';
		$format = FORMAT_LOCAL_YMDHM;
		$result = $this->Time->localDate($date, $format, ['oclock' => true]);
		$expected = '01.12.2009, 00:00 ' . __d('tools', 'o\'clock');
		$this->assertEquals($expected, $result);
	}

	/**
	 * TimeTest::testPeriod()
	 *
	 * @return void
	 */
	public function testPeriod() {
		//$this->out($this->_header(__FUNCTION__), true);
		$values = [
			[__d('tools', 'Today'), [date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y')))]],

			['2010', ['2010-01-01 00:00:00', '2010-12-31 23:59:59']],
			['2011-02', ['2011-02-01 00:00:00', '2011-02-28 23:59:59']],
			['2012-02', ['2012-02-01 00:00:00', '2012-02-29 23:59:59']],
			['2010-02-23', ['2010-02-23 00:00:00', '2010-02-23 23:59:59']],
			['2010-02-23 bis 2010-02-26', ['2010-02-23 00:00:00', '2010-02-26 23:59:59']],
			//array('2010-02-23 11:11:11 bis 2010-02-23 11:12:01', array('2010-02-23 11:11:11', '2010-02-23 11:12:01')),
			// localized
			['23.02.2011', ['2011-02-23 00:00:00', '2011-02-23 23:59:59']],
			['23.2.2010 bis 26.2.2011', ['2010-02-23 00:00:00', '2011-02-26 23:59:59']],
		];

		foreach ($values as $v) {
			$ret = $this->Time->period($v[0]);
			//pr($ret);
			$this->assertEquals($v[1], $ret);

		}
	}

	/**
	 * TimeTest::testPeriodAsSql()
	 *
	 * @return void
	 */
	public function testPeriodAsSql() {
		//$this->out($this->_header(__FUNCTION__), true);
		$values = [
			[__d('tools', 'Today'), "(Model.field >= '" . date(FORMAT_DB_DATE) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE) . " 23:59:59')"],
			[__d('tools', 'Yesterday') . ' ' . __d('tools', 'until') . ' ' . __d('tools', 'Today'), "(Model.field >= '" . date(FORMAT_DB_DATE, time() - DAY) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE) . " 23:59:59')"],
			[__d('tools', 'Today') . ' ' . __d('tools', 'until') . ' ' . __d('tools', 'Tomorrow'), "(Model.field >= '" . date(FORMAT_DB_DATE, time()) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE, time() + DAY) . " 23:59:59')"],
			[__d('tools', 'Yesterday') . ' ' . __d('tools', 'until') . ' ' . __d('tools', 'Tomorrow'), "(Model.field >= '" . date(FORMAT_DB_DATE, time() - DAY) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE, time() + DAY) . " 23:59:59')"],
		];

		foreach ($values as $v) {
			$ret = $this->Time->periodAsSql($v[0], 'Model.field');
			//pr($v[1]);
			//pr($ret);
			$this->assertSame($v[1], $ret);

		}
	}

	/**
	 * TimeTest::testDifference()
	 *
	 * @return void
	 */
	public function testDifference() {
		//$this->out($this->_header(__FUNCTION__), true);
		$values = [
			['2010-02-23 11:11:11', '2010-02-23 11:12:01', 50],
			['2010-02-23 11:11:11', '2010-02-24 11:12:01', DAY + 50],
		];

		foreach ($values as $v) {
			$ret = $this->Time->difference($v[0], $v[1]);
			$this->assertEquals($v[2], $ret);
		}
	}

	/**
	 * TimeTest::testIsLeapYear()
	 *
	 * @return void
	 */
	public function testIsLeapYear() {
		$this->Time = new Time('2001-01-01');
		$is = $this->Time->isLeapYear();
		$this->assertFalse($is);

		$this->Time = new Time('2008-01-01');
		$is = $this->Time->isLeapYear();
		$this->assertTrue($is);

		$this->Time = new Time('2000-01-01');
		$is = $this->Time->isLeapYear();
		$this->assertTrue($is);
	}

	/**
	 * TimeTest::testIsInRange()
	 *
	 * @return void
	 */
	public function testIsInRange() {
		$is = $this->Time->isInRange(date(FORMAT_DB_DATETIME, time() + 22 * HOUR), DAY);
		$this->assertTrue($is);

		$is = $this->Time->isInRange(date(FORMAT_DB_DATETIME, time() + 26 * HOUR), DAY);
		$this->assertFalse($is);

		$day = date(FORMAT_DB_DATETIME, time() + 10 * DAY);

		$this->assertTrue($this->Time->isInRange($day, 11 * DAY));
		$this->assertTrue($this->Time->isInRange($day, 10 * DAY));
		$this->assertFalse($this->Time->isInRange($day, 9 * DAY));

		$day = date(FORMAT_DB_DATETIME, time() - 78 * DAY);
		$this->assertTrue($this->Time->isInRange($day, 79 * DAY));
		$this->assertTrue($this->Time->isInRange($day, 78 * DAY));
		$this->assertFalse($this->Time->isInRange($day, 77 * DAY));
	}

	/**
	 * Test age
	 *
	 * @return void
	 */
	public function testAge() {
		$this->assertEquals('0', $this->Time->age(null));

		list($year, $month, $day) = explode('-', date('Y-m-d'));
		$this->assertEquals('0', $this->Time->age($year . '-' . $month . '-' . $day, null));

		list($year, $month, $day) = explode('-', date('Y-m-d', strtotime('-10 years')));
		$this->assertEquals('10', $this->Time->age($year . '-' . $month . '-' . $day, null));

		list($year, $month, $day) = explode('-', date('Y-m-d', strtotime('-10 years +1 day')));
		$this->assertEquals('9', $this->Time->age($year . '-' . $month . '-' . $day, null));

		list($year, $month, $day) = explode('-', date('Y-m-d', strtotime('-10 years -1 day')));
		$this->assertEquals('10', $this->Time->age($year . '-' . $month . '-' . $day, null));

		// Crossing years
		$this->assertEquals('2', $this->Time->age('2005-12-01', '2008-02-29'));
		$this->assertEquals('6', $this->Time->age('2002-01-29', '2008-12-02'));

		// Leap year
		$this->assertEquals('2', $this->Time->age('2005-03-01', '2008-02-28'));
		$this->assertEquals('2', $this->Time->age('2005-03-01', '2008-02-29'));

		$this->assertEquals('3', $this->Time->age('2005-03-01', '2008-03-01'));
		$this->assertEquals('3', $this->Time->age('2005-02-29', '2008-03-01'));

		// Future
		list($yearE, $monthE, $dayE) = explode('-', date('Y-m-d', strtotime('+10 years -1 day')));
		$this->assertEquals('9', $this->Time->age(null, $yearE . '-' . $monthE . '-' . $dayE));

		list($yearE, $monthE, $dayE) = explode('-', date('Y-m-d', strtotime('+10 years +1 day')));
		$this->assertEquals('10', $this->Time->age(null, $yearE . '-' . $monthE . '-' . $dayE));

		$birthday = '2033-04-09';
		$this->assertEquals(-1, $this->Time->age($birthday));

		$birthday = '1985-04-08';

		$relativeDate = '2010-04-07';
		$this->assertEquals('24', $this->Time->age($birthday, $relativeDate));

		$relativeDate = '2010-04-08';
		$this->assertEquals('25', $this->Time->age($birthday, $relativeDate));

		$relativeDate = '2010-04-09';
		$this->assertEquals('25', $this->Time->age($birthday, $relativeDate));
	}

	/**
	 * TimeTest::testAgeBounds()
	 *
	 * @return void
	 */
	public function testAgeBounds() {
		//$this->out($this->_header(__FUNCTION__), true);
		$values = [
			[20, 20, ['min' => '1990-07-07', 'max' => '1991-07-06']],
			[10, 30, ['min' => '1980-07-07', 'max' => '2001-07-06']],
			[11, 12, ['min' => '1998-07-07', 'max' => '2000-07-06']],
		];

		foreach ($values as $v) {
			//echo $v[0].'/'.$v[1];
			$ret = $this->Time->ageBounds($v[0], $v[1], true, '2011-07-06'); //TODO: relative time
			//pr($ret);
			if (isset($v[2])) {
				$this->assertSame($v[2], $ret);
				$this->assertEquals($v[0], $this->Time->age($v[2]['max'], '2011-07-06'));
				$this->assertEquals($v[1], $this->Time->age($v[2]['min'], '2011-07-06'));
			}
		}
	}

	/**
	 * TimeTest::testAgeByYear()
	 *
	 * @return void
	 */
	public function testAgeByYear() {
		//$this->out($this->_header(__FUNCTION__), true);

		// year only
		$is = $this->Time->ageByYear(2000);
		//$this->out($is);
		$this->assertEquals((date('Y') - 2001) . '/' . (date('Y') - 2000), $is);

		$is = $this->Time->ageByYear(1985);
		$this->assertEquals((date('Y') - 1986) . '/' . (date('Y') - 1985), $is);

		// with month
		if (($month = date('n') + 1) <= 12) {
			$is = $this->Time->ageByYear(2000, $month);
			//$this->out($is);
			//$this->assertEquals($is, (date('Y')-2001).'/'.(date('Y')-2000), null, '2000/'.$month);
			$this->assertSame(date('Y') - 2001, $is); //null, '2000/'.$month
		}

		if (($month = date('n') - 1) >= 1) {
			$is = $this->Time->ageByYear(2000, $month);
			//$this->out($is);
			//$this->assertEquals($is, (date('Y')-2001).'/'.(date('Y')-2000), null, '2000/'.$month);
			$this->assertSame(date('Y') - 2000, $is); //null, '2000/'.$month)
		}
	}

	/**
	 * TimeTest::testDaysInMonth()
	 *
	 * @return void
	 */
	public function testDaysInMonth() {
		//$this->out($this->_header(__FUNCTION__), true);

		$ret = $this->Time->daysInMonth('2004', '3');
		$this->assertEquals(31, $ret);

		$ret = $this->Time->daysInMonth('2006', '4');
		$this->assertEquals(30, $ret);

		$ret = $this->Time->daysInMonth('2007', '2');
		$this->assertEquals(28, $ret);

		$ret = $this->Time->daysInMonth('2008', '2');
		$this->assertEquals(29, $ret);
	}

	/**
	 * TimeTest::testDay()
	 *
	 * @return void
	 */
	public function testDayName() {
		//$this->out($this->_header(__FUNCTION__), true);
		$ret = $this->Time->dayName('0');
		$this->assertEquals(__d('tools', 'Sunday'), $ret);

		$ret = $this->Time->dayName(2, true);
		$this->assertEquals(__d('tools', 'Tue'), $ret);

		$ret = $this->Time->dayName(6);
		$this->assertEquals(__d('tools', 'Saturday'), $ret);

		$ret = $this->Time->dayName(6, false, 1);
		$this->assertEquals(__d('tools', 'Sunday'), $ret);

		$ret = $this->Time->dayName(0, false, 2);
		$this->assertEquals(__d('tools', 'Tuesday'), $ret);

		$ret = $this->Time->dayName(1, false, 6);
		$this->assertEquals(__d('tools', 'Sunday'), $ret);
	}

	/**
	 * TimeTest::testMonth()
	 *
	 * @return void
	 */
	public function testMonthName() {
		//$this->out($this->_header(__FUNCTION__), true);
		$ret = $this->Time->monthName('11');
		$this->assertEquals(__d('tools', 'November'), $ret);

		$ret = $this->Time->monthName(1);
		$this->assertEquals(__d('tools', 'January'), $ret);

		$ret = $this->Time->monthName(2, true, ['appendDot' => true]);
		$this->assertEquals(__d('tools', 'Feb') . '.', $ret);

		$ret = $this->Time->monthName(5, true, ['appendDot' => true]);
		$this->assertEquals(__d('tools', 'May'), $ret);
	}

	/**
	 * TimeTest::testDays()
	 *
	 * @return void
	 */
	public function testDayNames() {
		//$this->out($this->_header(__FUNCTION__), true);
		$ret = $this->Time->dayNames();
		$this->assertTrue(count($ret) === 7);
	}

	/**
	 * TimeTest::testMonths()
	 *
	 * @return void
	 */
	public function testMonthNames() {
		//$this->out($this->_header(__FUNCTION__), true);
		$ret = $this->Time->monthNames();
		$this->assertTrue(count($ret) === 12);
	}

	/**
	 * TimeTest::testRelLengthOfTime()
	 *
	 * @return void
	 */
	public function testRelLengthOfTime() {
		$ret = $this->Time->relLengthOfTime('1990-11-20');
		//pr($ret);

		$ret = $this->Time->relLengthOfTime('2012-11-20');
		//pr($ret);

		$res = $this->Time->relLengthOfTime(date(FORMAT_DB_DATETIME, time() - 3600));
		//pr($res);
		$this->assertTrue(!empty($res));

		$res = $this->Time->relLengthOfTime(date(FORMAT_DB_DATETIME, time() - 4 * DAY - 5 * HOUR), null, ['plural' => 'n']);
		//pr($res);
		//$this->assertEquals($res, 'Vor 4 Tagen, 5 '.__d('tools', 'Hours'));
		$this->assertEquals(__d('tools', '{0} ago', '4 ' . __d('tools', 'Days') . ', ' . '5 ' . __d('tools', 'Hours')), $res);

		$res = $this->Time->relLengthOfTime(date(FORMAT_DB_DATETIME, time() + 4 * DAY + 5 * HOUR), null, ['plural' => 'n']);
		//pr($res);
		$this->assertEquals(__d('tools', 'In {0}', '4 ' . __d('tools', 'Days') . ', ' . '5 ' . __d('tools', 'Hours')), $res);

		$res = $this->Time->relLengthOfTime(date(FORMAT_DB_DATETIME, time()), null, ['plural' => 'n']);
		//pr($res);
		$this->assertEquals($res, __d('tools', 'justNow'));
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
		$this->assertEquals('01/' . $year, $this->Time->cweek($year . '-' . $month . '-' . $day));

		$year = 2009;
		$month = 1;
		$day = 1;
		$date = mktime(0, 0, 0, $month, $day, $year);
		$this->assertEquals('01/' . $year, $this->Time->cweek($year . '-' . $month . '-' . $day));

		$year = 2009;
		$month = 1;
		$day = 9;
		$date = mktime(0, 0, 0, $month, $day, $year);
		$this->assertEquals('02/' . $year, $this->Time->cweek($year . '-' . $month . '-' . $day . ' 00:00:00'));

		$year = 2009;
		$month = 12;
		$day = 26;
		$date = mktime(0, 0, 0, $month, $day, $year);
		$this->assertEquals('52/' . $year, $this->Time->cweek($year . '-' . $month . '-' . $day));
	}

	/**
	 * Test IsInTheFuture
	 *
	 * @return void
	 */
	public function testIsInTheFuture() {
		$testDate = date(FORMAT_DB_DATE, time() + 2 * DAY);
		$is = $this->Time->isInTheFuture($testDate);
		$this->assertTrue($is);

		$testDate = date(FORMAT_DB_DATETIME, time() - 1 * MINUTE);
		$is = $this->Time->isInTheFuture($testDate);
		$this->assertFalse($is);
	}

	/**
	 * Test IsNotTodayAndInTheFuture
	 *
	 * @return void
	 */
	public function testIsNotTodayAndInTheFuture() {
		$testDate = date(FORMAT_DB_DATE, time());
		$is = $this->Time->isNotTodayAndInTheFuture($testDate);
		$this->assertFalse($is);

		$testDate = date(FORMAT_DB_DATETIME, time() + 1 * DAY);
		$is = $this->Time->isNotTodayAndInTheFuture($testDate);
		$this->assertTrue($is);
	}

	/**
	 * Test IsDayAfterTomorrow
	 *
	 * @return void
	 */
	public function testIsDayAfterTomorrow() {
		$testDate = new Time(time() + 2 * DAY);
		$is = $this->Time->isDayAfterTomorrow($testDate);
		$this->assertTrue($is);

		$testDate = new Time(time() - 1 * MINUTE);
		$is = $this->Time->isDayAfterTomorrow($testDate);
		$this->assertFalse($is);
	}

	/**
	 * @return void
	 */
	public function testLengthOfTime() {
		$ret = $this->Time->lengthOfTime(60, 'Hi');
		$this->assertEquals('1 Minutes', $ret);

		$ret = $this->Time->lengthOfTime(DAY);
		$this->assertEquals('1 Days, 0 Hours', $ret);

		// FIX ME! Doesn't work!
		$ret = $this->Time->lengthOfTime(-60);
		//$this->assertEquals('-60 Seconds', $ret);

		$this->assertEquals('6 ' . __d('tools', 'Minutes') . ', 40 ' . __d('tools', 'Seconds'), $this->Time->lengthOfTime(400));

		$res = $this->Time->lengthOfTime(400, 'i');
		$this->assertEquals('6 ' . __d('tools', 'Minutes'), $res);

		$res = $this->Time->lengthOfTime(6 * DAY);
		$this->assertEquals('6 ' . __d('tools', 'Days') . ', 0 ' . __d('tools', 'Hours'), $res);
	}

	/**
	 * @return void
	 */
	public function testFuzzyFromOffset() {
		$ret = $this->Time->fuzzyFromOffset(MONTH);
		$this->assertSame('about a month', $ret);

		$ret = $this->Time->fuzzyFromOffset(120);
		$this->assertSame('a few minutes', $ret);

		$ret = $this->Time->fuzzyFromOffset(DAY);
		$this->assertSame('about a day', $ret);

		$ret = $this->Time->fuzzyFromOffset(DAY + 2 * MINUTE);
		$this->assertSame('about a day', $ret);

		// FIX ME! Doesn't work!
		$ret = $this->Time->fuzzyFromOffset(-DAY);
		//dd($ret);
	}

	/**
	 * @return void
	 */
	public function testCweekMod() {
		$result = $this->Time->cWeekMod(0);
		$this->assertEquals(0, $result);

		$result = $this->Time->cWeekMod(1);
		$this->assertEquals(1, $result);

		$result = $this->Time->cWeekMod(6);
		$this->assertEquals(0, $result);
	}

	/**
	 * @return void
	 */
	public function testCweeks() {
		//$this->out($this->_header(__FUNCTION__), true);
		$ret = $this->Time->cweeks('2004');
		$this->assertEquals(53, $ret);

		$ret = $this->Time->cweeks('2010');
		$this->assertEquals(52, $ret);

		$ret = $this->Time->cweeks('2006');
		$this->assertEquals(52, $ret);

		$ret = $this->Time->cweeks('2007');
		$this->assertEquals(52, $ret);
		/*
		for ($i = 1990; $i < 2020; $i++) {
			//$this->out($this->Time->cweeks($i).BR;
		}
		*/
	}

	/**
	 * @return void
	 */
	public function testCweekBeginning() {
		//$this->out($this->_header(__FUNCTION__), true);
		$values = [
			'2001' => 978303600, # Mon 01.01.2001, 00:00
			'2006' => 1136156400, # Mon 02.01.2006, 00:00
			'2010' => 1262559600, # Mon 04.01.2010, 00:00
			'2013' => 1356908400, # Mon 31.12.2012, 00:00
		];
		foreach ($values as $year => $expected) {
			$ret = $this->Time->cweekBeginning($year);
			//$this->out($ret);
			//$this->out($this->Time->niceDate($ret, 'D') . ' ' . $this->Time->niceDate($ret, FORMAT_NICE_YMDHMS));
			//$this->assertEquals($ret, $expected, null, $year);
			$this->assertTrue($ret <= $expected + HOUR && $ret >= $expected);
		}

		$values = [
			['2001', '1', 978303600], # Mon 01.01.2001, 00:00:00
			['2001', '2', 978908400], # Mon 08.01.2001, 00:00:00
			['2001', '5', 980722800], # Mon 29.01.2001, 00:00:00
			['2001', '52', 1009148400], # Mon 24.12.2001, 00:00:00
			['2013', '11', 1362956400], # Mon 11.03.2013, 00:00:00
			['2006', '3', 1137366000], # Mon 16.01.2006, 00:00:00
		];
		foreach ($values as $v) {
			$ret = $this->Time->cweekBeginning($v[0], $v[1]);
			//$this->out($ret);
			//$this->out($this->Time->niceDate($ret, 'D') . ' ' . $this->Time->niceDate($ret, FORMAT_NICE_YMDHMS));
			//$this->assertSame($v[2], $ret, null, $v[1].'/'.$v[0]);
			$this->assertTrue($ret <= $v[2] + HOUR && $ret >= $v[2]);
		}
	}

	/**
	 * @return void
	 */
	public function testCweekEnding() {
		//$this->out($this->_header(__FUNCTION__), true);

		$values = [
			'2001' => 1009753199, # Sun 30.12.2001, 23:59:59
			'2006' => 1167605999, # Sun 31.12.2006, 23:59:59
			'2010' => 1294009199, # Sun 02.01.2011, 23:59:59
			'2013' => 1388357999, # Sun 29.12.2013, 23:59:59
		];
		foreach ($values as $year => $expected) {
			$ret = $this->Time->cweekEnding($year);
			//$this->out($ret);
			//$this->out($this->Time->niceDate($ret, 'D') . ' ' . $this->Time->niceDate($ret, FORMAT_NICE_YMDHMS));
			//$this->assertSame($expected, $ret);
			$this->assertTrue($ret <= $expected + HOUR && $ret >= $expected);
		}

		$values = [
			['2001', '1', 978908399], # Sun 07.01.2001, 23:59:59
			['2001', '2', 979513199], # Sun 14.01.2001, 23:59:59
			['2001', '5', 981327599], # Sun 04.02.2001, 23:59:59
			['2001', '52', 1009753199], # Sun 30.12.2001, 23:59:59
			['2013', '11', 1363561199], # Sun 17.03.2013, 23:59:59
			['2006', '3', 1137970799], # Sun 22.01.2006, 23:59:59
		];
		foreach ($values as $v) {
			$ret = $this->Time->cweekEnding($v[0], $v[1]);
			//$this->out($ret);
			//$this->out($this->Time->niceDate($ret, 'D') . ' ' . $this->Time->niceDate($ret, FORMAT_NICE_YMDHMS));
			//$this->assertSame($v[2], $ret, null, $v[1].'/'.$v[0]);
			$this->assertTrue($ret <= $v[2] + HOUR && $ret >= $v[2]);
		}
	}

	/**
	 * TimeTest::testAgeRange()
	 *
	 * @return void
	 */
	public function testAgeRange() {
		$is = $this->Time->ageRange(2000);
		$this->assertEquals(date('Y') - 2000 - 1, $is);

		$is = $this->Time->ageRange(date('Y') - 11, null, null, 5);
		$this->assertEquals([6, 10], $is);

		$is = $this->Time->ageRange(date('Y') - 13, null, null, 5);
		$this->assertEquals([11, 15], $is);

		$is = $this->Time->ageRange(1985, 23, 11);
		$this->assertEquals(date('Y') - 1985 - 1, $is);

		$is = $this->Time->ageRange(date('Y') - 29, null, null, 6);
		$this->assertEquals([25, 30], $is);

		$is = $this->Time->ageRange(date('Y') - 29, 21, 11, 7);
		$this->assertEquals([22, 28], $is);
	}

	/**
	 * TimeTest::testParseDate()
	 *
	 * @return void
	 */
	public function testParseLocalDate() {
		//echo $this->_header(__FUNCTION__);
		$tests = [
			'2010-12-11' => 1292022000,
			'2010-01-02' => 1262386800,
			'10-01-02' => 1262386800,
			'2.1.2010' => 1262386800,
			'2.1.10' => 1262386800,
			'02.01.10' => 1262386800,
			'02.01.2010' => 1262386800,
			'02.01.2010 22:11' => 1262386800,
			'2010-01-02 22:11' => 1262386800,
		];
		foreach ($tests as $was => $expected) {
			$is = $this->Time->parseLocalDate($was);
			$this->assertTrue($is <= $expected + HOUR && $is >= $expected);
		}
	}

	/**
	 * TimeTest::testParseTime()
	 *
	 * @return void
	 */
	public function testParseLocalTime() {
		//echo $this->_header(__FUNCTION__);
		$tests = [
			'2:4' => 7440,
			'2:04' => 7440,
			'2' => 7200,
			'1,5' => 3600 + 1800,
			'1.5' => 3600 + 1800,
			'1.50' => 3600 + 1800,
			'1.01' => 3660,
			':4' => 240,
			':04' => 240,
			':40' => 40 * MINUTE,
			'1:2:4' => 1 * HOUR + 2 * MINUTE + 4 * SECOND,
			'01:2:04' => 1 * HOUR + 2 * MINUTE + 4 * SECOND,
			'0:2:04' => 2 * MINUTE + 4 * SECOND,
			'::4' => 4 * SECOND,
			'::04' => 4 * SECOND,
			'::40' => 40 * SECOND,
			'2011-11-12 10:10:10' => 10 * HOUR + 10 * MINUTE + 10 * SECOND,
		];

		// positive
		foreach ($tests as $was => $expected) {
			$is = $this->Time->parseLocalTime($was);
			//pr($is);
			$this->assertEquals($expected, $is);
		}

		unset($tests['2011-11-12 10:10:10']);
		// negative
		foreach ($tests as $was => $expected) {
			$is = $this->Time->parseLocalTime('-' . $was);
			//pr($is);
			$this->assertEquals(-$expected, $is);
		}
	}

	/**
	 * TimeTest::testBuildTime()
	 *
	 * @return void
	 */
	public function testDuration() {
		$tests = [
			7440 => '2:04:00',
			7220 => '2:00:20',
			5400 => '1:30:00',
			3660 => '1:01:00',
		];

		// positive
		foreach ($tests as $was => $expected) {
			$is = $this->Time->duration($was);
			//pr($is);
			$this->assertEquals($expected, $is);
		}

		// M:SS
		$tests = [
			7440 => '124:00',
		];
		foreach ($tests as $was => $expected) {
			$is = $this->Time->duration($was, '%i:%S');
			//pr($is);
			$this->assertEquals($expected, $is);
		}
	}

	/**
	 * TimeTest::testBuildTime()
	 *
	 * @return void
	 */
	public function testBuildTime() {
		//echo $this->_header(__FUNCTION__);
		$tests = [
			7440 => '2:04:00',
			7220 => '2:00:20',
			5400 => '1:30:00',
			3660 => '1:01:00',
			43202 => '12:00:02',
		];

		// positive
		foreach ($tests as $was => $expected) {
			$is = $this->Time->buildTime($was);
			//pr($is);
			$this->assertEquals($expected, $is);
		}

		// negative
		foreach ($tests as $was => $expected) {
			$is = $this->Time->buildTime(-$was);
			//pr($is);
			$this->assertEquals('-' . $expected, $is);
		}

		$tests = [
			7220 => '2:00', # 02:00:20 => rounded to 2:00:00
			7441 => '2:04',
			43202 => '12:00',
		];

		// positive
		foreach ($tests as $was => $expected) {
			$is = $this->Time->buildTime($was, 'H:MM');
			//pr($is);
			$this->assertEquals($expected, $is);
		}

		// using DateTime interval
		$datetime1 = new DateTime('2009-10-11 13:13:13');
		$datetime2 = new DateTime('2009-10-12 13:13:15');
		$interval = $datetime1->diff($datetime2);
		$result = $this->Time->buildTime($interval, 'H:MM:SS');
		$this->assertEquals('24:00:02', $result);

		// using Time/Carbon
		$start = new Time();
		$end = (new Time())->addMinutes(3);
		$diff = $end->diffInSeconds($start);
		$result = $this->Time->buildTime($diff, 'H:MM:SS');
		$this->assertEquals('0:03:00', $result);

		$result = $this->Time->buildTime($diff, 'M:SS');
		$this->assertEquals('3:00', $result);
	}

	/**
	 * TimeTest::testBuildDefaultTime()
	 *
	 * @return void
	 */
	public function testBuildDefaultTime() {
		//echo $this->_header(__FUNCTION__);
		$tests = [
			7440 => '02:04:00',
			7220 => '02:00:20',
			5400 => '01:30:00',
			3660 => '01:01:00',
			1 * HOUR + 2 * MINUTE + 4 * SECOND => '01:02:04',
		];

		foreach ($tests as $was => $expected) {
			$is = $this->Time->buildDefaultTime($was);
			//pr($is);
			$this->assertEquals($expected, $is);
		}
	}

	/**
	 * 9.30 => 9.50
	 *
	 * @return void
	 */
	public function testStandardDecimal() {
		//echo $this->_header(__FUNCTION__);
		$value = '9.30';
		$is = $this->Time->standardToDecimalTime($value);
		$this->assertEquals('9.50', round($is, 2));

		$value = '9.3';
		$is = $this->Time->standardToDecimalTime($value);
		$this->assertEquals('9.50', round($is, 2));
	}

	/**
	 * 9.50 => 9.30
	 *
	 * @return void
	 */
	public function testDecimalStandard() {
		//echo $this->_header(__FUNCTION__);
		$value = '9.50';
		$is = $this->Time->decimalToStandardTime($value);
		$this->assertEquals('9.3', round($is, 2));

		$value = '9.5';
		$is = $this->Time->decimalToStandardTime($value);
		//pr($is);
		$this->assertEquals('9.3', $is);

		$is = $this->Time->decimalToStandardTime($value, 2);
		//pr($is);
		$this->assertEquals('9.30', $is);

		$is = $this->Time->decimalToStandardTime($value, 2, ':');
		//pr($is);
		$this->assertEquals('9:30', $is);
	}

	/**
	 * TimeTest::testHasDaylightSavingTime()
	 *
	 * @return void
	 */
	public function testHasDaylightSavingTime() {
		$timezone = 'Europe/Berlin';
		$x = $this->Time->hasDaylightSavingTime($timezone);
		$this->assertTrue($x);

		$timezone = 'Asia/Baghdad';
		$x = $this->Time->hasDaylightSavingTime($timezone);
		$this->assertFalse($x);
	}

	/**
	 * TimeTest::testTimezone()
	 *
	 * @return void
	 */
	public function testGetTimezone() {
		$timezone = $this->Time->getTimezone();
		// usually UTC
		$name = $timezone->getName();
		//$this->debug($name);
		$this->assertTrue(!empty($name));

		$location = $timezone->getLocation();
		//$this->debug($location);
		$this->assertTrue(!empty($location['country_code']));
		$this->assertTrue(isset($location['latitude']));
		$this->assertTrue(isset($location['longitude']));

		$offset = $timezone->getOffset(new DateTime('@' . mktime(0, 0, 0, 1, 1, date('Y'))));
		//$this->debug($offset);

		$phpTimezone = date_default_timezone_get();
		$this->assertEquals($name, $phpTimezone);
	}

}
