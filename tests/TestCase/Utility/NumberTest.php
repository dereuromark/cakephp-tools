<?php

App::uses('NumberLib', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class NumberLibTest extends MyCakeTestCase {

	public $NumberLib = null;

	public function setUp() {
		parent::setUp();

		Configure::write('Localization', array(
			'decimals' => ',',
			'thousands' => '.'
		));
		NumberLib::config();
	}

	/**
	 * NumberLibTest::testAverage()
	 *
	 * @return void
	 */
	public function testAverage() {
		$array = array();
		$is = NumberLib::average($array);
		$expected = 0.0;
		$this->assertSame($expected, $is);

		$array = array(3, 8, 4);
		$is = NumberLib::average($array);
		$expected = 5.0;
		$this->assertSame($expected, $is);

		$array = array(0.0, 3.8);
		$is = NumberLib::average($array);
		$expected = 2.0;
		$this->assertSame($expected, $is);

		$array = array(0.0, 3.7);
		$is = NumberLib::average($array, 1);
		$expected = 1.9;
		$this->assertSame($expected, $is);

		$array = array(0.0, 3.7);
		$is = NumberLib::average($array, 2);
		$expected = 1.85;
		$this->assertSame($expected, $is);
	}

	/**
	 * NumberLibTest::testMoney()
	 *
	 * @return void
	 */
	public function testMoney() {
		$is = NumberLib::money(22.11);
		$expected = '22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::money(-22.11);
		$expected = '-22,11 €';
		$this->assertSame($expected, $is);
	}

	/**
	 * NumberLibTest::testPrice()
	 *
	 * @return void
	 */
	public function testPrice() {
		$is = NumberLib::price(22.11);
		$expected = '22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::price(-22.11);
		$expected = '0,00 €';
		$this->assertSame($expected, $is);
	}

	/**
	 * NumberLibTest::testCurrency()
	 *
	 * @return void
	 */
	public function testCurrency() {
		$is = NumberLib::currency(22.11);
		$expected = '22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::currency(-22.11);
		$expected = '-22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::currency(-22.11, 'EUR', array('signed' => true));
		$expected = '-22,11 €';
		$this->assertSame($expected, $is);

		$is = NumberLib::currency(22.11, 'EUR', array('signed' => true));
		$expected = '+22,11 €';
		$this->assertSame($expected, $is);
	}

	/**
	 * NumberLibTest::testFormat()
	 *
	 * @return void
	 */
	public function testFormat() {
		$is = NumberLib::format(22.11);
		$expected = '22,11';
		$this->assertSame($expected, $is);

		$is = NumberLib::format(22933773);
		$expected = '22.933.773,00';
		$this->assertSame($expected, $is);

		$is = NumberLib::format(-0.895, array('places' => 3));
		$expected = '-0,895';
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testToPercentage() {
		$is = NumberLib::toPercentage(22.11, 2, array('decimals' => '.'));
		$expected = '22.11%';
		$this->assertSame($expected, $is);

		$is = NumberLib::toPercentage(22.11, 2, array('decimals' => ','));
		$expected = '22,11%';
		$this->assertSame($expected, $is);

		$is = NumberLib::toPercentage(22.11, 0, array('decimals' => '.'));
		$expected = '22%';
		$this->assertSame($expected, $is);

		$is = NumberLib::toPercentage(0.2311, 0, array('multiply' => true, 'decimals' => '.'));
		$expected = '23%';
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
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

	/**
	 * Test spacer format options for currency() method
	 *
	 * @return void
	 */
	public function testCurrencySpacer() {
		if ((float)Configure::version() < 2.4) {
			$format = NumberLib::getFormat('GBP');
			$format['wholeSymbol'] = '£';
			NumberLib::addFormat('GBP', $format);
		}

		$result = NumberLib::currency('4.111', 'GBP');
		$expected = '£4.11';
		$this->assertEquals($expected, $result);

		$result = NumberLib::currency('4.111', 'GBP', array('spacer' => false));
		$expected = '£4.11';
		$this->assertEquals($expected, $result);

		$result = NumberLib::currency('4.111', 'GBP', array('spacer' => true));
		$expected = '£ 4.11';
		$this->assertEquals($expected, $result);

		$result = NumberLib::currency('-4.111', 'GBP', array('spacer' => false, 'negative' => '-'));
		$expected = '-£4.11';
		$this->assertEquals($expected, $result);

		$result = NumberLib::currency('-4.111', 'GBP', array('spacer' => true, 'negative' => '-'));
		$expected = '-£ 4.11';
		$this->assertEquals($expected, $result);

		$result = NumberLib::currency('4.111', 'GBP', array('spacer' => '&nbsp;', 'escape' => false));
		$expected = '£&nbsp;4.11';
		$this->assertEquals($expected, $result);
	}

	/**
	 * NumberLibTest::testCurrencyUnknown()
	 *
	 * @return void
	 */
	public function testCurrencyUnknown() {
		$result = NumberLib::currency('4.111', 'XYZ');
		$expected = 'XYZ 4,11';
		$this->assertEquals($expected, $result);
	}

}
