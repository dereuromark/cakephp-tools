<?php
/**
 * Group test - Tools
 */
class AllModelTestsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Model tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Model');
		return $Suite;
	}
}
