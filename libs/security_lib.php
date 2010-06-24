<?php

define('HACKERS_ORG_XML', 'http://ha.ckers.org/xssAttacks.xml');

/**
 * used in configurations controller + debug helper
 */
class SecurityLib {


	/**
	 * get dangerous strings to test with
	 *
	 * @return array
	 * @static
	 **/
	function xssStrings($cache = true) {
		if ($cache) {
			$texts = Cache::read('security_lib_texts');
		}
		if (empty($texts)) {
			$texts = array();
			$contents =  $this->parse(HACKERS_ORG_XML);
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
	function parse($file) {
		App::import('Core', 'Xml');

		$xml = new Xml($file);
		$res = $xml->toArray();

		if (!empty($res['Xss']['Attack'])) {
			return (array)$res['Xss']['Attack'];
		}

		return array();
	}

}

?>