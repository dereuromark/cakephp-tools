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

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Lib');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Lib' . DS . 'Utility');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Lib' . DS . 'Misc');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'View');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'View' . DS . 'Helper');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Model');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Model' . DS . 'Behavior');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Model' . DS . 'Datasource');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Console' . DS . 'Command');

		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Controller' . DS . 'Component');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Controller' . DS . 'Component' . DS . 'Auth');

		//$path = CakePlugin::path('Tools') . 'Test' . DS . 'Case' . DS;
		//$Suite->addTestDirectoryRecursive($path);
		return $Suite;
	}

}
