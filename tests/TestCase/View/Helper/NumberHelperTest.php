<?php

namespace Tools\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\View\View;
use Tools\TestSuite\TestCase;
use Tools\Utility\Number;
use Tools\View\Helper\NumberHelper;

/**
 * Number Test Case
 */
class NumberHelperTest extends TestCase {

	public function setUp() {
		parent::setUp();

		Configure::write('Localization', [
			'decimals' => ',',
			'thousands' => '.'
		]);
		Number::config('en_EN');
		$this->Number = new NumberHelper(new View(null));
	}

	/**
	 * Test calling Utility.Number class
	 *
	 * @return void
	 */
	public function testParentCall() {
		$result = $this->Number->average([1, 3, 5]);
		$this->assertSame(3.0, $result);
	}

	/**
	 * Test format
	 *
	 * @return void
	 */
	public function testFormat() {
		$is = $this->Number->format('22');
		$expected = '22';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.01');
		$expected = '22,01';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22', ['places' => 2]);
		$expected = '22,00';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.30', ['places' => 1]);
		$expected = '22,3';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.30', ['precision' => -1]);
		$expected = '22';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.30', ['places' => 3]);
		$expected = '22,300';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('abc', ['places' => 2]);
		$expected = '0,00';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.3', ['places' => 2, 'before' => 'EUR ']);
		$expected = 'EUR 22,30';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.3', ['places' => 2, 'after' => ' EUR']);
		$expected = '22,30 EUR';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.3', ['places' => 2, 'after' => 'x', 'before' => 'v']);
		$expected = 'v22,30x';
		$this->assertEquals($expected, $is);

		$is = $this->Number->format('22.3', ['places' => 2, 'locale' => 'en-US']);
		$expected = '22.30';
		$this->assertEquals($expected, $is);
	}

	/**
	 * NumberHelperTest::testCurrency()
	 *
	 * @return void
	 */
	public function testCurrency() {
		$is = Number::defaultCurrency();
		$this->assertEquals('EUR', $is);

		$is = $this->Number->currency(22.2);
		$this->assertEquals('22,20 €', $is);
	}

	/**
	 * NumberHelperTest::testToReadableSize()
	 *
	 * @return void
	 */
	public function testToReadableSize() {
		$is = $this->Number->toReadableSize(1206);
		$this->assertEquals('1,18 KB', $is);

		$is = $this->Number->toReadableSize(1024 * 1024 * 1024);
		$this->assertEquals('1 GB', $is);

		$is = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 2.5);
		$this->assertEquals('2,5 TB', $is);
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Number);
	}

}
