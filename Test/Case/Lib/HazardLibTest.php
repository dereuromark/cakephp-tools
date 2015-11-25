<?php

App::uses('HazardLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class HazardLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		if ($this->isDebug()) {
			Configure::write('Hazard.debug', true);
		}

		$this->HazardLib = new TestHazardLib();
	}

	/**
	 * @return void
	 */
	public function testParse() {
		$is = $this->HazardLib->parseXml(HazardLib::URL);
		$this->assertTrue(!empty($is));
		$this->assertTrue(count($is) >= 3);
	}

	/**
	 * @return void
	 */
	public function testXssStrings() {
		$is = $this->HazardLib->xssStrings(false);
		$this->assertTrue(!empty($is));

		// cached
		Cache::delete('security_lib_texts');

		$is = $this->HazardLib->xssStrings();
		$this->assertTrue(!empty($is));

		$is = $this->HazardLib->xssStrings();
		$this->assertTrue(!empty($is));
	}

	/**
	 * @return void
	 */
	public function testPhp() {
		$is = $this->HazardLib->phpStrings();
		$this->assertTrue(!empty($is));
	}

	/**
	 * @return void
	 */
	public function testSql() {
		$is = $this->HazardLib->sqlStrings();
		$this->assertTrue(!empty($is));
	}

}

class TestHazardLib extends HazardLib {

	/**
	 * Return dummy data as long as no debug mode is given
	 *
	 * @return array
	 */
	public function parseXml($file) {
		return $this->_parseXml($file);
	}

	protected static function _parseXml($file) {
		if (Configure::read('Hazard.debug')) {
			return parent::_parseXml($file);
		}

		// Simulate the most important ones from the xml file to avoid API requests in CI testing
		$array = [
			['code' => '\'\';!--"<XSS>=&{()}'],
			['code' => '<SCRIPT>alert(\'XSS\')</SCRIPT>'],
			['code' => '<STYLE>.XSS{background-image:url("javascript:alert(\'XSS\')");}</STYLE><A CLASS=XSS></A>'],
		];

		return $array;
	}

}
