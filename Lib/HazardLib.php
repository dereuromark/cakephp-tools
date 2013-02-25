<?php

App::uses('Xml', 'Utility');

/**
 * Get dangerous strings for various security checks
 *
 * used in configurations controller + debug helper
 *
 * @author Mark Scherer
 * @license MIT
 * 2010-07-30 ms
 */
class HazardLib {

	const URL = 'http://ha.ckers.org/xssAttacks.xml';

	/**
	 * get dangerous sql strings to test with
	 * @return array
	 * @static
	 * 2010-07-31 ms
	 */
	public static function sqlStrings($veryDangerousToo = false) {
		/*
		$res = array(
			"SELECT * FROM users WHERE email = 'x'; INSERT INTO users ('username', 'password') VALUES ('x', 'y');--"
		);
		$veryDangerous = array(
			"SELECT * FROM users WHERE email = 'x'; DROP TABLE users; --'; -- Boom!"
		);
		*/
		$strings = array(
			"x'; INSERT INTO users ('username', 'password') VALUES ('x', 'y')",
		);

		$veryDangerous = array(
			"x'; DROP TABLE users; --",
		);
		if ($veryDangerousToo) {
			$strings = array_merge($strings, $veryDangerous);
		}
		return $strings;
	}

	/**
	 * get dangerous php strings to test with
	 * @return array
	 * 2010-07-31 ms
	 */
	public static function phpStrings() {
		$res = array(
			'a:100000000:{}', # serialized objects run the magic _ _wakeup() function when they're unserialized
			':3:"PDO":0:{}' # If the PDO extension is enabled -- and it is by default in PHP 5 -- you can cause a fatal error
		);
		return $res;
	}

	/**
	 * get dangerous html strings to test with
	 * @return array
	 * 2010-07-31 ms
	 */
	public static function xssStrings($cache = true) {
		if ($cache) {
			$texts = Cache::read('hazard_lib_texts');
		}
		if (empty($texts)) {
			$texts = array();
			$contents = self::_parseXml(self::URL);
			foreach ($contents as $content) {
				if ($content['code'] === 'See Below') {
					continue;
				}
				$texts[] = $content['code'];
			}
			if (empty($texts)) {
				trigger_error('ha.ckers.org FAILED - XML not available', E_WARNING);
				return array();
			}
			if ($cache) {
				Cache::write('hazard_lib_texts', $texts);
			}

		}
		return $texts;
	}

	/**
	 * Parse xml
	 *
	 * @return array
	 * 2010-02-07 ms
	 */
	protected static function _parseXml($file) {
		$xml = Xml::build($file);
		$res = Xml::toArray($xml);

		if (!empty($res['xss']['attack'])) {
			return (array)$res['xss']['attack'];
		}

		return array();
	}

}
