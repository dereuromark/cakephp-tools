<?php
/**
 * Group test - Tools
 */
class AllLibTestsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Lib tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Lib');
		return $Suite;
	}
}
