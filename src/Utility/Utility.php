<?php

namespace Tools\Utility;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Main class for all app-wide utility methods
 *
 * @author Mark Scherer
 * @license MIT
 */
class Utility {

	/**
	 * More sane !empty() check for actual (form) data input.
	 * It allows only empty string `''`, bool `false` or `null` to be failing the check.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public static function notBlank($value): bool {
		return $value === 0 || $value === 0.0 || $value === '0' || !empty($value);
	}

	/**
	 * Clean implementation of inArray to avoid false positives.
	 *
	 * in_array itself has some PHP flaws regarding cross-type comparison:
	 * - in_array('50x', array(40, 50, 60)) would be true!
	 * - in_array(50, array('40x', '50x', '60x')) would be true!
	 *
	 * @param mixed $needle
	 * @param array $haystack
	 * @return bool Success
	 */
	public static function inArray($needle, $haystack): bool {
		$strict = !is_numeric($needle);

		return in_array((string)$needle, $haystack, $strict);
	}

	/**
	 * Tokenizes a string using $separator.
	 *
	 * Options
	 * - clean: true/false (defaults to true and removes empty tokens and whitespace)
	 *
	 * @phpstan-param non-empty-string $separator
	 *
	 * @param string $data The data to tokenize
	 * @param string $separator The token to split the data on.
	 * @param array<string, mixed> $options
	 * @return array
	 */
	public static function tokenize(string $data, string $separator = ',', array $options = []): array {
		$defaults = [
			'clean' => true,
		];
		$options += $defaults;
		if (!$data) {
			return [];
		}
		$tokens = explode($separator, $data);
		if (!$options['clean']) {
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
	 * @param int $flags
	 * @param int|null $offset
	 * @return array Result
	 */
	public static function pregMatchAll($pattern, $subject, $flags = PREG_SET_ORDER, $offset = null): array {
		$pattern = substr($pattern, 0, 1) . '(*UTF8)' . substr($pattern, 1);
		preg_match_all($pattern, $subject, $matches, $flags, (int)$offset);

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
	 * @param int|null $flags
	 * @param int|null $offset
	 * @return array Result
	 */
	public static function pregMatch($pattern, $subject, $flags = null, $offset = null): array {
		$pattern = substr($pattern, 0, 1) . '(*UTF8)' . substr($pattern, 1);
		preg_match($pattern, $subject, $matches, (int)$flags, (int)$offset);

		return $matches;
	}

	/**
	 * Multibyte analogue of str_split() function.
	 * By default this works properly with UTF8 strings.
	 *
	 * @param string $str
	 * @param int $length
	 * @return array<string> Result
	 */
	public static function strSplit($str, $length = 1): array {
		if ($length < 1) {
			return [];
		}
		$result = [];
		$c = mb_strlen($str);
		for ($i = 0; $i < $c; $i += $length) {
			$result[] = mb_substr($str, $i, $length);
		}

		return $result;
	}

	/**
	 * Get the current IP address.
	 *
	 * @param bool $safe
	 * @return string IP address
	 */
	public static function getClientIp($safe = true): string {
		if (!$safe && env('HTTP_X_FORWARDED_FOR')) {
			$ipaddr = preg_replace('/(?:,.*)/', '', (string)env('HTTP_X_FORWARDED_FOR'));
		} elseif (!$safe && env('HTTP_CLIENT_IP')) {
			$ipaddr = env('HTTP_CLIENT_IP');
		} else {
			$ipaddr = env('REMOTE_ADDR');
		}

		return trim((string)$ipaddr);
	}

	/**
	 * Get the current referrer if available.
	 *
	 * @param bool $full (defaults to false and leaves the url untouched)
	 * @return string|null Referer (local or foreign)
	 */
	public static function getReferer($full = false): ?string {
		/** @var string|null $ref */
		$ref = env('HTTP_REFERER');
		/** @var string|null $forwarded */
		$forwarded = env('HTTP_X_FORWARDED_HOST');
		if ($forwarded) {
			$ref = $forwarded;
		}
		if (!$ref) {
			return $ref;
		}
		if ($full) {
			$ref = Router::url($ref, $full);
		}

		return $ref;
	}

	/**
	 * Remove unnecessary stuff + add http:// for external urls
	 *
	 * @param string $url
	 * @param bool $headerRedirect
	 * @param bool|null $detectHttps
	 * @return string Cleaned Url
	 */
	public static function cleanUrl($url, $headerRedirect = false, $detectHttps = null): string {
		if ($url === '' || $url === 'http://' || $url === 'http://www' || $url === 'http://www.') {
			$url = '';
		} else {
			$url = static::autoPrefixUrl($url, 'http://', $detectHttps);
		}

		if ($headerRedirect && !empty($url)) {
			$headers = static::getHeaderFromUrl($url);
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
	 * Removes http:// or other protocols from the link.
	 *
	 * @param string $url
	 * @param array<string> $protocols Defaults to http and https. Pass empty array for all.
	 * @return string strippedUrl
	 */
	public static function stripProtocol(string $url, array $protocols = ['http', 'https']): string {
		$pieces = parse_url($url);
		// Already stripped?
		if (empty($pieces['scheme'])) {
			return $url;
		}
		if ($protocols && !in_array($pieces['scheme'], $protocols)) {
			return $url;
		}

		return mb_substr($url, mb_strlen($pieces['scheme']) + 3);
	}

	/**
	 * A more robust wrapper around for file_exists() which easily
	 * fails to return true for existent remote files.
	 * Per default it allows http/https images to be looked up via urlExists()
	 * for a better result.
	 *
	 * @param string $file File
	 * @param string $pattern
	 * @return bool Success
	 */
	public static function fileExists($file, $pattern = '~^https?://~i'): bool {
		if (!preg_match($pattern, $file)) {
			return file_exists($file);
		}

		return static::urlExists($file);
	}

	/**
	 * file_exists() does not always work with URLs.
	 * So if you check on strpos(http) === 0 you can use this
	 * to check for URLs instead.
	 *
	 * @param string $url Absolute URL.
	 * @param array $statusCodes List of accepted status codes. Defaults to 200 OK.
	 * @return bool Success
	 */
	public static function urlExists($url, array $statusCodes = []): bool {
		if (function_exists('curl_init')) {
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_NOBODY, true);
			$result = curl_exec($curl);
			if ($result === false) {
				return false;
			}

			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($statusCodes === []) {
				$statusCodes = [200];
			}

			return in_array($statusCode, $statusCodes, true);
		}

		// @codingStandardsIgnoreStart
		$headers = @get_headers($url);
		// @codingStandardsIgnoreEnd
		if ($headers && preg_match('|\b200\b|', $headers[0])) {
			return true;
		}

		return false;
	}

	/**
	 * Parse headers from a specific URL content.
	 *
	 * @param string $url
	 *
	 * @return mixed array of headers or FALSE on failure
	 */
	public static function getHeaderFromUrl(string $url) {
		// @codingStandardsIgnoreStart
		$urlArray = @parse_url($url);
		// @codingStandardsIgnoreEnd
		if (!$urlArray) {
			return false;
		}

		/** @var callable $callback */
		$callback = 'trim';
		$urlArray = array_map($callback, $urlArray);
		$urlArray['port'] = (!isset($urlArray['port'])) ? '' : (':' . (int)$urlArray['port']);
		$path = (isset($urlArray['path'])) ? $urlArray['path'] : '';

		if (!$path) {
			$path = '/';
		}

		$path .= (isset($urlArray['query'])) ? "?$urlArray[query]" : '';

		$defaults = [
			'http' => [
				'header' => "Accept: text/html\r\n"
					. "Connection: Close\r\n"
					. "User-Agent: Mozilla/5.0 (Windows NT 6.2; WOW64)\r\n",
			],
		];
		stream_context_get_default($defaults);

		if (isset($urlArray['host']) && $urlArray['host'] !== gethostbyname($urlArray['host'])) {
			$scheme = $urlArray['scheme'] ?? '';
			$urlArray = "$scheme://$urlArray[host]$urlArray[port]$path";
			try {
				$headers = get_headers($urlArray);
			} catch (Exception $exception) {
				Log::write('debug', '`' . $url . '` URL parse error - ' . $exception->getMessage());

				return false;
			}
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
	 * @param string|null $prefix
	 * @param bool|null $detectHttps
	 *
	 * @return string
	 */
	public static function autoPrefixUrl(string $url, ?string $prefix = null, ?bool $detectHttps = null): string {
		if ($prefix === null) {
			$prefix = 'http://';
		}

		$modifiedUrl = $url;
		$pos = strpos($url, '.');
		if ($pos !== false) {
			if (!str_contains(substr($url, 0, $pos), '//')) {
				$modifiedUrl = $prefix . $url;
			}

			if ($detectHttps === null) {
				$detectHttps = !Configure::read('debug') || PHP_SAPI !== 'cli';
			}
			if ($prefix === 'http://' && $detectHttps && static::urlExists('https://' . $url)) {
				$modifiedUrl = 'https://' . $url;
			}
		}

		return $modifiedUrl;
	}

	/**
	 * Returns true only if all values are true.
	 *
	 * @param array $array
	 *
	 * @return bool Result
	 */
	public static function logicalAnd(array $array): bool {
		if (!$array) {
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
	 *
	 * @param array $array
	 *
	 * @return bool Result
	 */
	public static function logicalOr(array $array): bool {
		foreach ($array as $result) {
			if ($result) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convenience function for automatic casting in form methods etc.
	 *
	 * @param mixed $value
	 * @param string $type
	 * @return mixed Safe value for DB query, or NULL if type was not a valid one
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
				$value = (float)$value;

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
	 * Trim recursively
	 *
	 * @param mixed $value
	 * @param bool $transformNullToString
	 *
	 * @return mixed
	 */
	public static function trimDeep($value, bool $transformNullToString = false) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = static::trimDeep($v, $transformNullToString);
			}

			return $value;
		}

		if (is_string($value) || $value === null) {
			return ($value === null && !$transformNullToString) ? $value : trim((string)$value);
		}

		return $value;
	}

	/**
	 * Applies h() recursively
	 *
	 * @param array|string $value
	 * @return array|string
	 */
	public static function specialcharsDeep($value) {
		/** @var callable $callable */
		$callable = 'self::specialcharsDeep';
		$value = is_array($value) ? array_map($callable, $value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

		return $value;
	}

	/**
	 * Main deep method
	 *
	 * @param string $function Callable or function name.
	 * @param mixed $value
	 * @return array|string
	 */
	public static function deep($function, $value) {
		$callable = 'self::' . $function;
		/**
		 * @var callable&non-falsy-string $callable
		 * @var callable $function
		 */
		$value = is_array($value) ? array_map($callable, $value) : $function($value);

		return $value;
	}

	/**
	 * Counts the dimensions of an array. If $all is set to false (which is the default) it will
	 * only consider the dimension of the first element in the array.
	 *
	 * @param mixed $array Array to count dimensions on
	 * @param bool $all Set to true to count the dimension considering all elements in array
	 * @param int $count Start the dimension count at this number
	 *
	 * @return int The number of dimensions in $array
	 */
	public static function countDim($array, bool $all = false, int $count = 0): int {
		if ($all) {
			$depth = [$count];
			if (is_array($array) && reset($array) !== false) {
				foreach ($array as $value) {
					$depth[] = static::countDim($value, true, $count + 1);
				}
			}
			$return = max($depth);
		} else {
			if (is_array(reset($array))) {
				$return = static::countDim(reset($array)) + 1;
			} else {
				$return = 1;
			}
		}

		return $return;
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
	 * @phpstan-param non-empty-string $separator
	 *
	 * @param array $data
	 * @param string $separator
	 * @param string|null $undefinedKey
	 * @return array
	 */
	public static function expandList(array $data, string $separator = '.', ?string $undefinedKey = null): array {
		$result = [];
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
			$child = [$keys[0] => [$value]];
			array_shift($keys);
			foreach ($keys as $k) {
				$child = [
					$k => $child,
				];
			}
			$result = Hash::merge($result, $child);
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
	 * @param string $separator
	 * @return array
	 */
	public static function flattenList(array $data, string $separator = '.'): array {
		$result = [];
		$stack = [];
		$path = null;

		reset($data);
		while ($data) {
			$key = key($data);
			$element = $data[$key];
			unset($data[$key]);

			if (is_array($element) && !empty($element)) {
				if (!empty($data)) {
					$stack[] = [$data, $path];
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

			if (!$data && $stack) {
				[$data, $path] = array_pop($stack);
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
	 * @param array $array Array to flatten
	 * @param bool $preserveKeys
	 *
	 * @return array
	 */
	public static function arrayFlatten(array $array, bool $preserveKeys = false): array {
		if ($preserveKeys) {
			return static::_arrayFlatten($array);
		}
		if (!$array) {
			return [];
		}
		$result = [];
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$result = array_merge($result, static::arrayFlatten($value));
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
	protected static function _arrayFlatten($a, $f = []) {
		if (!$a) {
			return [];
		}
		foreach ($a as $k => $v) {
			if (is_array($v)) {
				$f = static::_arrayFlatten($v, $f);
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
	 * @param array $array keyValuePairs
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return string key
	 */
	public static function arrayShiftKeys(array &$array): string {
		foreach ($array as $key => $value) {
			unset($array[$key]);

			return $key;
		}

		throw new InvalidArgumentException('Empty array');
	}

}
