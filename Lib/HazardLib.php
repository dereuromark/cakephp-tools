<?php

App::uses('Xml', 'Utility');

/**
 * get dangerous strings for various security checks
 *
 * used in configurations controller + debug helper
 * 2010-07-30 ms
 */
class HazardLib {

	const URL = 'http://ha.ckers.org/xssAttacks.xml';


	/**
	 * get dangerous sql strings to test with
	 * @return array
	 * @static
	 * 2010-07-31 ms
	 **/
	public function sqlStrings($veryDangerousToo = false) {
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
	 * @static
	 * 2010-07-31 ms
	 **/
	public function phpStrings() {
		$res = array(
			'a:100000000:{}', # serialized objects run the magic _ _wakeup() function when they're unserialized
			':3:"PDO":0:{}' # If the PDO extension is enabled -- and it is by default in PHP 5 -- you can cause a fatal error
		);
		return $res;
	}


	/**
	 * get dangerous html strings to test with
	 * @return array
	 * @static
	 * 2010-07-31 ms
	 **/
	public function xssStrings($cache = true) {
		if ($cache) {
			$texts = Cache::read('security_lib_texts');
		}
		if (empty($texts)) {
			$texts = array();
			$contents = $this->_parseXml(self::URL);
			foreach ($contents as $content) {
				$texts[] = $content['code'];
			}
			if (empty($texts)) {
				trigger_error('ha.ckers.org FAILED - XML not available', E_WARNING);
				return array();
			}
			if ($cache) {
				Cache::write('security_lib_texts', $texts);
			}

		}
		return $texts;
	}


	/**
	 * parse xml
	 * 2010-02-07 ms
	 */
	public function _parseXml($file) {
		$xml = Xml::build($file);
		$res = Xml::toArray($xml);

		if (!empty($res['xss']['attack'])) {
			return (array)$res['xss']['attack'];
		}

		return array();
	}

}


