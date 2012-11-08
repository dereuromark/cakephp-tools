<?php

abstract class MyCakeTestCase extends CakeTestCase {


/*** assert mods ***/

/** enhanced **/

	protected static function assertNotWithinMargin($result, $expected, $margin, $message = '') {
		$upper = $result + $margin;
		$lower = $result - $margin;
		return self::assertFalse((($expected <= $upper) && ($expected >= $lower)), $message);
	}

	//deprecated?
	public function assertIsNull($is, $title = null, $value = null, $message = '', $options = array()) {
		$expectation = 'NULL';
		self::_printTitle($expectation, $title, $options);
		self::_printResult($is, $value, $options);
		return $this->assertNull($is, $message);
	}

	//deprecated?
	public function assertIsNotNull($is, $title = null, $value = null, $message = '', $options = array()) {
		$expectation = 'NOT NULL';
		self::_printTitle($expectation, $title, $options);
		self::_printResult($is, $value, $options);
		return $this->assertNotNull($is, $message);
	}

/*** time needed ***/

	protected static $startTime = null;

	protected function _microtime($precision = 8) {
		return round(microtime(true), $precision);
	}

	protected function _startClock($precision = 8) {
		self::$startTime = self::_microtime();
	}

	protected function _elapsedTime($precision = 8, $restart = false) {
		$elapsed = self::_microtime() - self::$startTime;
		if ($restart) {
			self::_startClock();
		}
		return round($elapsed, $precision);
	}

/*
# cakephp2 phpunit wrapper
	public function assertEquals($expected, $actual, $title = null, $value = null, $message = '', $options = array()) {
		return $this->assertEqual($expected, $actual, $title, $value, $message, $options);
	}

	public function assertInternalType($expected, $actual) {
		return $this->assertType($expected, $actual);
	}

	public function markTestIncomplete() {
		$this->skipIf(true, '%s - Test Incomplete');
		return;
	}
*/

/*** Helper Functions **/


	/**
	 * outputs debug information during a web tester (browser) test case
	 * since PHPUnit>=3.6 swallowes all output by default
	 * this is a convenience output handler since debug() or pr() have no effect
	 * @param mixed $data
	 * @param bool $pre should a pre tag be enclosed around the output
	 * @return void
	 * 2011-12-04 ms
	 */
	public static function out($data, $pre = true) {
		if ($pre) {
			pr($data);
		} else {
			echo $data;
		}
		if (empty($_SERVER['HTTP_HOST'])) {
			# cli mode / shell access: use the --debug modifier if you are using the CLI interface
			return;
		}
		ob_flush();
	}

	protected function _basePath($full = false) {
		$phpSelf = $_SERVER['PHP_SELF'];
		if (strpos($phpSelf, 'webroot/test.php') !== false) {
			$pieces = explode('webroot/test.php', $phpSelf, 2);
		} else {
			$pieces = explode('test.php', $phpSelf, 2);
		}
		$url = array_shift($pieces);
		if ($full) {
			$protocol = array_shift(explode('/', $_SERVER['SERVER_PROTOCOL'], 2));
			$url = strtolower($protocol).'://'.$_SERVER['SERVER_NAME'].$url;
		}
		return $url;
	}

	protected function _header($title) {
		if (strpos($title, 'test') === 0) {
			$title = substr($title, 4);
			$title = Inflector::humanize(Inflector::underscore($title));
		}
		return '<h3>'.$title.'</h3>';
	}

	/**
	 * without trailing slash!?
	 * //TODO: test
	 * 2011-04-03 ms
	 */
	protected function _baseurl() {
		return current(split("webroot", $_SERVER['PHP_SELF']));
	}

	/**
	 * @param float $time
	 * @param int precision
	 * @param bool $secs: usually in milliseconds (for long times set it to 'true')
	 * 2009-07-20 ms
	 */
	protected function _printElapsedTime($time = null, $precision = 8, $secs = false) {
		if ($time === null) {
			$time = self::_elapsedTime($precision);
		}
		if ($secs) {
			$unit = 's';
			$prec = 7;
		} else {
			$time = $time*1000;
			$unit = 'ms';
			$prec = 4;
		}

		$precision = ($precision !== null) ? $precision : $prec;
		pr('elapsedTime: '.number_format($time, $precision, ',', '.').' '.$unit);
	}

	protected function _title($expectation, $title = null) {
		$eTitle = '{expects: '.$expectation.'}';
		if (!empty($title)) {
			$eTitle = $title.' '.$eTitle;
		}
		return BR.BR.'<b>'.$eTitle.'</b>'.BR;
	}

	protected function _printTitle($expectation, $title = null) {
		if (empty($_SERVER['HTTP_HOST']) || !isset($_GET['show_passes']) || !$_GET['show_passes']) {
			return false;
		}
		echo self::_title($expectation, $title);
	}

	protected function _printResults($expected, $is, $pre = null, $status = false) {
		if (empty($_SERVER['HTTP_HOST']) || !isset($_GET['show_passes']) || !$_GET['show_passes']) {
			return false;
		}

		if ($pre !== null) {
			echo 'value:';
			pr ($pre);
		}
		echo 'result is:';
		pr($is);
		if (!$status) {
			echo 'result expected:';
			pr ($expected);
		}
	}

	protected function _printResult($is, $pre = null, $status = false) {
		if (empty($_SERVER['HTTP_HOST']) || !isset($_GET['show_passes']) || !$_GET['show_passes']) {
			return false;
		}

		if ($pre !== null) {
			echo 'value:';
			pr($pre);
		}
		echo 'result is:';
		pr($is);
	}

	/**
	 * osFix method
	 *
	 * @param string $string
	 * @return string
	 */
	protected function _osFix($string) {
		return str_replace(array("\r\n", "\r"), "\n", $string);
	}


}

