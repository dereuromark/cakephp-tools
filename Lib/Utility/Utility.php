<?php
App::uses('Sanitize', 'Utility');
App::uses('Router', 'Routing');

/**
 * Main class for all app-wide utility methods
 *
 * @author Mark Scherer
 * @license MIT
 * 2012-02-27 ms
 */
class Utility {

	/**
	 * get the current ip address
	 * @param bool $safe
	 * @return string $ip
	 * 2011-11-02 ms
	 */
	public static function getClientIp($safe = null) {
		if ($safe === null) {
			$safe = false;
		}
		if (!$safe && env('HTTP_X_FORWARDED_FOR') != null) {
			$ipaddr = preg_replace('/(?:,.*)/', '', env('HTTP_X_FORWARDED_FOR'));
		} else {
			if (env('HTTP_CLIENT_IP') != null) {
				$ipaddr = env('HTTP_CLIENT_IP');
			} else {
				$ipaddr = env('REMOTE_ADDR');
			}
		}

		if (env('HTTP_CLIENTADDRESS') != null) {
			$tmpipaddr = env('HTTP_CLIENTADDRESS');

			if (!empty($tmpipaddr)) {
				$ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
			}
		}
		return trim($ipaddr);
	}

	/**
	 * get the current referer
	 * @param bool $full (defaults to false and leaves the url untouched)
	 * @return string $referer (local or foreign)
	 * 2011-11-02 ms
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
			$ref = Router::url($full);
		}
		return $ref;
	}

	/**
	 * remove unnessary stuff + add http:// for external urls
	 * TODO: protocol to lower!
	 * @static
	 * 2009-12-22 ms
	 */
	public static function cleanUrl($url, $headerRedirect = false) {
		if ($url == '' || $url == 'http://' || $url == 'http://www' || $url == 'http://www.') {
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
	 * @static
	 * 2009-12-26 ms
	 */
	public static function getHeaderFromUrl($url) {
		$url = @parse_url($url);

		if (empty($url)) {
			return false;
		}

		$url = array_map('trim', $url);
		$url['port'] = (!isset($url['port']))?80 : (int)$url['port'];
		$path = (isset($url['path']))?$url['path'] : '';

		if (empty($path)) {
			$path = '/';
		}

		$path .= (isset($url['query']))?"?$url[query]" : '';

		if (isset($url['host']) && $url['host'] != gethostbyname($url['host'])) {
			$headers = @get_headers("$url[scheme]://$url[host]:$url[port]$path");
			return (is_array($headers)?$headers : false);
		}
		return false;
	}

	/**
	 * add protocol prefix if neccessary (and possible)
	 * static?
	 * 2010-06-02 ms
	 */
	public function autoPrefixUrl($url, $prefix = null) {
		if ($prefix === null) {
			$prefix = 'http://';
		}

		if (($pos = strpos($url, '.')) !== false) {
			if (strpos(substr($url, 0, $pos), '//') === false) {
				$url = $prefix.$url;
			}
		}
		return $url;
	}

	/**
	 * returns true only if all values are true
	 * @return bool $result
	 * maybe move to bootstrap?
	 * 2011-11-02 ms
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
	 * returns true if at least one value is true
	 * @return bool $result
	 * maybe move to bootstrap?
	 * 2011-11-02 ms
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
	 * convinience function for automatic casting in form methods etc
	 * @return safe value for DB query, or NULL if type was not a valid one
	 * @static
	 * maybe move to bootstrap?
	 * 2008-12-12 ms
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
				$value = (array )$value;
				break;
			case 'bool':
				$value = (bool)$value;
				break;
			case 'string':
				$value = (string )$value;
				break;
			default:
				return null;
		}
		return $value;
	}

	/**
	 * trim recursivly
	 *
	 * 2009-07-07 ms
	 */
	public static function trimDeep($value) {
		$value = is_array($value) ? array_map('self::trimDeep', $value) : trim($value);
		return $value;
	}

	/**
	 * h() recursivly
	 *
	 * 2009-07-07 ms
	 */
	public static function specialcharsDeep($value) {
		$value = is_array($value) ? array_map('self::specialcharsDeep', $value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		return $value;
	}

	/**
	 * removes all except A-Z,a-z,0-9 and allowedChars (allowedChars array) recursivly
	 *
	 * 2009-07-07 ms
	 */
	public static function paranoidDeep($value) {
		$value = is_array($value) ? array_map('self::paranoidDeep', $value) : Sanatize::paranoid($value, $this->allowedChars);
		return $value;
	}

	/**
	 * transfers/removes all < > from text (remove TRUE/FALSE)
	 *
	 * 2009-07-07 ms
	 */
	public static function htmlDeep($value) {
		$value = is_array($value) ? array_map('self::htmlDeep', $value) : Sanatize::html($value, $this->removeChars);
		return $value;
	}

	/**
	 * main deep method
	 *
	 * 2009-07-07 ms
	 */
	public static function deep($function, $value) {
		$value = is_array($value) ? array_map('self::' . $function, $value) : $function($value);
		return $value;
	}

	/**
	 * Flattens an array, or returns FALSE on fail.
	 * 2011-07-02 ms
	 */
	public static function arrayFlatten($array) {
		if (!is_array($array)) {
		return false;
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
	 * @param array $keyValuePairs
	 * @return string $key
	 * like array_shift() only for keys and not values
	 * 2011-01-22 ms
	 */
	public static function arrayShiftKeys(&$array) {
		foreach ($array as $key => $value) {
			unset($array[$key]);
			return $key;
		}
	}


	protected static $_counterStartTime;

	/**
	 * returns microtime as float value
	 * (to be subtracted right away)
	 * @static
	 * 2009-07-07 ms
	 */
	public static function microtime($precision = 8) {
		return round(microtime(true), $precision);
	}

	/**
	 * @return void
	 * 2009-07-07 ms
	 */
	public static function startClock() {
		self::$_counterStartTime = self::microtime();
	}

	/**
	 * @static
	 * 2009-07-07 ms
	 */
	public static function returnElapsedTime($precision = 8, $restartClock = false) {
		$startTime = self::$_counterStartTime;
		if ($restartClock) {
			self::startClock();
		}
		return self::calcElapsedTime($startTime, self::microtime(), $precision);
	}

	/**
	 * returns microtime as float value
	 * (to be subtracted right away)
	 * @static
	 * 2009-07-07 ms
	 */
	public static function calcElapsedTime($start, $end, $precision = 8) {
		$elapsed = $end - $start;
		return round($elapsed, $precision);
	}

}

