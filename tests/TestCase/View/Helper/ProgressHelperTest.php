<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\Utility\Number;
use Tools\View\Helper\ProgressHelper;

class ProgressHelperTest extends TestCase {

	/**
	 * @var \Cake\View\View
	 */
	protected $View;

	/**
	 * @var \Tools\View\Helper\ProgressHelper
	 */
	protected $progressHelper;

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
		$this->progressHelper = new ProgressHelper($this->View);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		//ini_set('intl.default_locale', $this->locale);
	}

	/**
	 * @return void
	 */
	public function testDraw() {
		$result = $this->progressHelper->draw(0.00, 3);
		$this->assertSame('░░░', $result);

		$result = $this->progressHelper->draw(1.00, 3);
		$this->assertSame('███', $result);

		$result = $this->progressHelper->draw(0.50, 3);
		$this->assertSame('██░', $result);

		$result = $this->progressHelper->draw(0.30, 5);
		$this->assertSame('██░░░', $result);

		$result = $this->progressHelper->draw(0.01, 3);
		$this->assertSame('█░░', $result);

		$result = $this->progressHelper->draw(0.99, 3);
		$this->assertSame('██░', $result);
	}

	/**
	 * @return void
	 */
	public function testProgressBar() {
		$result = $this->progressHelper->progressBar(0.001, 3);
		$this->assertSame('<span title="1%">█░░</span>', $result);

		$result = $this->progressHelper->progressBar(0.999, 3);
		$this->assertSame('<span title="99%">██░</span>', $result);

		$result = $this->progressHelper->progressBar(0.000, 3);
		$this->assertSame('<span title="0%">░░░</span>', $result);

		$result = $this->progressHelper->progressBar(1.000, 3);
		$this->assertSame('<span title="100%">███</span>', $result);
	}

	/**
	 * @return void
	 */
	public function testCalculatePercentage() {
		$result = $this->progressHelper->calculatePercentage(0, 0);
		$this->assertSame(0.00, $result);

		$result = $this->progressHelper->calculatePercentage(0.0, 0.0);
		$this->assertSame(0.00, $result);

		$result = $this->progressHelper->calculatePercentage(997, 1);
		$this->assertSame(0.01, $result);

		$result = $this->progressHelper->calculatePercentage(997, 996);
		$this->assertSame(0.99, $result);

		$result = $this->progressHelper->calculatePercentage(997, 997);
		$this->assertSame(1.00, $result);
	}

	/**
	 * @return void
	 */
	public function testRoundPercentage() {
		$result = $this->progressHelper->roundPercentage(0.001);
		$this->assertSame(0.01, $result);

		$result = $this->progressHelper->roundPercentage(0.999);
		$this->assertSame(0.99, $result);
	}

}
