<?php
/**
 * Tools Plugin - All plugin tests
 */
class AllToolsTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Tools tests');

		$path = CakePlugin::path('Tools') . 'Test' . DS . 'Case' . DS;
		$Suite->addTestDirectoryRecursive($path);
		return $Suite;
	}

}
