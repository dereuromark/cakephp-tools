<?php
namespace Tools\TestSuite;

use Cake\TestSuite\TestCase as CakeTestCase;

/**
 * Tools TestCase class
 *
 */
abstract class TestCase extends CakeTestCase {

	/**
	 * Opposite wrapper method of assertWithinMargin.
	 *
	 * @param float $result
	 * @param float $expected
	 * @param float $margin
	 * @param string $message
	 * @return void
	 */
	protected static function assertNotWithinMargin($result, $expected, $margin, $message = '') {
		$upper = $result + $margin;
		$lower = $result - $margin;
		return static::assertFalse((($expected <= $upper) && ($expected >= $lower)), $message);
	}

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
	 * Outputs debug information during a web tester (browser) test case
	 * since PHPUnit>=3.6 swallowes all output by default.
	 * This is a convenience output handler since debug() or pr() have no effect
	 *
	 * @param mixed $data
	 * @param bool $force Should the output be flushed (forced)
	 * @return void
	 */
	protected static function debug($data, $force = false) {
		if (php_sapi_name() === 'cli') {
			return;
		}
		debug($data, null, false);
		if (!$force) {
			return;
		}
		ob_flush();
	}

}
