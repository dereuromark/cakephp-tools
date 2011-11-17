<?php

App::uses('DangerLib', 'Tools.Lib');

class DangerLibTest extends CakeTestCase {


	public function setUp() {
		$this->DangerLib = new DangerLib();
	}


	/**
	 * 2010-02-07 ms
	 */
	public function _testParse() {

		$is = $this->DangerLib->_parseXml(DangerLib::URL);
		pr(h($is));
		$this->assertTrue(!empty($is));
		$this->assertEqual(count($is), 113);
	}


	/**
	 * 2010-02-07 ms
	 */
	public function testXssStrings() {

		$is = $this->DangerLib->xssStrings(false);
		pr(h($is));
		$this->assertTrue(!empty($is));

		# cached
		Cache::delete('security_lib_texts');

		$is = $this->DangerLib->xssStrings();
		pr(h($is));
		$this->assertTrue(!empty($is) && count($is), 113);

		$is = $this->DangerLib->xssStrings();
		pr(h($is));
		$this->assertTrue(!empty($is) && count($is), 113);

	}
	
	public function testPhp() {
		$is = $this->DangerLib->phpStrings();
		pr(h($is));
	}
	
	public function testSql() {
		$is = $this->DangerLib->sqlStrings();
		pr(h($is));
	}


}

