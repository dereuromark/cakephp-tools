<?php
namespace Tools\TestSuite;

use Cake\TestSuite\TestCase as CakeTestCase;

/**
 * Tools TestCase class
 *
 */
abstract class TestCase extends CakeTestCase {

	use ToolsTestTrait;

	/**
	 * Opposite wrapper method of assertWithinRange().
	 *
	 * @param float $result
	 * @param float $expected
	 * @param float $margin
	 * @param string $message
	 * @return void
	 */
	protected static function assertNotWithinRange($expected, $result, $margin, $message = '') {
		$upper = $result + $margin;
		$lower = $result - $margin;

		return static::assertTrue((($expected > $upper) || ($expected < $lower)), $message);
	}

}
