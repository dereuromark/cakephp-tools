<?php

App::uses('HazardLib', 'Tools.Lib');

class HazardLibTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->HazardLib = new HazardLib();
	}

	/**
	 */
	public function _testParse() {

		$is = $this->HazardLib->_parseXml(HazardLib::URL);
		//pr(h($is));
		$this->assertTrue(!empty($is));
		$this->assertEquals(count($is), 113);
	}

	/**
	 */
	public function testXssStrings() {

		$is = $this->HazardLib->xssStrings(false);
		//pr(h($is));
		$this->assertTrue(!empty($is));

		// cached
		Cache::delete('security_lib_texts');

		$is = $this->HazardLib->xssStrings();
		//pr(h($is));
		$this->assertTrue(!empty($is) && count($is), 113);

		$is = $this->HazardLib->xssStrings();
		//pr(h($is));
		$this->assertTrue(!empty($is) && count($is), 113);
	}

	public function testPhp() {
		$is = $this->HazardLib->phpStrings();
		//pr(h($is));
	}

	public function testSql() {
		$is = $this->HazardLib->sqlStrings();
		//pr(h($is));
	}

}
