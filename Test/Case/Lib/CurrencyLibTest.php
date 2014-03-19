<?php
App::uses('CurrencyLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CurrencyLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->CurrencyLib = new TestCurrencyLib();
	}

	/**
	 * CurrencyLibTest::testStartReset()
	 *
	 * @return void
	 */
	public function testStartReset() {
		$this->CurrencyLib->reset();
	}

	/**
	 * CurrencyLibTest::testConvert()
	 *
	 * @return void
	 */
	public function testConvert() {
		$this->out('<h2>30 EUR in USD</h2>', true);
		$is = $this->CurrencyLib->convert(30, 'EUR', 'USD');
		$this->debug($is);
		$this->assertTrue($is > 30 && $is < 60);

		$this->assertFalse($this->CurrencyLib->cacheFileUsed());
	}

	/**
	 * CurrencyLibTest::testIsAvailable()
	 *
	 * @return void
	 */
	public function testIsAvailable() {
		$is = $this->CurrencyLib->isAvailable('EUR');
		$this->assertTrue($is);

		$is = $this->CurrencyLib->isAvailable('XYZ');
		$this->assertFalse($is);
	}

	/**
	 * CurrencyLibTest::testTable()
	 *
	 * @return void
	 */
	public function testTable() {
		$is = $this->CurrencyLib->table();
		$this->assertTrue(is_array($is) && !empty($is));

		$is = $this->CurrencyLib->table('XYZ');
		$this->assertFalse($is);

		$this->assertTrue($this->CurrencyLib->cacheFileUsed());
	}

	public function testHistory() {
		$is = $this->CurrencyLib->history();
		$this->assertTrue(is_array($is) && !empty($is));
	}

	/**
	 * CurrencyLibTest::testReset()
	 *
	 * @return void
	 */
	public function testReset() {
		$res = $this->CurrencyLib->reset();
		$this->assertTrue($res === null || $res === true);
	}

}

class TestCurrencyLib extends CurrencyLib {

	protected function _loadXml($url) {
		if (php_sapi_name() !== 'cli' && !empty($_GET) && !empty($_GET['debug'])) {
			debug('Live Data!');
			return parent::_loadXml($url);
		}

		$file = basename($url);
		$url = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'xml' . DS . $file;
		return parent::_loadXml($url);
	}

}