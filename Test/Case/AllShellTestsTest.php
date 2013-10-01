<?php
/**
 * Group test - Tools
 */
class AllShellTestsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Shell tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Console' . DS . 'Command');
		return $Suite;
	}
}
