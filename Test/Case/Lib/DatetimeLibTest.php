<?php

App::uses('DatetimeLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.Lib');

class DatetimeLibTest extends MyCakeTestCase {

	public $Datetime;

	public function setUp() {
		$this->Datetime = new DatetimeLib();
		$this->assertTrue(is_object($this->Datetime));
	}


	public function tearDown() {
		unset($this->Datetime);
	}

	public function testParse() {
		$this->out($this->_header(__FUNCTION__));
		
		$ret = $this->Datetime->parseDate('15-Feb-2009', 'j-M-Y', 'start');
		pr($ret);
		$this->assertEquals($ret, '2009-02-15 00:00:00');
		
		# problem when not passing months or days as well - no way of knowing how exact the date was
		$ret = $this->Datetime->parseDate('2009', 'Y', 'start');
		pr($ret);
		//$this->assertEquals($ret, '2009-01-01 00:00:00');
		$ret = $this->Datetime->parseDate('Feb 2009', 'M Y', 'start');
		pr($ret);
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
			$ret = $this->Datetime->parseDate($v[0], null, 'start');
			pr($ret);
			$this->assertEquals($ret, $v[1][0]);
			
			$ret = $this->Datetime->parseDate($v[0], null, 'end');
			pr($ret);
			$this->assertEquals($ret, $v[1][1]);
		}
	}


	public function testPeriod() {
		$this->out($this->_header(__FUNCTION__));
		$values = array(
			array(__('Today'), array(date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))), date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y'))))),
			
			array('2010', array('2010-01-01 00:00:00', '2010-12-31 23:59:59')),
			array('2011-02', array('2011-02-01 00:00:00', '2011-02-28 23:59:59')),
			array('2012-02', array('2012-02-01 00:00:00', '2012-02-29 23:59:59')),
			array('2010-02-23', array('2010-02-23 00:00:00', '2010-02-23 23:59:59')),
			array('2010-02-23 bis 2010-02-26', array('2010-02-23 00:00:00', '2010-02-26 23:59:59')),
			//array('2010-02-23 11:11:11 bis 2010-02-23 11:12:01', array('2010-02-23 11:11:11', '2010-02-23 11:12:01')),
			# localized
			array('23.02.2011', array('2011-02-23 00:00:00', '2011-02-23 23:59:59')),
			array('23.2.2010 bis 26.2.2011', array('2010-02-23 00:00:00', '2011-02-26 23:59:59')),
		);
		
		foreach ($values as $v) {
			$ret = $this->Datetime->period($v[0]);
			pr($ret);
			$this->assertEquals($ret, $v[1]);
			
		}
	}
	
	public function testPeriodAsSql() {
		$this->out($this->_header(__FUNCTION__));
		$values = array(
			array(__('Today'), "(Model.field >= '".date(FORMAT_DB_DATE)." 00:00:00') AND (Model.field <= '".date(FORMAT_DB_DATE)." 23:59:59')"),
			array(__('Yesterday').' '.__('until').' '.__('Today'), "(Model.field >= '".date(FORMAT_DB_DATE, time()-DAY)." 00:00:00') AND (Model.field <= '".date(FORMAT_DB_DATE)." 23:59:59')"),
			array(__('Tomorrow').' '.__('until').' '.__('The day after tomorrow'), "(Model.field >= '".date(FORMAT_DB_DATE, time()+DAY)." 00:00:00') AND (Model.field <= '".date(FORMAT_DB_DATE, time()+2*DAY)." 23:59:59')"),
		);
		
		foreach ($values as $v) {
			$ret = $this->Datetime->periodAsSql($v[0], 'Model.field');
			pr($ret);
			$this->assertEquals($ret, $v[1]);
			
		}
	}


	public function testDifference() {
		$this->out($this->_header(__FUNCTION__));
		$values = array(
			array('2010-02-23 11:11:11', '2010-02-23 11:12:01', 50),
			array('2010-02-23 11:11:11', '2010-02-24 11:12:01', DAY+50)
		);
		
		foreach ($values as $v) {
			$ret = $this->Datetime->difference($v[0], $v[1]);
			$this->assertEquals($ret, $v[2]);
		}
	}
	
	public function testAgeBounds() {
		$this->out($this->_header(__FUNCTION__));
		$values = array(
			array(20, 20, array('min'=>'1990-07-07', 'max'=>'1991-07-06')),
			array(10, 30, array('min'=>'1980-07-07', 'max'=>'2001-07-06')),
			array(11, 12, array('min'=>'1998-07-07', 'max'=>'2000-07-06'))
		);
		
		foreach ($values as $v) {
			echo $v[0].'/'.$v[1];
			$ret = $this->Datetime->ageBounds($v[0], $v[1], true, '2011-07-06'); //TODO: relative time
			pr($ret);
			if (isset($v[2])) {
				$this->assertSame($ret, $v[2]);
				pr($this->Datetime->age($v[2]['min']));
				pr($this->Datetime->age($v[2]['max']));
				$this->assertEquals($v[0], $this->Datetime->age($v[2]['max']));
				$this->assertEquals($v[1], $this->Datetime->age($v[2]['min']));
			}
		}
	}
		
	public function testAgeByYear() {
		$this->out($this->_header(__FUNCTION__));
		
		# year only
		$is = $this->Datetime->ageByYear(2000);
		$this->out($is);
		$this->assertEquals($is, (date('Y')-2001).'/'.(date('Y')-2000));
		
		$is = $this->Datetime->ageByYear(1985);
		$this->assertEquals($is, (date('Y')-1986).'/'.(date('Y')-1985));
		
		# with month
		if (($month = date('n')+1) <= 12) {
			$is = $this->Datetime->ageByYear(2000, $month);
			$this->out($is);
			//$this->assertEquals($is, (date('Y')-2001).'/'.(date('Y')-2000), null, '2000/'.$month);
			$this->assertEquals($is, (date('Y')-2001), null, '2000/'.$month);
		}
		
		if (($month = date('n')-1) >= 1) {
			$is = $this->Datetime->ageByYear(2000, $month);
			$this->out($is);
			//$this->assertEquals($is, (date('Y')-2001).'/'.(date('Y')-2000), null, '2000/'.$month);
			$this->assertEquals($is, (date('Y')-2000), null, '2000/'.$month);
		}
	}
	
	
	public function testDaysInMonth() {
		$this->out($this->_header(__FUNCTION__));
		
		$ret = $this->Datetime->daysInMonth('2004', '3');
		$this->assertEquals($ret, 31);
		
		$ret = $this->Datetime->daysInMonth('2006', '4');
		$this->assertEquals($ret, 30);
		
		$ret = $this->Datetime->daysInMonth('2007', '2');
		$this->assertEquals($ret, 28);
		
		$ret = $this->Datetime->daysInMonth('2008', '2');
		$this->assertEquals($ret, 29);	
	}	
	
	public function testDay() {
		$this->out($this->_header(__FUNCTION__));
		$ret = $this->Datetime->day('0');
		$this->assertEquals(__('Sunday'), $ret);
		
		$ret = $this->Datetime->day(2, true);
		$this->assertEquals(__('Tue'), $ret);
		
		$ret = $this->Datetime->day(6);
		$this->assertEquals(__('Saturday'), $ret);
		
		$ret = $this->Datetime->day(6, false, 1);
		$this->assertEquals(__('Sunday'), $ret);
		
		$ret = $this->Datetime->day(0, false, 2);
		$this->assertEquals(__('Tuesday'), $ret);
		
		$ret = $this->Datetime->day(1, false, 6);
		$this->assertEquals(__('Sunday'), $ret);
	}
	
	public function testMonth() {
		$this->out($this->_header(__FUNCTION__));
		$ret = $this->Datetime->month('11');
		$this->assertEquals(__('November'), $ret);
		
		$ret = $this->Datetime->month(1);
		$this->assertEquals(__('January'), $ret);
		
		$ret = $this->Datetime->month(2, true, array('appendDot'=>true));
		$this->assertEquals(__('Feb').'.', $ret);
		
		$ret = $this->Datetime->month(5, true, array('appendDot'=>true));
		$this->assertEquals(__('May'), $ret);
	}
	
	public function testDays() {
		$this->out($this->_header(__FUNCTION__));
		$ret = $this->Datetime->days();
		$this->assertTrue(count($ret) === 7);
	}
	
	public function testMonths() {
		$this->out($this->_header(__FUNCTION__));
		$ret = $this->Datetime->months();
		$this->assertTrue(count($ret) === 12);
	}
	
	
	public function testRelLengthOfTime() {
		$this->out($this->_header(__FUNCTION__));
		
		$ret = $this->Datetime->relLengthOfTime('1990-11-20');
		pr($ret);
		
		$ret = $this->Datetime->relLengthOfTime('2012-11-20');
		pr($ret);
	}
	
	public function testLengthOfTime() {
		$this->out($this->_header(__FUNCTION__));
		
		$ret = $this->Datetime->lengthOfTime(60);
		pr($ret);
		
		# FIX ME! Doesn't work!
		$ret = $this->Datetime->lengthOfTime(-60);
		pr($ret);
		
		$ret = $this->Datetime->lengthOfTime(-121);
		pr($ret);
	}
	
	public function testFuzzyFromOffset() {
		$this->out($this->_header(__FUNCTION__));
		
		$ret = $this->Datetime->fuzzyFromOffset(MONTH);
		pr($ret);
		
		$ret = $this->Datetime->fuzzyFromOffset(120);
		pr($ret);
		
		$ret = $this->Datetime->fuzzyFromOffset(DAY);
		pr($ret);
		
		$ret = $this->Datetime->fuzzyFromOffset(DAY+2*MINUTE);
		pr($ret);
		
		# FIX ME! Doesn't work!
		$ret = $this->Datetime->fuzzyFromOffset(-DAY);
		pr($ret);
	}	
	
	public function testCweekMod() {
	
	}

	public function testCweekDay() {
		$this->out($this->_header(__FUNCTION__));
		
		# wednesday
		$ret = $this->Datetime->cweekDay(51, 2011, 2);
		$this->out('51, 2011, 2');
		$this->out(date(FORMAT_DB_DATETIME, $ret));
		$this->assertEquals(1324422000, $ret);
	}
	
	public function testCweeks() {
		$this->out($this->_header(__FUNCTION__));
		$ret = $this->Datetime->cweeks('2004');
		$this->assertEquals($ret, 53);
		
		$ret = $this->Datetime->cweeks('2010');
		$this->assertEquals($ret, 52);
		
		$ret = $this->Datetime->cweeks('2006');
		$this->assertEquals($ret, 52);
		
		$ret = $this->Datetime->cweeks('2007');
		$this->assertEquals($ret, 52);
		/*
		for ($i = 1990; $i < 2020; $i++) {
			$this->out($this->Datetime->cweeks($i).BR;
		}
		*/
	}
	
	public function testCweekBeginning() {
		$this->out($this->_header(__FUNCTION__));
		$values = array(
			'2001' => 978303600, # Mon 01.01.2001, 00:00
			'2006' => 1136156400, # Mon 02.01.2006, 00:00
			'2010' => 1262559600, # Mon 04.01.2010, 00:00
			'2013' => 1356908400, # Mon 31.12.2012, 00:00
		);
		foreach ($values as $year => $expected) {
			$ret = $this->Datetime->cweekBeginning($year);
			$this->out($ret);
			$this->out($this->Datetime->niceDate($ret, 'D').' '.$this->Datetime->niceDate($ret, FORMAT_NICE_YMDHMS));
			$this->assertEquals($ret, $expected, null, $year);
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
			$ret = $this->Datetime->cweekBeginning($v[0], $v[1]);
			$this->out($ret);
			$this->out($this->Datetime->niceDate($ret, 'D').' '.$this->Datetime->niceDate($ret, FORMAT_NICE_YMDHMS));
			$this->assertEquals($ret, $v[2], null, $v[1].'/'.$v[0]);
		}
	}
	
	public function testCweekEnding() {
		$this->out($this->_header(__FUNCTION__));

		$values = array(
			'2001' => 1009753199, # Sun 30.12.2001, 23:59:59
			'2006' => 1167605999, # Sun 31.12.2006, 23:59:59
			'2010' => 1294009199, # Sun 02.01.2011, 23:59:59
			'2013' => 1388357999, # Sun 29.12.2013, 23:59:59
		);
		foreach ($values as $year => $expected) {
			$ret = $this->Datetime->cweekEnding($year);
			$this->out($ret);
			$this->out($this->Datetime->niceDate($ret, 'D').' '.$this->Datetime->niceDate($ret, FORMAT_NICE_YMDHMS));
			$this->assertEquals($ret, $expected);
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
			$ret = $this->Datetime->cweekEnding($v[0], $v[1]);
			$this->out($ret);
			$this->out($this->Datetime->niceDate($ret, 'D').' '.$this->Datetime->niceDate($ret, FORMAT_NICE_YMDHMS));
			$this->assertEquals($ret, $v[2], null, $v[1].'/'.$v[0]);
		}
	}

	public function testAgeByHoroscop() {
		App::uses('ZodiacLib', 'Tools.Misc');
		$zodiac = new ZodiacLib();
		$is = $this->Datetime->ageByHoroscope(2000, ZodiacLib::SIGN_VIRGO);
		pr($is);
		$this->assertEquals($is, 11);
		$is = $this->Datetime->ageByHoroscope(1991, ZodiacLib::SIGN_LIBRA);
		pr($is);
		$this->assertEquals($is, 20);
		$is = $this->Datetime->ageByHoroscope(1986, ZodiacLib::SIGN_CAPRICORN);
		pr($is);
		$this->assertEquals($is, array(24, 25));
		$is = $this->Datetime->ageByHoroscope(2000, ZodiacLib::SIGN_SCORPIO);
		pr($is);
		$this->assertEquals($is, array(10, 11));
	}
	
	public function testAgeRange() {
		$is = $this->Datetime->ageRange(2000);
		pr($is);
		$this->assertEquals($is, 10);
		$is = $this->Datetime->ageRange(2002, null, null, 5);
		pr($is);
		$this->assertEquals($is, array(6, 10));
		$is = $this->Datetime->ageRange(2000,  null, null, 5);
		pr($is);
		$this->assertEquals($is, array(6, 10));
		$is = $this->Datetime->ageRange(1985, 23, 11);
		pr($is);
		$this->assertEquals($is, 25);
		$is = $this->Datetime->ageRange(1985, null, null, 6);
		pr($is);
		$this->assertEquals($is, array(25, 30));
		$is = $this->Datetime->ageRange(1985,  21, 11, 7);
		pr($is);
		$this->assertEquals($is, array(22, 28));
	}
}