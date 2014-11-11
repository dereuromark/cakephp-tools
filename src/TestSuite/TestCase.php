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

}
