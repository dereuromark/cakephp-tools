<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\Utility\Number;
use Tools\View\Helper\NumberHelper;

class NumberHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\NumberHelper|\Tools\Utility\Number
	 */
	protected $Number;

	/**
	 * @var string
	 */
	protected $locale;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->locale = ini_get('intl.default_locale');
		ini_set('intl.default_locale', 'de-DE');

		Configure::write('Localization', [
			'decimals' => ',',
			'thousands' => '.',
		]);
		Number::config('de_DE');
		$this->Number = new NumberHelper(new View(null));
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		ini_set('intl.default_locale', $this->locale);

		unset($this->Number);
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
		$is = $this->Number->format(22);
		$expected = '22';
		$this->assertSame($expected, $is);

		$is = $this->Number->format(22.01);
		$expected = '22,01';
		$this->assertSame($expected, $is);

		$is = $this->Number->format(22, ['places' => 2]);
		$expected = '22,00';
		$this->assertSame($expected, $is);

		$is = $this->Number->format(22.30, ['places' => 1]);
		$expected = '22,3';
		$this->assertSame($expected, $is);

		$is = $this->Number->format('22.30', ['precision' => -1]);
		$expected = '22'; // Why 22,3 locally?
		$this->assertSame($expected, $is);

		$is = $this->Number->format(22.30, ['places' => 3]);
		$expected = '22,300';
		$this->assertSame($expected, $is);

		//$is = $this->Number->format('abc', ['places' => 2]);
		//$expected = '0,00';
		//$this->assertSame($expected, $is);

		$is = $this->Number->format(22.3, ['places' => 2, 'before' => 'EUR ']);
		$expected = 'EUR 22,30';
		$this->assertSame($expected, $is);

		$is = $this->Number->format(22.3, ['places' => 2, 'after' => ' EUR']);
		$expected = '22,30 EUR';
		$this->assertSame($expected, $is);

		$is = $this->Number->format(22.3, ['places' => 2, 'after' => 'x', 'before' => 'v']);
		$expected = 'v22,30x';
		$this->assertSame($expected, $is);

		$is = $this->Number->format(22.3, ['places' => 2, 'locale' => 'en-US']);
		$expected = '22.30';
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testCurrency() {
		$is = Number::getDefaultCurrency();
		$this->assertSame('EUR', $is);

		$is = $this->Number->currency(22.2);
		$this->assertSame('22,20 €', $is);
	}

	/**
	 * @return void
	 */
	public function testToReadableSize() {
		$is = $this->Number->toReadableSize(1206);
		$this->assertSame('1,18 KB', $is);

		$is = $this->Number->toReadableSize(1024 * 1024 * 1024);
		$this->assertSame('1 GB', $is);

		$is = $this->Number->toReadableSize(1024 * 1024 * 1024 * 1024 * 2.5);
		$this->assertSame('2,5 TB', $is);
	}

}
