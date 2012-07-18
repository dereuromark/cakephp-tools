<?php

App::uses('TimeLib', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.Lib');

class TimeLibTest extends MyCakeTestCase {

	public $TimeLib = null;

	public function startTest() {
		//$this->TimeLib = new TimeLib();
	}


	public function testParseDate() {
		echo $this->_header(__FUNCTION__);
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
			//pr($is);
			pr(date(FORMAT_NICE_YMDHMS, $is));
			$this->assertSame($expected, $is); //, null, $was
		}
	}


	public function testParseTime() {
		echo $this->_header(__FUNCTION__);
		$tests = array(
			'2:4' => 7440,
			'2:04' => 7440,
			'2' => 7200,
			'1,5' => 3600+1800,
			'1.5' => 3600+1800,
			'1.50' => 3600+1800,
			'1.01' => 3660,
			':4' => 240,
			':04' => 240,
			':40' => 40*MINUTE,
			'1:2:4' => 1*HOUR+2*MINUTE+4*SECOND,
			'01:2:04' => 1*HOUR+2*MINUTE+4*SECOND,
			'0:2:04' => 2*MINUTE+4*SECOND,
			'::4' => 4*SECOND,
			'::04' => 4*SECOND,
			'::40' => 40*SECOND,
			'2011-11-12 10:10:10' => 10*HOUR+10*MINUTE+10*SECOND,
		);

		# positive
		foreach ($tests as $was => $expected) {
			$is = TimeLib::parseTime($was);
			//pr($is);
			$this->assertEquals($expected, $is); //null, $was
		}

		unset($tests['2011-11-12 10:10:10']);
		# negative
		foreach ($tests as $was => $expected) {
			$is = TimeLib::parseTime('-'.$was);
			//pr($is);
			$this->assertEquals($is, -$expected); //, null, '-'.$was.' ['.$is.' => '.(-$expected).']'
		}
	}

	public function testBuildTime() {
		echo $this->_header(__FUNCTION__);
		$tests = array(
			7440 => '2:04',
			7220 => '2:00', # 02:00:20 => rounded to 2:00:00
			5400 => '1:30',
			3660 => '1:01',
		);

		# positive
		foreach ($tests as $was => $expected) {
			$is = TimeLib::buildTime($was);
			pr($is);
			$this->assertEquals($expected, $is);
		}

		# negative
		foreach ($tests as $was => $expected) {
			$is = TimeLib::buildTime(-$was);
			pr($is);
			$this->assertEquals($is, '-'.$expected);
		}
	}

	public function testBuildDefaultTime() {
		echo $this->_header(__FUNCTION__);
		$tests = array(
			7440 => '02:04:00',
			7220 => '02:00:20',
			5400 => '01:30:00',
			3660 => '01:01:00',
			1*HOUR+2*MINUTE+4*SECOND => '01:02:04',
		);

		foreach ($tests as $was => $expected) {
			$is = TimeLib::buildDefaultTime($was);
			pr($is);
			$this->assertEquals($expected, $is);
		}
	}

	/**
	 * basic
	 */
	public function testStandardDecimal() {
		echo $this->_header(__FUNCTION__);
		$value = '9.30';
		$is = TimeLib::standardToDecimalTime($value);
		$this->assertEquals(round($is, 2), '9.50');

		$value = '9.3';
		$is = TimeLib::standardToDecimalTime($value);
		$this->assertEquals(round($is, 2), '9.50');
	}


	public function testDecimalStandard() {
		echo $this->_header(__FUNCTION__);
		$value = '9.50';
		$is = TimeLib::decimalToStandardTime($value);
		$this->assertEquals(round($is, 2), '9.3');

		$value = '9.5';
		$is = TimeLib::decimalToStandardTime($value);
		pr($is);
		$this->assertEquals($is, '9.3');

		$is = TimeLib::decimalToStandardTime($value, 2);
		pr($is);
		$this->assertEquals($is, '9.30');

		$is = TimeLib::decimalToStandardTime($value, 2, ':');
		pr($is);
		$this->assertEquals($is, '9:30');
	}


}