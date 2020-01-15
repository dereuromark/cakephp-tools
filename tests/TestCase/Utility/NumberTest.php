<?php

namespace Tools\Test\TestCase\Utility;

use Shim\TestSuite\TestCase;
use Tools\Utility\Number;

class NumberTest extends TestCase {

	/**
	 * @var \Tools\Utility\Number
	 */
	protected $Number;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Number::setDefaultCurrency();
	}

	/**
	 * @return void
	 */
	public function testAverage() {
		$array = [];
		$is = Number::average($array);
		$expected = 0.0;
		$this->assertSame($expected, $is);

		$array = [3, 8, 4];
		$is = Number::average($array);
		$expected = 5.0;
		$this->assertSame($expected, $is);

		$array = [0.0, 3.8];
		$is = Number::average($array);
		$expected = 2.0;
		$this->assertSame($expected, $is);

		$array = [0.0, 3.7];
		$is = Number::average($array, 1);
		$expected = 1.9;
		$this->assertSame($expected, $is);

		$array = [0.0, 3.7];
		$is = Number::average($array, 2);
		$expected = 1.85;
		$this->assertSame($expected, $is);
	}

	/**
	 * NumberTest::testMoney()
	 *
	 * @return void
	 */
	public function testMoney() {
		Number::setDefaultCurrency('EUR');

		$is = Number::money(22.11, ['locale' => 'DE']);
		$expected = '22,11 €';

		$this->assertSame($expected, $is);

		$is = Number::money(-22.11, ['locale' => 'DE']);
		//$expected = '-22,11 €';
		$expected = '-22,11 €';
		//file_put_contents(TMP . 'x.txt', $is);
		$this->assertSame($expected, $is);

		$is = Number::money(0, ['locale' => 'DE']);
		$expected = '0,00 €';
		$this->assertSame($expected, $is);
	}

	/**
	 * NumberTest::testCurrency()
	 *
	 * @return void
	 */
	public function testCurrency() {
		Number::setDefaultCurrency('EUR');

		$is = Number::currency(22.11);
		$expected = '22,11 €';
		$this->assertSame($expected, $is);

		$is = Number::currency(22.11, null, ['useIntlCode' => true]);
		$expected = '22,11 EUR';
		$this->assertSame($expected, $is);

		$is = Number::currency(-22.11);
		$expected = '-22,11 €';
		$this->assertSame($expected, $is);

		$is = Number::currency(-22.11, null, ['signed' => true]);
		$expected = '-22,11 €';
		$this->assertSame($expected, $is);

		$is = Number::currency(22.11, null, ['signed' => true]);
		$expected = '+22,11 €';
		$this->assertSame($expected, $is);

		$result = Number::currency('4.111', 'GBP', ['locale' => 'EN', 'useIntlCode' => true]);
		$expected = 'GBP 4.11';
		$this->assertEquals($expected, $result);
	}

	/**
	 * NumberTest::testFormat()
	 *
	 * @return void
	 */
	public function testFormat() {
		$is = Number::format(22.11);
		$expected = '22,11';
		$this->assertSame($expected, $is);

		$is = Number::format(22933773);
		$expected = '22.933.773';
		$this->assertSame($expected, $is);

		$is = Number::format(22933773, ['places' => 2]);
		$expected = '22.933.773,00';
		$this->assertSame($expected, $is);

		$is = Number::format(-0.895, ['places' => 3]);
		$expected = '-0,895';
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testRoundTo() {
		//increment = 10
		$values = [
			'22' => 20,
			'15' => 20,
			'3.4' => 0,
			'6' => 10,
			'-3.12' => 0,
			'-10' => -10,
		];
		foreach ($values as $was => $expected) {
			$is = Number::roundTo($was, 10);
			$this->assertSame($expected, $is, $was);
		}
		//increment = 0.1
		$values2 = [
			'22' => 22.0,
			'15.234' => 15.2,
			'3.4' => 3.4,
			'6.131' => 6.1,
			'-3.17' => -3.2,
			'-10.99' => -11.0,
		];
		foreach ($values2 as $was => $expected) {
			$is = Number::roundTo($was, 0.1);
			$this->assertSame($expected, $is, $was);
		}
	}

	/**
	 * @return void
	 */
	public function testRoundUpTo() {
		//increment = 10
		$values = [
			'22.765' => 30.0,
			'15.22' => 20.0,
			'3.4' => 10.0,
			'6' => 10.0,
			'-3.12' => -0.0,
			'-10' => -10.0,
		];
		foreach ($values as $was => $expected) {
			$is = Number::roundUpTo($was, 10);
			$this->assertSame($expected, $is, $was);
		}
		//increment = 5
		$values = [
			'22' => 25.0,
			'15.234' => 20.0,
			'3.4' => 5.0,
			'6.131' => 10.0,
			'-3.17' => -0.0,
			'-10.99' => -10.0,
		];
		foreach ($values as $was => $expected) {
			$is = Number::roundUpTo($was, 5);
			$this->assertSame($expected, $is, $was);
		}
	}

	/**
	 * @return void
	 */
	public function testRoundDownTo() {
		//increment = 10
		$values = [
			'22.765' => 20.0,
			'15.22' => 10.0,
			'3.4' => 0.0,
			'6' => 0.0,
			'-3.12' => -10.0,
			'-10' => -10.0,
		];
		foreach ($values as $was => $expected) {
			$is = Number::roundDownTo($was, 10);
			$this->assertSame($expected, $is, $was);
		}
		//increment = 3
		$values = [
			'22' => 21.0,
			'15.234' => 15.0,
			'3.4' => 3.0,
			'6.131' => 6.0,
			'-3.17' => -6.0,
			'-10.99' => -12.0,
		];
		foreach ($values as $was => $expected) {
			$is = Number::roundDownTo($was, 3);
			$this->assertSame($expected, $is, $was);
		}
	}

	/**
	 * @return void
	 */
	public function testGetDecimalPlaces() {
		$values = [
			'100' => -2,
			'0.0001' => 4,
			'10' => -1,
			'0.1' => 1,
			'1' => 0,
			'0.001' => 3,
		];
		foreach ($values as $was => $expected) {
			$is = Number::getDecimalPlaces($was);
			$this->assertSame($expected, $is);
		}
	}

	/**
	 * NumberTest::testCurrencyUnknown()
	 *
	 * @return void
	 */
	public function testCurrencyUnknown() {
		$result = Number::currency('4.111', 'XYZ', ['locale' => 'DE']);
		$expected = '4,11 XYZ';
		file_put_contents(TMP . 'x.txt', $result);
		$this->assertEquals($expected, $result);
	}

}
