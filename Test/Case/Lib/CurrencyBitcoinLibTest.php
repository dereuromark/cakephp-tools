<?php

App::uses('CurrencyBitcoinLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CurrencyBitcoinLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->CurrencyBitcoin = new CurrencyBitcoinLib();
	}

	/**
	 */
	public function testBitmarket() {
		$is = $this->CurrencyBitcoin->bitmarket();
		$this->debug($is);
		//$this->assertFalse($is);
	}

	/**
	 */
	public function testBitcoincharts() {
		$is = $this->CurrencyBitcoin->bitcoincharts();
		$this->debug($is);
		//$this->assertFalse($is);
	}

	/**
	 */
	public function testRate() {
		$this->skipIf(true, 'TODO!');

		$is = $this->CurrencyBitcoin->rate();
		$this->debug($is);
		$this->assertTrue(is_numeric($is) && $is > 0 && $is < 100);

		$is = $this->CurrencyBitcoin->rate(['api' => 'bitcoincharts']);
		$this->debug($is);
		$this->assertTrue(is_numeric($is) && $is > 0 && $is < 100);
	}

}
