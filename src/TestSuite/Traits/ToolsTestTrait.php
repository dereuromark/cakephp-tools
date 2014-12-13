<?php
namespace Tools\TestSuite\Traits;

/**
 * Utility methods for easier testing in CakePHP & PHPUnit
 */
trait ToolsTestTrait {

	/**
	 * OsFix method
	 *
	 * @param string $string
	 * @return string
	 */
	protected static function _osFix($string) {
		return str_replace(array("\r\n", "\r"), "\n", $string);
	}

	/**
	 * Outputs debug information during a test run.
	 * This is a convenience output handler since debug() itself is not desired
	 * for tests in general.
	 *
	 * Force flushing the output
	 *
	 * @param mixed $data
	 * @param bool $force Should the output be flushed (forced)
	 * @return void
	 */
	protected static function debug($data) {
		$output = !empty($_SERVER['argv']) && (in_array('-v', $_SERVER['argv'], true) || in_array('-vv', $_SERVER['argv'], true));
		if (!$output) {
			return;
		}
		$showFrom = in_array('-vv', $_SERVER['argv'], true);

		debug($data, null, $showFrom);
	}

}
