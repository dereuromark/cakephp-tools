<?php
/**
 * Draft 0.2 for PHP argument order fix
 * 2012-04-14 ms
 */

/**
 * Fix/Unify order, unify _ (strstr to str_str etc).
 * @inspired by http://www.skyrocket.be/2009/05/30/php-function-naming-and-argument-order/comment-page-1
 *
 * 2012-04-13 ms
 */
final class Str {

	/**
	 * Avoid constructor conflicts.
	 *
	 * @return void
	 */
	final public function __construct() {
	}

	/**
	 * Find the first occurrence of a string.
	 * Note: use iStr for CI
	 *
	 * @return mixed
	 */
	final public static function str($needle, $haystack, $beforeNeedle  = false) {
		return strstr($haystack, $needle, $beforeNeedle);
	}

	/**
	 * Case-insensitive strstr().
	 *
	 * @return mixed
	 */
	final public static function iStr($needle, $haystack, $beforeNeedle  = false) {
		return stristr($haystack, $needle, $beforeNeedle);
	}

	/**
	 * Find the first occurrence of a string - alias of strstr().
	 *
	 * @return mixed
	 */
	final public static function chr($needle, $haystack, $beforeNeedle  = false) {
		return strchr($haystack, $needle, $beforeNeedle);
	}

	/**
	 * Find the last occurrence of a character in a string.
	 * Note: If needle contains more than one character, only the first is used.
	 * This behavior is different from that of strstr(). This behavior is different from that of strstr().
	 * If needle is not a string, it is converted to an integer and applied as the ordinal value of a character.
	 *
	 * @return mixed
	 */
	final public static function rChr($needle, $haystack) {
		return strrchr($haystack, $needle);
	}

	/**
	 * Replace all occurrences of the search string with the replacement string.
	 * Note: use iReplace for CI
	 *
	 * @return mixed
	 */
	final public static function replace($search, $replace, $subject, &$count = null) {
		return str_replace($search, $replace, $subject, $count);
	}

	/**
	 * Case-insensitive version of str_replace().
	 *
	 * @return mixed
	 */
	final public static function iReplace($search, $replace, $subject, &$count = null) {
		return str_ireplace($search, $replace, $subject, $count);
	}

	/**
	 * Replace text within a portion of a string.
	 *
	 * @return mixed
	 */
	final public static function substrReplace($string, $replacement, $start, $length = null) {
		return substr_replace($string, $replacement, $start, $length);
	}

	/**
	 * Count the number of substring occurrences.
	 *
	 * @return int
	 */
	final public static function count($needle, $haystack, $offset = 0, $length = null) {
		return substr_count($needle, $haystack, $offset = 0, $length);
	}

	/**
	 * Binary safe comparison of two strings from an offset, up to length characters.
	 * Note: use iCompare for CI (for the sake of consistency and less arguments - already enough)
	 *
	 * @return mixed
	 */
	final public static function compare($mainStr, $str, $offset = 0, $length = null) {
		return substr_compare($mainStr, $str, $offset = 0, $length);
	}

	/**
	 * Binary safe comparison of two strings from an offset, up to length characters.
	 *
	 * @return mixed
	 */
	final public static function iCompare($mainStr, $str, $offset = 0, $length = null) {
		return substr_compare($needle, $haystack, $offset = 0, $length, true);
	}

	/**
	 * Find the position of the first occurrence of a substring in a string.
	 *
	 * @return mixed
	 */
	final public static function pos($needle, $haystack, $offset = 0) {
		return strpos($haystack, $needle, $offset);
	}

	/**
	 * Find the position of the last occurrence of a substring in a string.
	 *
	 * @return mixed
	 */
	final public static function rPos($needle, $haystack, $offset = 0) {
		return strrpos($haystack, $needle, $offset);
	}

}
