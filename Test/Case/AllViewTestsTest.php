<?php
/**
 * Group test - Tools
 */
class AllViewTestsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All View tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'View');
		return $Suite;
	}
}
