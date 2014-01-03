<?php

App::uses('Sanitize', 'Utility');
App::uses('Router', 'Routing');

/**
 * Main class for all app-wide utility methods
 *
 * @author Mark Scherer
 * @license MIT
 */
class Utility {

	/**
	 * Clean implementation of inArray to avoid false positives.
	 *
	 * in_array itself has some PHP flaws regarding cross-type comparison:
	 * - in_array('50x', array(40, 50, 60)) would be true!
	 * - in_array(50, array('40x', '50x', '60x')) would be true!
	 *
	 * @param mixed $needle
	 * @param array $haystack
	 * @return boolean Success
	 */
	public static function inArray($needle, $haystack) {
		$strict = !is_numeric($needle);
		return in_array((string)$needle, $haystack, $strict);
	}

	/**
	 * Tokenizes a string using $separator.
	 *
	 * Options
	 * - clean: true/false (defaults to true and removes empty tokens and whitespace)
	 *
	 * @param string $data The data to tokenize
	 * @param string $separator The token to split the data on.
	 * @param array $options
	 * @return void
	 */
	public static function tokenize($data, $separator = ',', $options = array()) {
		$defaults = array(
			'clean' => true
		);
		$options += $defaults;
		if (empty($data)) {
			return array();
		}
		$tokens = explode($separator, $data);
		if (empty($tokens) || !$options['clean']) {
			return $tokens;
		}

		$tokens = array_map('trim', $tokens);
		foreach ($tokens as $key => $token) {
			if ($token === '') {
				unset($tokens[$key]);
			}
		}
		return $tokens;
	}

	/**
	 * Multibyte analogue of preg_match_all() function. Only that this returns the result.
	 * By default this works properly with UTF8 strings.
	 *
	 * Do not forget to use preg_quote() first on strings that could potentially contain
	 * unescaped characters.
	 *
	 * Note that you still need to add the u modifier (for UTF8) to your pattern yourself.
	 *
	 * Example: /some(.*)pattern/u
	 *
	 * @param string $pattern The pattern to use.
	 * @param string $subject The string to match.
	 * @param integer $flags
	 * @param integer $offset
	 * @return array Result
	 */
	public static function pregMatchAll($pattern, $subject, $flags = PREG_SET_ORDER, $offset = null) {
		$pattern = substr($pattern, 0, 1) . '(*UTF8)' . substr($pattern, 1);
		preg_match_all($pattern, $subject, $matches, $flags, $offset);
		return $matches;
	}

	/**
	 * Multibyte analogue of preg_match() function. Only that this returns the result.
	 * By default this works properly with UTF8 strings.
	 *
	 * Do not forget to use preg_quote() first on strings that could potentially contain
	 * unescaped characters.
	 *
	 * Note that you still need to add the u modifier (for UTF8) to your pattern yourself.
	 *
	 * Example: /some(.*)pattern/u
	 *
	 * @param string $pattern The pattern to use.
	 * @param string $subject The string to match.
	 * @param integer $flags
	 * @param integer $offset
	 * @return array Result
	 */
	public static function pregMatch($pattern, $subject, $flags = null, $offset = null) {
		$pattern = substr($pattern, 0, 1) . '(*UTF8)' . substr($pattern, 1);
		preg_match($pattern, $subject, $matches, $flags, $offset);
		return $matches;
	}

	/**
	 * Multibyte analogue of str_split() function.
	 * By default this works properly with UTF8 strings.
	 *
	 * @param string $text
	 * @param integer $length
	 * @return array Result
	 */
	public static function strSplit($str, $length = 1) {
		if ($length < 1) {
			return false;
		}
		$result = array();
		$c = mb_strlen($str);
		for ($i = 0; $i < $c; $i += $length) {
			$result[] = mb_substr($str, $i, $length);
		}
		return $result;
	}

	/**
	 * Get the current IP address.
	 *
	 * @param boolean $safe
	 * @return string IP address
	 */
	public static function getClientIp($safe = true) {
		if (!$safe && env('HTTP_X_FORWARDED_FOR')) {
			$ipaddr = preg_replace('/(?:,.*)/', '', env('HTTP_X_FORWARDED_FOR'));
		} else {
			if (env('HTTP_CLIENT_IP')) {
				$ipaddr = env('HTTP_CLIENT_IP');
			} else {
				$ipaddr = env('REMOTE_ADDR');
			}
		}

		if (env('HTTP_CLIENTADDRESS')) {
			$tmpipaddr = env('HTTP_CLIENTADDRESS');

			if (!empty($tmpipaddr)) {
				$ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
			}
		}
		return trim($ipaddr);
	}

	/**
	 * Get the current referrer if available.
	 *
	 * @param boolean $full (defaults to false and leaves the url untouched)
	 * @return string referer (local or foreign)
	 */
	public static function getReferer($full = false) {
		$ref = env('HTTP_REFERER');
		$forwarded = env('HTTP_X_FORWARDED_HOST');
		if ($forwarded) {
			$ref = $forwarded;
		}
		if (empty($ref)) {
			return $ref;
		}
		if ($full) {
			$ref = Router::url($ref, $full);
		}
		return $ref;
	}

	/**
	 * Remove unnessary stuff + add http:// for external urls
	 * TODO: protocol to lower!
	 *
	 * @param string $url
	 * @return string Cleaned Url
	 */
	public static function cleanUrl($url, $headerRedirect = false) {
		if ($url === '' || $url === 'http://' || $url === 'http://www' || $url === 'http://www.') {
			$url = '';
		} else {
			$url = self::autoPrefixUrl($url, 'http://');
		}

		if ($headerRedirect && !empty($url)) {
			$headers = self::getHeaderFromUrl($url);
			if ($headers !== false) {
				$headerString = implode("\n", $headers);

				if ((bool)preg_match('#^HTTP/.*\s+[(301)]+\s#i', $headerString)) {
					foreach ($headers as $header) {
						if (mb_strpos($header, 'Location:') === 0) {
							$url = trim(hDec(mb_substr($header, 9))); // rawurldecode/urldecode ?
						}
					}
				}
			}
		}

		$length = mb_strlen($url);
		while (!empty($url) && mb_strrpos($url, '/') === $length - 1) {
			$url = mb_substr($url, 0, $length - 1);
			$length--;
		}
		return $url;
	}

	/**
	 * Parse headers from a specific URL content.
	 *
	 * @param string $url
	 * @return mixed array of headers or FALSE on failure
	 */
	public static function getHeaderFromUrl($url) {
		$url = @parse_url($url);

		if (empty($url)) {
			return false;
		}

		$url = array_map('trim', $url);
		$url['port'] = (!isset($url['port'])) ? '' : (':' . (int)$url['port']);
		$path = (isset($url['path'])) ? $url['path'] : '';

		if (empty($path)) {
			$path = '/';
		}

		$path .= (isset($url['query'])) ? "?$url[query]" : '';

		$defaults = array(
			'http' => array(
				'header' => "Accept: text/html\r\n" .
					"Connection: Close\r\n" .
					"User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64)\r\n",
			)
		);
		stream_context_get_default($defaults);

		if (isset($url['host']) && $url['host'] !== gethostbyname($url['host'])) {
			$url = "$url[scheme]://$url[host]$url[port]$path";
			$headers = get_headers($url);
			if (is_array($headers)) {
				return $headers;
			}
		}
		return false;
	}

	/**
	 * Add protocol prefix if necessary (and possible)
	 *
	 * @param string $url
	 */
	public static function autoPrefixUrl($url, $prefix = null) {
		if ($prefix === null) {
			$prefix = 'http://';
		}

		if (($pos = strpos($url, '.')) !== false) {
			if (strpos(substr($url, 0, $pos), '//') === false) {
				$url = $prefix . $url;
			}
		}
		return $url;
	}

	/**
	 * Encode strings with base64_encode and also
	 * replace chars base64 uses that would mess up the url.
	 *
	 * Do not use this for querystrings. Those will escape automatically.
	 * This is only useful for named or passed params.
	 *
	 * @param string $string Unsafe string
	 * @return string Encoded string
	 */
	public static function urlEncode($string) {
		return str_replace(array('/', '='), array('-', '_'), base64_encode($string));
	}

	/**
	 * Decode strings with base64_encode and also
	 * replace back chars base64 uses that would mess up the url.
	 *
	 * Do not use this for querystrings. Those will escape automatically.
	 * This is only useful for named or passed params.
	 *
	 * @param string $string Safe string
	 * @return string Decoded string
	 */
	public static function urlDecode($string) {
		return base64_decode(str_replace(array('-', '_'), array('/', '='), $string));
	}

	/**
	 * Returns true only if all values are true.
	 * //TODO: maybe move to bootstrap?
	 *
	 * @param array $array
	 * @return boolean Result
	 */
	public static function logicalAnd($array) {
		if (empty($array)) {
			return false;
		}
		foreach ($array as $result) {
			if (!$result) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns true if at least one value is true.
	 * //TODO: maybe move to bootstrap?
	 *
	 * @param array $array
	 * @return boolean Result
	 *
	 */
	public static function logicalOr($array) {
		foreach ($array as $result) {
			if ($result) {
				return true;
			}
		}
		return false;
	}

	/**
	 * On non-transaction db connections it will return a deep array of bools instead of bool.
	 * So we need to call this method inside the modified saveAll() method to return the expected single bool there, too.
	 *
	 * @param array
	 * @return boolean
	 */
	public static function isValidSaveAll($array) {
		if (empty($array)) {
			return false;
		}
		$ret = true;
		foreach ($array as $key => $val) {
			if (is_array($val)) {
				$ret = $ret & Utility::logicalAnd($val);
			} else {
				$ret = $ret & $val;
			}
		}
		return (bool)$ret;
	}

	/**
	 * Convenience function for automatic casting in form methods etc.
	 * //TODO: maybe move to bootstrap?
	 *
	 * @param mixed $value
	 * @param string $type
	 * @return safe value for DB query, or NULL if type was not a valid one
	 */
	public static function typeCast($value, $type) {
		switch ($type) {
			case 'int':
				$value = (int)$value;
				break;
			case 'float':
				$value = (float)$value;
				break;
			case 'double':
				$value = (double)$value;
				break;
			case 'array':
				$value = (array)$value;
				break;
			case 'bool':
				$value = (bool)$value;
				break;
			case 'string':
				$value = (string)$value;
				break;
			default:
				return null;
		}
		return $value;
	}

	/**
	 * Trim recursivly
	 *
	 */
	public static function trimDeep($value) {
		$value = is_array($value) ? array_map('self::trimDeep', $value) : trim($value);
		return $value;
	}

	/**
	 * H() recursivly
	 *
	 */
	public static function specialcharsDeep($value) {
		$value = is_array($value) ? array_map('self::specialcharsDeep', $value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		return $value;
	}

	/**
	 * Removes all except A-Z,a-z,0-9 and allowedChars (allowedChars array) recursivly
	 *
	 */
	public static function paranoidDeep($value) {
		$value = is_array($value) ? array_map('self::paranoidDeep', $value) : Sanatize::paranoid($value, $this->allowedChars);
		return $value;
	}

	/**
	 * Transfers/removes all < > from text (remove TRUE/FALSE)
	 *
	 */
	public static function htmlDeep($value) {
		$value = is_array($value) ? array_map('self::htmlDeep', $value) : Sanatize::html($value, $this->removeChars);
		return $value;
	}

	/**
	 * Main deep method
	 *
	 */
	public static function deep($function, $value) {
		$value = is_array($value) ? array_map('self::' . $function, $value) : $function($value);
		return $value;
	}

	/**
	 * Expands the values of an array of strings into a deep array.
	 * Opposite of flattenList().
	 *
	 * It needs at least a single separator to be present in all values
	 * as the key would otherwise be undefined. If data can contain such key-less
	 * rows, use $undefinedKey to avoid an exception being thrown. But it will
	 * effectivly collide with other values in that same key then.
	 *
	 * So `Some.Deep.Value` becomes `array('Some' => array('Deep' => array('Value')))`.
	 *
	 * @param array $data
	 * @param string $separator
	 * @param string $undefinedKey
	 * @return array
	 */
	public static function expandList(array $data, $separator = '.', $undefinedKey = null) {
		$result = array();
		foreach ($data as $value) {
			$keys = explode($separator, $value);
			$value = array_pop($keys);

			$keys = array_reverse($keys);
			if (!isset($keys[0])) {
				if ($undefinedKey === null) {
					throw new RuntimeException('Key-less values are not supported without $undefinedKey.');
				}
				$keys[0] = $undefinedKey;
			}
			$child = array($keys[0] => array($value));
			array_shift($keys);
			foreach ($keys as $k) {
				$child = array(
					$k => $child
				);
			}
			$result = Set::merge($result, $child);
		}
		return $result;
	}

	/**
	 * Flattens a deep array into an array of strings.
	 * Opposite of expandList().
	 *
	 * So `array('Some' => array('Deep' => array('Value')))` becomes `Some.Deep.Value`.
	 *
	 * Note that primarily only string should be used.
	 * However, boolean values are casted to int and thus
	 * both boolean and integer values also supported.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function flattenList(array $data, $separator = '.') {
		$result = array();
		$stack = array();
		$path = null;

		reset($data);
		while (!empty($data)) {
			$key = key($data);
			$element = $data[$key];
			unset($data[$key]);

			if (is_array($element) && !empty($element)) {
				if (!empty($data)) {
					$stack[] = array($data, $path);
				}
				$data = $element;
				reset($data);
				$path .= $key . $separator;
			} else {
				if (is_bool($element)) {
					$element = (int)$element;
				}
				$result[] = $path . $element;
			}

			if (empty($data) && !empty($stack)) {
				list($data, $path) = array_pop($stack);
				reset($data);
			}
		}
		return $result;
	}

	/**
	 * Force-flattens an array.
	 *
	 * Careful with this method. It can lose information.
	 * The keys will not be changed, thus possibly overwrite each other.
	 *
	 * //TODO: check if it can be replace by Hash::flatten() or Utility::flatten().
	 *
	 * @param array $array to flatten
	 * @param boolean $perserveKeys
	 * @return array
	 */
	public static function arrayFlatten($array, $preserveKeys = false) {
		if ($preserveKeys) {
			return self::_arrayFlatten($array);
		}
		if (!$array) {
			return array();
		}
		$result = array();
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$result = array_merge($result, self::arrayFlatten($value));
			} else {
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * Force-flattens an array and preserves the keys.
	 *
	 * Careful with this method. It can lose information.
	 * The keys will not be changed, thus possibly overwrite each other.
	 *
	 * //TODO: check if it can be replace by Hash::flatten() or Utility::flatten().
	 *
	 * @param array $a
	 * @param array $f
	 * @return array
	 */
	protected static function _arrayFlatten($a, $f = array()) {
		if (!$a) {
			return array();
		}
		foreach ($a as $k => $v) {
			if (is_array($v)) {
				$f = self::_arrayFlatten($v, $f);
			} else {
				$f[$k] = $v;
			}
		}
		return $f;
	}

	/**
	 * Similar to array_shift but on the keys of the array
	 * like array_shift() only for keys and not values
	 *
	 * @param array $keyValuePairs
	 * @return string key
	 */
	public static function arrayShiftKeys(&$array) {
		foreach ($array as $key => $value) {
			unset($array[$key]);
			return $key;
		}
	}

	protected static $_counterStartTime;

	/**
	 * Returns microtime as float value
	 * (to be subtracted right away)
	 *
	 * @param int $precision
	 * @return float
	 */
	public static function microtime($precision = 8) {
		return round(microtime(true), $precision);
	}

	/**
	 * @return void
	 */
	public static function startClock() {
		self::$_counterStartTime = self::microtime();
	}

	/**
	 * @param int $precision
	 * @param boolean $restartClock
	 * @return float
	 */
	public static function returnElapsedTime($precision = 8, $restartClock = false) {
		$startTime = self::$_counterStartTime;
		if ($restartClock) {
			self::startClock();
		}
		return self::calcElapsedTime($startTime, self::microtime(), $precision);
	}

	/**
	 * Returns microtime as float value
	 * (to be subtracted right away)
	 *
	 * @param int $start
	 * @param int $end
	 * @param int $precision
	 * @return float
	 */
	public static function calcElapsedTime($start, $end, $precision = 8) {
		$elapsed = $end - $start;
		return round($elapsed, $precision);
	}

}
