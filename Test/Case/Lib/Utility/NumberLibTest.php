<?php

App::uses('NumberLib', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class NumberLibTest extends MyCakeTestCase {

	public $NumberLib = null;

	public function setUp() {
		//$this->NumberLib = new NumberLib();
	}

	public function testMoney() {
		$is = NumberLib::money(22.11);
		$expected = '22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::money(-22.11);
		$expected = '-22,11 €';
		$this->assertSame($expected, $is);
	}

	public function testPrice() {
		$is = NumberLib::price(22.11);
		$expected = '22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::price(-22.11);
		$expected = '0,00 €';
		$this->assertSame($expected, $is);
	}

	public function testCurrency() {
		$is = NumberLib::currency(22.11);
		$expected = '22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::currency(-22.11);
		$expected = '-22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::currency(-22.11, 'EUR', array('signed'=>true));
		$expected = '-22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::currency(22.11, 'EUR', array('signed'=>true));
		$expected = '+22,11 €';
		$this->assertSame($expected, $is);
	}

	/**
	 * 2012-04-06 ms
	 */
	public function testToPercentage() {
		$is = NumberLib::toPercentage(22.11, 2, '.');
		$expected = '22.11%';
		$this->assertSame($expected, $is);

		$is = NumberLib::toPercentage(22.11, 2, ',');
		$expected = '22,11%';
		$this->assertSame($expected, $is);

		$is = NumberLib::toPercentage(22.11, 0, ',');
		$expected = '22%';
		$this->assertSame($expected, $is);
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
			//debug($expected); debug($is); echo BR; ob_flush();

			$this->assertSame($expected, $is, null, $was);
		}
		//increment = 0.1
		$values2 = array(
			'22' => 22.0,
			'15.234' => 15.2,
			'3.4' => 3.4,
			'6.131' => 6.1,
			'-3.17' => -3.2,
			'-10.99' => -11.0
		);
		foreach ($values2 as $was => $expected) {
			$is = NumberLib::roundTo($was, 0.1);
			$this->assertSame($expected, $is, null, $was);
		}
	}

	/**
	 *2011-04-14 lb
	 */
	public function testRoundUpTo() {
		//increment = 10
		$values = array(
			'22.765' => 30.0,
			'15.22' => 20.0,
			'3.4' => 10.0,
			'6' => 10.0,
			'-3.12' => -0.0,
			'-10' => -10.0
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundUpTo($was, 10);
			$this->assertSame($expected, $is, null, $was);
		}
		//increment = 5
		$values = array(
			'22' => 25.0,
			'15.234' => 20.0,
			'3.4' => 5.0,
			'6.131' => 10.0,
			'-3.17' => -0.0,
			'-10.99' => -10.0
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundUpTo($was, 5);
			$this->assertSame($expected, $is, null, $was);
		}
	}


	/**
	 *2011-04-14 lb
	 */
	public function testRoundDownTo() {
		//increment = 10
		$values = array(
			'22.765' => 20.0,
			'15.22' => 10.0,
			'3.4' => 0.0,
			'6' => 0.0,
			'-3.12' => -10.0,
			'-10' => -10.0
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundDownTo($was, 10);
			$this->assertSame($expected, $is, null, $was);
		}
		//increment = 3
		$values = array(
			'22' => 21.0,
			'15.234' => 15.0,
			'3.4' => 3.0,
			'6.131' => 6.0,
			'-3.17' => -6.0,
			'-10.99' => -12.0
		);
		foreach ($values as $was => $expected) {
			$is = NumberLib::roundDownTo($was, 3);
			$this->assertSame($expected, $is, null, $was);
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
			$this->assertSame($expected, $is); //, null, $was
		}
	}

}