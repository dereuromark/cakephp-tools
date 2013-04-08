<?php

App::uses('CurrencyBitcoinLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CurrencyBitcoinLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->CurrencyBitcoin = new CurrencyBitcoinLib();
	}

	/**
	 * 2011-10-07 ms
	 */
	public function testBitmarket() {
		echo $this->_header('bitmarket - '.$this->CurrencyBitcoin->settings['currency']);
		$is = $this->CurrencyBitcoin->bitmarket();
		$this->debug($is);
		//$this->assertFalse($is);
	}

	/**
	 * 2011-10-07 ms
	 */
	public function testBitcoincharts() {
		echo $this->_header('bitcoincharts - '.$this->CurrencyBitcoin->settings['currency']);
		$is = $this->CurrencyBitcoin->bitcoincharts();
		$this->debug($is);
		//$this->assertFalse($is);
	}

	/**
	 * 2011-10-07 ms
	 */
	public function testRate() {
		$this->skipIf(true, 'TODO!');

		echo $this->_header('rate - bitmarket - '.$this->CurrencyBitcoin->settings['currency']);
		$is = $this->CurrencyBitcoin->rate();
		$this->debug($is);
		$this->assertTrue(is_numeric($is) && $is > 0 && $is < 100);

		echo $this->_header('rate - bitcoincharts - '.$this->CurrencyBitcoin->settings['currency']);
		$is = $this->CurrencyBitcoin->rate(array('api'=>'bitcoincharts'));
		$this->debug($is);
		$this->assertTrue(is_numeric($is) && $is > 0 && $is < 100);
	}
}
