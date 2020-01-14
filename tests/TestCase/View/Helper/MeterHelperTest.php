<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\View\View;
use InvalidArgumentException;
use Shim\TestSuite\TestCase;
use Tools\Utility\Number;
use Tools\View\Helper\MeterHelper;

class MeterHelperTest extends TestCase {

	/**
	 * @var \Cake\View\View
	 */
	protected $View;

	/**
	 * @var \Tools\View\Helper\MeterHelper
	 */
	protected $meterHelper;

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
		ini_set('intl.default_locale', 'en-US');
		Number::config('en_EN');

		$this->View = new View(null);
		$this->meterHelper = new MeterHelper($this->View);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		ini_set('intl.default_locale', $this->locale);
	}

	/**
	 * @return void
	 */
	public function testPrepareValue() {
		$value = 11.1;
		$max = 13.0;
		$min = 11.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareValue', [$value, $max, $min, false]);

		$this->assertSame($value, $result);

		$max = 11.0;
		$min = 10.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareValue', [$value, $max, $min, false]);

		$this->assertSame($max, $result);

		$max = 13.0;
		$min = 12.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareValue', [$value, $max, $min, false]);

		$this->assertSame($min, $result);

		$max = 10.0;
		$min = 9.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareValue', [$value, $max, $min, true]);

		$this->assertSame($value, $result);

		$max = 13.0;
		$min = 12.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareValue', [$value, $max, $min, true]);

		$this->assertSame($value, $result);
	}

	/**
	 * @return void
	 */
	public function testPrepareMax() {
		$value = 11.1;
		$max = 13.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareMax', [$value, $max, false]);

		$this->assertSame($max, $result);

		$max = 11.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareMax', [$value, $max, false]);

		$this->assertSame($max, $result);

		$max = 10.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareMax', [$value, $max, true]);

		$this->assertSame($value, $result);
	}

	/**
	 * @return void
	 */
	public function testPrepareMin() {
		$value = 11.1;
		$min = 10.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareMin', [$value, $min, false]);

		$this->assertSame($min, $result);

		$min = 12.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareMin', [$value, $min, false]);

		$this->assertSame($min, $result);

		$min = 12.0;
		$result = $this->invokeMethod($this->meterHelper, 'prepareMin', [$value, $min, true]);

		$this->assertSame($value, $result);
	}

	/**
	 * @return void
	 */
	public function testDraw() {
		$result = $this->meterHelper->draw(0.00, 3);
		$this->assertSame('░░░', $result);

		$result = $this->meterHelper->draw(1.00, 3);
		$this->assertSame('███', $result);

		$result = $this->meterHelper->draw(0.50, 3);
		$this->assertSame('██░', $result);

		$result = $this->meterHelper->draw(0.30, 5);
		$this->assertSame('██░░░', $result);

		$result = $this->meterHelper->draw(0.01, 3);
		$this->assertSame('░░░', $result);

		$result = $this->meterHelper->draw(0.99, 3);
		$this->assertSame('███', $result);
	}

	/**
	 * @return void
	 */
	public function testHtmlMeterBar() {
		$result = $this->meterHelper->htmlMeterBar(40 / 3, 20, 5);
		$expected = '<meter value="13.3333" min="5" max="20" title="56%"></meter>';
		$this->assertSame($expected, $result);

		$result = $this->meterHelper->htmlMeterBar(-1, 2, 0);
		$expected = '<meter value="0" min="0" max="2" title="0%"></meter>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testMeterBar() {
		$result = $this->meterHelper->meterBar(0.001, 1, 0, 3);
		$this->assertSame('<span title="0%">░░░</span>', $result);

		$result = $this->meterHelper->meterBar(2.1, 10, -10, 3);
		$this->assertSame('<span title="60%">██░</span>', $result);

		$result = $this->meterHelper->meterBar(0.000, 1, 0, 3);
		$this->assertSame('<span title="0%">░░░</span>', $result);

		$result = $this->meterHelper->meterBar(98, 100, -100, 3);
		$this->assertSame('<span title="99%">███</span>', $result);

		$result = $this->meterHelper->meterBar(1.000, 1, 0, 3);
		$this->assertSame('<span title="100%">███</span>', $result);
	}

	/**
	 * @return void
	 */
	public function testMeterBarInvalid() {
		$this->expectException(InvalidArgumentException::class);

		$this->meterHelper->meterBar(1, -1, 1, 3);
	}

}
