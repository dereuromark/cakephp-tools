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
		$this->out($this->_header('bitmarket - ' . $this->CurrencyBitcoin->settings['currency']), true);
		$is = $this->CurrencyBitcoin->bitmarket();
		$this->debug($is);
		//$this->assertFalse($is);
	}

	/**
	 */
	public function testBitcoincharts() {
		$this->debug($this->_header('bitcoincharts - ' . $this->CurrencyBitcoin->settings['currency']), true);
		$is = $this->CurrencyBitcoin->bitcoincharts();
		$this->debug($is);
		//$this->assertFalse($is);
	}

	/**
	 */
	public function testRate() {
		$this->skipIf(true, 'TODO!');

		$this->debug($this->_header('rate - bitmarket - ' . $this->CurrencyBitcoin->settings['currency']), true);
		$is = $this->CurrencyBitcoin->rate();
		$this->debug($is);
		$this->assertTrue(is_numeric($is) && $is > 0 && $is < 100);

		$this->debug($this->_header('rate - bitcoincharts - ' . $this->CurrencyBitcoin->settings['currency']), true);
		$is = $this->CurrencyBitcoin->rate(array('api' => 'bitcoincharts'));
		$this->debug($is);
		$this->assertTrue(is_numeric($is) && $is > 0 && $is < 100);
	}

}
