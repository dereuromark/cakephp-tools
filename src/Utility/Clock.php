<?php

namespace Tools\Utility;

class Clock {

	/**
	 * @var float
	 */
	protected static $_counterStartTime = 0.0;

	/**
	 * Returns microtime as float value
	 * (to be subtracted right away)
	 *
	 * @param int $precision
	 *
	 * @return float
	 */
	public static function microtime(int $precision = 8): float {
		return round(microtime(true), $precision);
	}

	/**
	 * @return void
	 */
	public static function startClock(): void {
		static::$_counterStartTime = static::microtime();
	}

	/**
	 * @param int $precision
	 * @param bool $restartClock
	 * @return float
	 */
	public static function returnElapsedTime($precision = 8, $restartClock = false): float {
		$startTime = static::$_counterStartTime;
		if ($restartClock) {
			static::startClock();
		}

		return static::calcElapsedTime($startTime, static::microtime(), $precision);
	}

	/**
	 * Returns microtime as float value
	 * (to be subtracted right away)
	 *
	 * @param float $start
	 * @param float $end
	 * @param int $precision
	 * @return float
	 */
	public static function calcElapsedTime($start, $end, $precision = 8) {
		$elapsed = $end - $start;

		return round($elapsed, $precision);
	}

}
