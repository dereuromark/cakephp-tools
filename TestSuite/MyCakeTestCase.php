<?php
App::uses('ShimTestCase', 'Shim.TestSuite');

abstract class MyCakeTestCase extends ShimTestCase {

	protected static $_startTime = null;

	/**
	 * @param int $precision
	 * @return float
	 */
	protected function _microtime($precision = 8) {
		return round(microtime(true), $precision);
	}

	/**
	 * @param int $precision
	 * @return void
	 */
	protected function _startClock($precision = 8) {
		static::$_startTime = static::_microtime();
	}

	/**
	 * @param int $precision
	 * @param bool $restart
	 * @return float
	 */
	protected function _elapsedTime($precision = 8, $restart = false) {
		$elapsed = static::_microtime() - static::$_startTime;
		if ($restart) {
			static::_startClock();
		}
		return round($elapsed, $precision);
	}

	/**
	 * @param float $time
	 * @param int precision
	 * @param bool $secs: usually in milliseconds (for long times set it to 'true')
	 */
	protected function _printElapsedTime($time = null, $precision = 8, $secs = false) {
		if ($time === null) {
			$time = static::_elapsedTime($precision);
		}
		if ($secs) {
			$unit = 's';
			$prec = 7;
		} else {
			$time = $time * 1000;
			$unit = 'ms';
			$prec = 4;
		}

		$precision = ($precision !== null) ? $precision : $prec;
		pr('elapsedTime: ' . number_format($time, $precision, ',', '.') . ' ' . $unit);
	}

}
