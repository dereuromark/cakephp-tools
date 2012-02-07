<?php

App::uses('NumberLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.Lib');

class NumberLibTest extends MyCakeTestCase {

	public $NumberLib = null;

	public function startTest() {
		//$this->NumberLib = new NumberLib();
	}

	/**
	 *2011-04-14 lb
	 */
	public function testRoundTo() {
		//increment = 10
		$values = array(
			'22' => 20,
			'15' => 20,
			'3.4' => 0,
			'6' => 10,
			'-3.12' => 0,
			'-10' => -10
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundTo($was, 10);
			$this->assertEquals($is, $expected, null, $was);
		}
		//increment = 0.1
		$values2 = array(
			'22' => 22,
			'15.234' => 15.2,
			'3.4' => 3.4,
			'6.131' => 6.1,
			'-3.17' => -3.2,
			'-10.99' => -11
		);
		foreach ($values2 as $was => $expected) {
			$is = NumberLib::roundTo($was, 0.1);
			$this->assertEquals($is, $expected, null, $was);
		}
	}

	/**
	 *2011-04-14 lb
	 */
	public function testRoundUpTo() {
		//increment = 10
		$values = array(
			'22.765' => 30,
			'15.22' => 20,
			'3.4' => 10,
			'6' => 10,
			'-3.12' => 0,
			'-10' => -10
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundUpTo($was, 10);
			$this->assertEquals($is, $expected, null, $was);
		}
		//increment = 5
		$values = array(
			'22' => 25,
			'15.234' => 20,
			'3.4' => 5,
			'6.131' => 10,
			'-3.17' => 0,
			'-10.99' => -10
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundUpTo($was, 5);
			$this->assertEquals($is, $expected, null, $was);
		}
	}


	/**
	 *2011-04-14 lb
	 */
	public function testRoundDownTo() {
		//increment = 10
		$values = array(
			'22.765' => 20,
			'15.22' => 10,
			'3.4' => 0,
			'6' => 0,
			'-3.12' => -10,
			'-10' => -10
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundDownTo($was, 10);
			$this->assertEquals($is, $expected, null, $was);
		}
		//increment = 3
		$values = array(
			'22' => 21,
			'15.234' => 15,
			'3.4' => 3,
			'6.131' => 6,
			'-3.17' => -6,
			'-10.99' => -12
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundDownTo($was, 3);
			$this->assertEquals($is, $expected, null, $was);
		}
	}

	/**
	 *2011-04-15 lb
	 */
	public function testGetDecimalPlaces() {
		$values = array(
			'100' => -2,
			'0.0001' => 4,
			'10' => -1,
			'0.1' => 1,
			'1' => 0,
			'0.001' => 3
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::getDecimalPlaces($was, 10);
			$this->assertEquals($is, $expected, null, $was);
		}
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
			$is = NumberLib::parseDate($was);
			//pr($is);
			pr(date(FORMAT_NICE_YMDHMS, $is));
			$this->assertEquals($is, $expected, null, $was);
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
			$is = NumberLib::parseTime($was);
			//pr($is);
			$this->assertEquals($is, $expected, null, $was);
		}

		unset($tests['2011-11-12 10:10:10']);
		# negative
		foreach ($tests as $was => $expected) {
			$is = NumberLib::parseTime('-'.$was);
			//pr($is);
			$this->assertEquals($is, -$expected, null, '-'.$was.' ['.$is.' => '.(-$expected).']');
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
			$is = NumberLib::buildTime($was);
			pr($is);
			$this->assertEquals($is, $expected);
		}

		# negative
		foreach ($tests as $was => $expected) {
			$is = NumberLib::buildTime(-$was);
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
			$is = NumberLib::buildDefaultTime($was);
			pr($is);
			$this->assertEquals($is, $expected);
		}
	}

	/**
	 * basic
	 */
	public function testStandardDecimal() {
		echo $this->_header(__FUNCTION__);
		$value = '9.30';
		$is = NumberLib::standardToDecimalTime($value);
		$this->assertEquals(round($is, 2), '9.50');

		$value = '9.3';
		$is = NumberLib::standardToDecimalTime($value);
		$this->assertEquals(round($is, 2), '9.50');
	}


	public function testDecimalStandard() {
		echo $this->_header(__FUNCTION__);
		$value = '9.50';
		$is = NumberLib::decimalToStandardTime($value);
		$this->assertEquals(round($is, 2), '9.3');

		$value = '9.5';
		$is = NumberLib::decimalToStandardTime($value);
		pr($is);
		$this->assertEquals($is, '9.3');

		$is = NumberLib::decimalToStandardTime($value, 2);
		pr($is);
		$this->assertEquals($is, '9.30');

		$is = NumberLib::decimalToStandardTime($value, 2, ':');
		pr($is);
		$this->assertEquals($is, '9:30');
	}


}