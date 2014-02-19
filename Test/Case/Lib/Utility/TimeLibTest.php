<?php

App::uses('TimeLib', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class TimeLibTest extends MyCakeTestCase {

	public $Time = null;

	/**
	 * TimeLibTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->Time = new TimeLib();
		$this->assertTrue(is_object($this->Time));
		$this->assertInstanceOf('TimeLib', $this->Time);
	}

	/**
	 * Currently only works with timezoned localized values, not with UTC!!!
	 *
	 * @return void
	 */
	public function testIncrementDate() {
		$timezone = Configure::read('Config.timezone');
		//$timezone = Datetime::timezone();
		Configure::write('Config.timezone', 'Europe/Berlin');
		$phpTimezone = date_default_timezone_get();
		date_default_timezone_set('Europe/Berlin');

		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, 0, 0);
		$this->assertSame($from, $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, 0, 1);
		$this->assertSame('2013-01-31', $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, 0, 2);
		$this->assertSame('2013-02-28', $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, 0, 4);
		$this->assertSame('2013-04-30', $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, 1, 0);
		$this->assertSame('2013-12-31', $Date->format(FORMAT_DB_DATE));

		// from leap year
		$from = '2008-02-29';
		$Date = TimeLib::incrementDate($from, 1, 0);
		$this->assertSame('2009-02-28', $Date->format(FORMAT_DB_DATE));

		// into leap year
		$from = '2007-02-28';
		$Date = TimeLib::incrementDate($from, 1, 0);
		$this->assertSame('2008-02-29', $Date->format(FORMAT_DB_DATE));

		// other direction
		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, 0, -1);
		$this->assertSame('2012-11-30', $Date->format(FORMAT_DB_DATE));

		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, -1, -1);
		$this->assertSame('2011-11-30', $Date->format(FORMAT_DB_DATE));

		// including days
		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, 0, 1, 1);
		$this->assertSame('2013-02-01', $Date->format(FORMAT_DB_DATE));

		// including days
		$from = '2012-12-31';
		$Date = TimeLib::incrementDate($from, 0, 1, 5);
		$this->assertSame('2013-02-05', $Date->format(FORMAT_DB_DATE));

		Configure::write('Config.timezone', $timezone);
		date_default_timezone_set($phpTimezone);
	}

	/**
	 * TimeLibTest::testNiceDate()
	 *
	 * @return void
	 */
	public function testNiceDate() {
		$res = setlocale(LC_TIME, 'de_DE.UTF-8', 'deu_deu');
		//$this->assertTrue(!empty($res));

		$values = array(
			array('2009-12-01 00:00:00', FORMAT_NICE_YMD, '01.12.2009'),
			array('2009-12-01 00:00:00', FORMAT_NICE_M_FULL, 'December'),
		);
		foreach ($values as $v) {
			$ret = TimeLib::niceDate($v[0], $v[1]);
			//$this->debug($ret);
			$this->assertEquals($v[2], $ret);
		}
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

		$ret = TimeLib::niceDate('2009-12-01');
		//debug($ret);
		$this->assertEquals('01.12.2009', $ret);

		$ret = TimeLib::localDate('2009-12-01');
		//debug($ret);
		$this->assertEquals('01.12.2009', $ret);
	}

	/**
	 * TimeLibTest::testLocalDate()
	 *
	 * @return void
	 */
	public function testLocalDate() {
		$this->skipIf(php_sapi_name() === 'cli', 'for now');
		$res = setlocale(LC_TIME, array('de_DE.UTF-8', 'deu_deu'));
		$this->assertTrue(!empty($res));

		$values = array(
			array('2009-12-01 00:00:00', FORMAT_LOCAL_YMD, '01.12.2009'),
			array('2009-12-01 00:00:00', FORMAT_LOCAL_M_FULL, 'Dezember'),
		);
		foreach ($values as $v) {
			$ret = TimeLib::localDate($v[0], $v[1]);
			//$this->debug($ret);
			$this->assertEquals($v[2], $ret);
		}
	}

	/**
	 * TimeLibTest::testParseLocalizedDate()
	 *
	 * @return void
	 */
	public function testParseLocalizedDate() {
		$this->out($this->_header(__FUNCTION__), true);

		$ret = TimeLib::parseLocalizedDate('15-Feb-2009', 'j-M-Y', 'start');
		//$this->debug($ret);
		$this->assertEquals('2009-02-15 00:00:00', $ret);

		// problem when not passing months or days as well - no way of knowing how exact the date was
		$ret = TimeLib::parseLocalizedDate('2009', 'Y', 'start');
		//pr($ret);
		//$this->assertEquals($ret, '2009-01-01 00:00:00');
		$ret = TimeLib::parseLocalizedDate('Feb 2009', 'M Y', 'start');
		//pr($ret);
		//$this->assertEquals($ret, '2009-02-01 00:00:00');

		$values = array(
			array(__('Today'), array(date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y'))))),
			array('2010', array('2010-01-01 00:00:00', '2010-12-31 23:59:59')),
			array('23.02.2011', array('2011-02-23 00:00:00', '2011-02-23 23:59:59')),
			array('22/02/2011', array('2011-02-22 00:00:00', '2011-02-22 23:59:59')),
			array('3/2/11', array('2011-02-03 00:00:00', '2011-02-03 23:59:59')),
			array('2/12/9', array('2009-12-02 00:00:00', '2009-12-02 23:59:59')),
			array('12/2009', array('2009-12-01 00:00:00', '2009-12-31 23:59:59')),
		);
		foreach ($values as $v) {
			$ret = TimeLib::parseLocalizedDate($v[0], null, 'start');
			//pr($ret);
			$this->assertEquals($v[1][0], $ret);

			$ret = TimeLib::parseLocalizedDate($v[0], null, 'end');
			//pr($ret);
			$this->assertEquals($v[1][1], $ret);
		}
	}

	/**
	 * TimeLibTest::testPeriod()
	 *
	 * @return void
	 */
	public function testPeriod() {
		$this->out($this->_header(__FUNCTION__), true);
		$values = array(
			array(__('Today'), array(date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y'))))),

			array('2010', array('2010-01-01 00:00:00', '2010-12-31 23:59:59')),
			array('2011-02', array('2011-02-01 00:00:00', '2011-02-28 23:59:59')),
			array('2012-02', array('2012-02-01 00:00:00', '2012-02-29 23:59:59')),
			array('2010-02-23', array('2010-02-23 00:00:00', '2010-02-23 23:59:59')),
			array('2010-02-23 bis 2010-02-26', array('2010-02-23 00:00:00', '2010-02-26 23:59:59')),
			//array('2010-02-23 11:11:11 bis 2010-02-23 11:12:01', array('2010-02-23 11:11:11', '2010-02-23 11:12:01')),
			// localized
			array('23.02.2011', array('2011-02-23 00:00:00', '2011-02-23 23:59:59')),
			array('23.2.2010 bis 26.2.2011', array('2010-02-23 00:00:00', '2011-02-26 23:59:59')),
		);

		foreach ($values as $v) {
			$ret = TimeLib::period($v[0]);
			//pr($ret);
			$this->assertEquals($v[1], $ret);

		}
	}

	/**
	 * TimeLibTest::testPeriodAsSql()
	 *
	 * @return void
	 */
	public function testPeriodAsSql() {
		$this->out($this->_header(__FUNCTION__), true);
		$values = array(
			array(__('Today'), "(Model.field >= '" . date(FORMAT_DB_DATE) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE) . " 23:59:59')"),
			array(__('Yesterday') . ' ' . __('until') . ' ' . __('Today'), "(Model.field >= '" . date(FORMAT_DB_DATE, time() - DAY) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE) . " 23:59:59')"),
			array(__('Today') . ' ' . __('until') . ' ' . __('Tomorrow'), "(Model.field >= '" . date(FORMAT_DB_DATE, time()) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE, time() + DAY) . " 23:59:59')"),
			array(__('Yesterday') . ' ' . __('until') . ' ' . __('Tomorrow'), "(Model.field >= '" . date(FORMAT_DB_DATE, time() - DAY) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE, time() + DAY) . " 23:59:59')"),
		);

		foreach ($values as $v) {
			$ret = TimeLib::periodAsSql($v[0], 'Model.field');
			//pr($v[1]);
			//pr($ret);
			$this->assertSame($v[1], $ret);

		}
	}

	/**
	 * TimeLibTest::testDifference()
	 *
	 * @return void
	 */
	public function testDifference() {
		$this->out($this->_header(__FUNCTION__), true);
		$values = array(
			array('2010-02-23 11:11:11', '2010-02-23 11:12:01', 50),
			array('2010-02-23 11:11:11', '2010-02-24 11:12:01', DAY + 50)
		);

		foreach ($values as $v) {
			$ret = TimeLib::difference($v[0], $v[1]);
			$this->assertEquals($v[2], $ret);
		}
	}

	/**
	 * TimeLibTest::testAgeBounds()
	 *
	 * @return void
	 */
	public function testAgeBounds() {
		$this->out($this->_header(__FUNCTION__), true);
		$values = array(
			array(20, 20, array('min' => '1990-07-07', 'max' => '1991-07-06')),
			array(10, 30, array('min' => '1980-07-07', 'max' => '2001-07-06')),
			array(11, 12, array('min' => '1998-07-07', 'max' => '2000-07-06'))
		);

		foreach ($values as $v) {
			//echo $v[0].'/'.$v[1];
			$ret = TimeLib::ageBounds($v[0], $v[1], true, '2011-07-06'); //TODO: relative time
			//pr($ret);
			if (isset($v[2])) {
				$this->assertSame($v[2], $ret);
				$this->assertEquals($v[0], TimeLib::age($v[2]['max'], '2011-07-06'));
				$this->assertEquals($v[1], TimeLib::age($v[2]['min'], '2011-07-06'));
			}
		}
	}

	/**
	 * TimeLibTest::testAgeByYear()
	 *
	 * @return void
	 */
	public function testAgeByYear() {
		$this->out($this->_header(__FUNCTION__), true);

		// year only
		$is = TimeLib::ageByYear(2000);
		$this->out($is);
		$this->assertEquals((date('Y') - 2001) . '/' . (date('Y') - 2000), $is);

		$is = TimeLib::ageByYear(1985);
		$this->assertEquals((date('Y') - 1986) . '/' . (date('Y') - 1985), $is);

		// with month
		if (($month = date('n') + 1) <= 12) {
			$is = TimeLib::ageByYear(2000, $month);
			$this->out($is);
			//$this->assertEquals($is, (date('Y')-2001).'/'.(date('Y')-2000), null, '2000/'.$month);
			$this->assertSame(date('Y') - 2001, $is); //null, '2000/'.$month
		}

		if (($month = date('n') - 1) >= 1) {
			$is = TimeLib::ageByYear(2000, $month);
			$this->out($is);
			//$this->assertEquals($is, (date('Y')-2001).'/'.(date('Y')-2000), null, '2000/'.$month);
			$this->assertSame(date('Y') - 2000, $is); //null, '2000/'.$month)
		}
	}

	/**
	 * TimeLibTest::testDaysInMonth()
	 *
	 * @return void
	 */
	public function testDaysInMonth() {
		$this->out($this->_header(__FUNCTION__), true);

		$ret = TimeLib::daysInMonth('2004', '3');
		$this->assertEquals(31, $ret);

		$ret = TimeLib::daysInMonth('2006', '4');
		$this->assertEquals(30, $ret);

		$ret = TimeLib::daysInMonth('2007', '2');
		$this->assertEquals(28, $ret);

		$ret = TimeLib::daysInMonth('2008', '2');
		$this->assertEquals(29, $ret);
	}

	/**
	 * TimeLibTest::testDay()
	 *
	 * @return void
	 */
	public function testDay() {
		$this->out($this->_header(__FUNCTION__), true);
		$ret = TimeLib::day('0');
		$this->assertEquals(__('Sunday'), $ret);

		$ret = TimeLib::day(2, true);
		$this->assertEquals(__('Tue'), $ret);

		$ret = TimeLib::day(6);
		$this->assertEquals(__('Saturday'), $ret);

		$ret = TimeLib::day(6, false, 1);
		$this->assertEquals(__('Sunday'), $ret);

		$ret = TimeLib::day(0, false, 2);
		$this->assertEquals(__('Tuesday'), $ret);

		$ret = TimeLib::day(1, false, 6);
		$this->assertEquals(__('Sunday'), $ret);
	}

	/**
	 * TimeLibTest::testMonth()
	 *
	 * @return void
	 */
	public function testMonth() {
		$this->out($this->_header(__FUNCTION__), true);
		$ret = TimeLib::month('11');
		$this->assertEquals(__('November'), $ret);

		$ret = TimeLib::month(1);
		$this->assertEquals(__('January'), $ret);

		$ret = TimeLib::month(2, true, array('appendDot' => true));
		$this->assertEquals(__('Feb') . '.', $ret);

		$ret = TimeLib::month(5, true, array('appendDot' => true));
		$this->assertEquals(__('May'), $ret);
	}

	/**
	 * TimeLibTest::testDays()
	 *
	 * @return void
	 */
	public function testDays() {
		$this->out($this->_header(__FUNCTION__), true);
		$ret = TimeLib::days();
		$this->assertTrue(count($ret) === 7);
	}

	/**
	 * TimeLibTest::testMonths()
	 *
	 * @return void
	 */
	public function testMonths() {
		$this->out($this->_header(__FUNCTION__), true);
		$ret = TimeLib::months();
		$this->assertTrue(count($ret) === 12);
	}

	/**
	 * TimeLibTest::testRelLengthOfTime()
	 *
	 * @return void
	 */
	public function testRelLengthOfTime() {
		$this->out($this->_header(__FUNCTION__), true);

		$ret = TimeLib::relLengthOfTime('1990-11-20');
		//pr($ret);

		$ret = TimeLib::relLengthOfTime('2012-11-20');
		//pr($ret);
	}

	/**
	 * TimeLibTest::testLengthOfTime()
	 *
	 * @return void
	 */
	public function testLengthOfTime() {
		$this->out($this->_header(__FUNCTION__), true);

		$ret = TimeLib::lengthOfTime(60);
		//pr($ret);

		// FIX ME! Doesn't work!
		$ret = TimeLib::lengthOfTime(-60);
		//pr($ret);

		$ret = TimeLib::lengthOfTime(-121);
		//pr($ret);
	}

	/**
	 * TimeLibTest::testFuzzyFromOffset()
	 *
	 * @return void
	 */
	public function testFuzzyFromOffset() {
		$this->out($this->_header(__FUNCTION__), true);

		$ret = TimeLib::fuzzyFromOffset(MONTH);
		//pr($ret);

		$ret = TimeLib::fuzzyFromOffset(120);
		//pr($ret);

		$ret = TimeLib::fuzzyFromOffset(DAY);
		//pr($ret);

		$ret = TimeLib::fuzzyFromOffset(DAY + 2 * MINUTE);
		//pr($ret);

		// FIX ME! Doesn't work!
		$ret = TimeLib::fuzzyFromOffset(-DAY);
		//pr($ret);
	}

	/**
	 * TimeLibTest::testCweekMod()
	 *
	 * @return void
	 */
	public function testCweekMod() {
		//$result = TimeLib::cWeekMod();
	}

	/**
	 * TimeLibTest::testCweekDay()
	 *
	 * @return void
	 */
	public function testCweekDay() {
		$this->out($this->_header(__FUNCTION__), true);

		// wednesday
		$ret = TimeLib::cweekDay(51, 2011, 2);
		$this->out('51, 2011, 2');
		$this->out(date(FORMAT_DB_DATETIME, $ret));
		$this->assertTrue($ret >= 1324422000 && $ret <= 1324425600);
		//$this->assertEquals(1324422000, $ret);
	}

	public function testCweeks() {
		$this->out($this->_header(__FUNCTION__), true);
		$ret = TimeLib::cweeks('2004');
		$this->assertEquals(53, $ret);

		$ret = TimeLib::cweeks('2010');
		$this->assertEquals(52, $ret);

		$ret = TimeLib::cweeks('2006');
		$this->assertEquals(52, $ret);

		$ret = TimeLib::cweeks('2007');
		$this->assertEquals(52, $ret);
		/*
		for ($i = 1990; $i < 2020; $i++) {
			$this->out(TimeLib::cweeks($i).BR;
		}
		*/
	}

	public function testCweekBeginning() {
		$this->out($this->_header(__FUNCTION__), true);
		$values = array(
			'2001' => 978303600, # Mon 01.01.2001, 00:00
			'2006' => 1136156400, # Mon 02.01.2006, 00:00
			'2010' => 1262559600, # Mon 04.01.2010, 00:00
			'2013' => 1356908400, # Mon 31.12.2012, 00:00
		);
		foreach ($values as $year => $expected) {
			$ret = TimeLib::cweekBeginning($year);
			$this->out($ret);
			$this->out(TimeLib::niceDate($ret, 'D') . ' ' . TimeLib::niceDate($ret, FORMAT_NICE_YMDHMS));
			//$this->assertEquals($ret, $expected, null, $year);
			$this->assertTrue($ret <= $expected + HOUR && $ret >= $expected);
		}

		$values = array(
			array('2001', '1', 978303600), # Mon 01.01.2001, 00:00:00
			array('2001', '2', 978908400), # Mon 08.01.2001, 00:00:00
			array('2001', '5', 980722800), # Mon 29.01.2001, 00:00:00
			array('2001', '52', 1009148400), # Mon 24.12.2001, 00:00:00
			array('2013', '11', 1362956400), # Mon 11.03.2013, 00:00:00
			array('2006', '3', 1137366000), # Mon 16.01.2006, 00:00:00
		);
		foreach ($values as $v) {
			$ret = TimeLib::cweekBeginning($v[0], $v[1]);
			$this->out($ret);
			$this->out(TimeLib::niceDate($ret, 'D') . ' ' . TimeLib::niceDate($ret, FORMAT_NICE_YMDHMS));
			//$this->assertSame($v[2], $ret, null, $v[1].'/'.$v[0]);
			$this->assertTrue($ret <= $v[2] + HOUR && $ret >= $v[2]);
		}
	}

	public function testCweekEnding() {
		$this->out($this->_header(__FUNCTION__), true);

		$values = array(
			'2001' => 1009753199, # Sun 30.12.2001, 23:59:59
			'2006' => 1167605999, # Sun 31.12.2006, 23:59:59
			'2010' => 1294009199, # Sun 02.01.2011, 23:59:59
			'2013' => 1388357999, # Sun 29.12.2013, 23:59:59
		);
		foreach ($values as $year => $expected) {
			$ret = TimeLib::cweekEnding($year);
			$this->out($ret);
			$this->out(TimeLib::niceDate($ret, 'D') . ' ' . TimeLib::niceDate($ret, FORMAT_NICE_YMDHMS));
			//$this->assertSame($expected, $ret);
			$this->assertTrue($ret <= $expected + HOUR && $ret >= $expected);
		}

		$values = array(
			array('2001', '1', 978908399), # Sun 07.01.2001, 23:59:59
			array('2001', '2', 979513199), # Sun 14.01.2001, 23:59:59
			array('2001', '5', 981327599), # Sun 04.02.2001, 23:59:59
			array('2001', '52', 1009753199), # Sun 30.12.2001, 23:59:59
			array('2013', '11', 1363561199), # Sun 17.03.2013, 23:59:59
			array('2006', '3', 1137970799), # Sun 22.01.2006, 23:59:59
		);
		foreach ($values as $v) {
			$ret = TimeLib::cweekEnding($v[0], $v[1]);
			$this->out($ret);
			$this->out(TimeLib::niceDate($ret, 'D') . ' ' . TimeLib::niceDate($ret, FORMAT_NICE_YMDHMS));
			//$this->assertSame($v[2], $ret, null, $v[1].'/'.$v[0]);
			$this->assertTrue($ret <= $v[2] + HOUR && $ret >= $v[2]);
		}
	}

	/**
	 * TimeLibTest::testAgeByHoroscop()
	 *
	 * @return void
	 */
	public function testAgeByHoroscop() {
		App::uses('ZodiacLib', 'Tools.Misc');
		$this->skipIf(php_sapi_name() === 'cli', 'Fix these tests');

		$is = TimeLib::ageByHoroscope(2000, ZodiacLib::SIGN_VIRGO);
		// between xxxx-08-24 and xxxx-09-23 the latter, otherwise the first:
		$this->assertEquals(date('Y') - 2000 - 1, $is);
		$this->assertEquals(array(date('Y') - 2000 - 1, date('Y') - 2000), $is);

		$is = TimeLib::ageByHoroscope(1991, ZodiacLib::SIGN_LIBRA);
		//pr($is);
		$this->assertEquals(date('Y') - 1991 - 1, $is);

		$is = TimeLib::ageByHoroscope(1986, ZodiacLib::SIGN_CAPRICORN);
		//pr($is);
		$this->assertEquals(array(date('Y') - 1986 - 1, date('Y') - 1986), $is);

		$is = TimeLib::ageByHoroscope(2000, ZodiacLib::SIGN_SCORPIO);
		//debug($is);
		$this->assertEquals(date('Y') - 2000 - 1, $is); //array(10, 11)
	}

	/**
	 * TimeLibTest::testAgeRange()
	 *
	 * @return void
	 */
	public function testAgeRange() {
		$is = TimeLib::ageRange(2000);
		$this->assertEquals(date('Y') - 2000 - 1, $is);

		$is = TimeLib::ageRange(date('Y') - 11, null, null, 5);
		$this->assertEquals(array(6, 10), $is);

		$is = TimeLib::ageRange(date('Y') - 13, null, null, 5);
		$this->assertEquals(array(11, 15), $is);

		$is = TimeLib::ageRange(1985, 23, 11);
		$this->assertEquals(date('Y') - 1985 - 1, $is);

		$is = TimeLib::ageRange(date('Y') - 29, null, null, 6);
		$this->assertEquals(array(25, 30), $is);

		$is = TimeLib::ageRange(date('Y') - 29, 21, 11, 7);
		$this->assertEquals(array(22, 28), $is);
	}

	/**
	 * TimeLibTest::testParseDate()
	 *
	 * @return void
	 */
	public function testParseDate() {
		//echo $this->_header(__FUNCTION__);
		$tests = array(
			'2010-12-11' => 1292022000,
			'2010-01-02' => 1262386800,
			'10-01-02' => 1262386800,
			'2.1.2010' => 1262386800,
			'2.1.10' => 1262386800,
			'02.01.10' => 1262386800,
			'02.01.2010' => 1262386800,
			'02.01.2010 22:11' => 1262386800,
			'2010-01-02 22:11' => 1262386800,
		);
		foreach ($tests as $was => $expected) {
			$is = TimeLib::parseDate($was);
			$this->assertTrue($is <= $expected + HOUR && $is >= $expected);
		}
	}

	/**
	 * TimeLibTest::testParseTime()
	 *
	 * @return void
	 */
	public function testParseTime() {
		//echo $this->_header(__FUNCTION__);
		$tests = array(
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
		);

		// positive
		foreach ($tests as $was => $expected) {
			$is = TimeLib::parseTime($was);
			//pr($is);
			$this->assertEquals($expected, $is);
		}

		unset($tests['2011-11-12 10:10:10']);
		// negative
		foreach ($tests as $was => $expected) {
			$is = TimeLib::parseTime('-' . $was);
			//pr($is);
			$this->assertEquals(-$expected, $is);
		}
	}

	/**
	 * TimeLibTest::testBuildTime()
	 *
	 * @return void
	 */
	public function testBuildTime() {
		//echo $this->_header(__FUNCTION__);
		$tests = array(
			7440 => '2:04',
			7220 => '2:00', # 02:00:20 => rounded to 2:00:00
			5400 => '1:30',
			3660 => '1:01',
		);

		// positive
		foreach ($tests as $was => $expected) {
			$is = TimeLib::buildTime($was);
			//pr($is);
			$this->assertEquals($expected, $is);
		}

		// negative
		foreach ($tests as $was => $expected) {
			$is = TimeLib::buildTime(-$was);
			//pr($is);
			$this->assertEquals('-' . $expected, $is);
		}
	}

	/**
	 * TimeLibTest::testBuildDefaultTime()
	 *
	 * @return void
	 */
	public function testBuildDefaultTime() {
		//echo $this->_header(__FUNCTION__);
		$tests = array(
			7440 => '02:04:00',
			7220 => '02:00:20',
			5400 => '01:30:00',
			3660 => '01:01:00',
			1 * HOUR + 2 * MINUTE + 4 * SECOND => '01:02:04',
		);

		foreach ($tests as $was => $expected) {
			$is = TimeLib::buildDefaultTime($was);
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
		$is = TimeLib::standardToDecimalTime($value);
		$this->assertEquals('9.50', round($is, 2));

		$value = '9.3';
		$is = TimeLib::standardToDecimalTime($value);
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
		$is = TimeLib::decimalToStandardTime($value);
		$this->assertEquals('9.3', round($is, 2));

		$value = '9.5';
		$is = TimeLib::decimalToStandardTime($value);
		//pr($is);
		$this->assertEquals('9.3', $is);

		$is = TimeLib::decimalToStandardTime($value, 2);
		//pr($is);
		$this->assertEquals('9.30', $is);

		$is = TimeLib::decimalToStandardTime($value, 2, ':');
		//pr($is);
		$this->assertEquals('9:30', $is);
	}

	/**
	 * TimeLibTest::testHasDaylightSavingTime()
	 *
	 * @return void
	 */
	public function testHasDaylightSavingTime() {
		$timezone = 'Europe/Berlin';
		$x = TimeLib::hasDaylightSavingTime($timezone);
		$this->assertTrue($x);

		$timezone = 'Asia/Baghdad';
		$x = TimeLib::hasDaylightSavingTime($timezone);
		$this->assertFalse($x);
	}

	/**
	 * TimeLibTest::testTimezone()
	 *
	 * @return void
	 */
	public function testTimezone() {
		$timezone = TimeLib::timezone();
		// usually UTC
		$name = $timezone->getName();
		$this->debug($name);
		$this->assertTrue(!empty($name));

		$location = $timezone->getLocation();
		$this->debug($location);
		$this->assertTrue(!empty($location['country_code']));
		$this->assertTrue(isset($location['latitude']));
		$this->assertTrue(isset($location['longitude']));

		$offset = $timezone->getOffset(new DateTime('@' . mktime(0, 0, 0, 1, 1, date('Y'))));
		$this->debug($offset);

		$phpTimezone = date_default_timezone_get();
		$this->assertEquals($name, $phpTimezone);
	}

}
