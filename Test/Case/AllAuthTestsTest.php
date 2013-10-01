<?php
/**
 * Group test - Tools
 */
class AllAuthTestsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Auth tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Controller' . DS . 'Component' . DS . 'Auth');
		return $Suite;
	}
}
