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

}
