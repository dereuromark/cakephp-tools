<?php

abstract class MyCakeTestCase extends CakeTestCase {

/*** time needed ***/

	protected static $startTime = null;

	public function _microtime($precision = 8) {
		return round(microtime(true), $precision);
	}

	public function _startClock($precision = 8) {
		self::$startTime = self::_microtime();
	}

	public function _elapsedTime($precision = 8, $restart = false) {
		$elapsed = self::_microtime() - self::$startTime;
		if ($restart) {
			self::_startClock();
		}
		return round($elapsed, $precision);
	}

	public function _header($title) {
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
	public function _baseurl() {
		return current(split("webroot", $_SERVER['PHP_SELF']));
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

# helper methods

	public function _basePath($full = false) {
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

	/**
	 * @param float $time
	 * @param int precision
	 * @param bool $secs: usually in milliseconds (for long times set it to 'true')
	 * 2009-07-20 ms
	 */
	public function _printElapsedTime($time = null, $precision = 8, $secs = false) {
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


/*** assert mods ***/

/** enhanced **/

	public static function assertNull($is, $title = null, $value = null, $message = '', $options = array()) {
		$expectation = 'NULL';
		self::_printTitle($expectation, $title, $options);
		self::_printResult($is, $value, $options);
		return parent::assertNull($is, $message);
	}

	public static function assertNotNull($is, $title = null, $value = null, $message = '', $options = array()) {
		$expectation = 'NOT NULL';
		self::_printTitle($expectation, $title, $options);
		self::_printResult($is, $value, $options);
		return parent::assertNotNull($is, $message);
	}

	/**
	 * own function: notEmpty
	 * FAIL on: array(), NULL, '', false, 0
	 * 2009-07-09 ms
	 */

	//deprecated
	public static function assertNotEmpty($is, $title = null, $value = null, $message = '') {
		$expectation = 'NOT EMPTY';
		self::_printTitle($expectation, $title);
		self::_printResult($is, $value);
		return parent::assertTrue(!empty($is), $message);
	}

	//deprecated
	public static function assertIsTrue($is, $title = null, $value = null, $message = '') {
		$expectation = 'TRUE';
		echo self::_title($expectation, $title);
		self::_printResult($is, $value);
		return parent::assertTrue($is, $message);
	}

	//deprecated
	public static function assertIsFalse($is, $title = null, $value = null, $message = '') {
		$expectation = 'FALSE';
		echo self::_title($expectation, $title);
		self::_printResult($is, $value);
		return parent::assertFalse($is, $message);
	}


/*** Helper Functions **/

	public function _title($expectation, $title = null) {
		$eTitle = '{expects: '.$expectation.'}';
		if (!empty($title)) {
			$eTitle = $title.' '.$eTitle;
		}
		return BR.BR.'<b>'.$eTitle.'</b>'.BR;
	}

	public function _printTitle($expectation, $title = null) {
		if (empty($_SERVER['HTTP_HOST']) || !isset($_GET['show_passes']) || !$_GET['show_passes']) {
			return false;
		}
		echo self::_title($expectation, $title);
	}

	public function _printResults($expected, $is, $pre = null, $status = false) {
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

	public function _printResult($is, $pre = null, $status = false) {
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
	 * outputs debug information during a web tester (browser) test case
	 * since PHPUnit>=3.6 swallowes all output by default 
	 * this is a convenience output handler since debug() or pr() have no effect
	 * @param mixed $data
	 * @param bool $pre should a pre tag be enclosed around the output
	 * @return void
	 * 2011-12-04 ms
	 */
	public function out($data, $pre = true) {
		if ($pre) {
			$data = pre($data);
		}
		echo $data;
		if (empty($_SERVER['HTTP_HOST'])) {
			# cli mode / shell access: use the --debug modifier if you are using the CLI interface
			return;
		}
		ob_flush();
	}

}

