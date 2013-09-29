<?php
App::uses('CurrencyLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CurrencyLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->CurrencyLib = new CurrencyLib();
	}

	public function testStartReset() {
		$this->CurrencyLib->reset();
	}

	/**
	 * test
	 */
	public function testConvert() {
		$this->out('<h2>30 EUR in USD</h2>', true);
		$is = $this->CurrencyLib->convert(30, 'EUR', 'USD');
		$this->debug($is);
		$this->assertTrue($is > 30 && $is < 60);

		$this->assertFalse($this->CurrencyLib->cacheFileUsed());
	}

	public function testIsAvailable() {
		$is = $this->CurrencyLib->isAvailable('EUR');
		$this->assertTrue($is);

		$is = $this->CurrencyLib->isAvailable('XYZ');
		$this->assertFalse($is);
	}

	public function testTable() {
		$this->out('<h2>Currency Table</h2>', true);
		$is = $this->CurrencyLib->table();
		$this->debug($is);
		$this->assertTrue(is_array($is) && !empty($is));

		$is = $this->CurrencyLib->table('XYZ');
		$this->assertFalse($is);

		$this->assertTrue($this->CurrencyLib->cacheFileUsed());
	}

	public function testReset() {
		$res = $this->CurrencyLib->reset();
		$this->assertTrue($res === null || $res === true);
	}

}