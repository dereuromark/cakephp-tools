<?php
/**
 * Draft 0.2 for PHP argument order fix
 */

/**
 * Fix/Unify order, unify _ (strstr to str_str etc).
 * @inspired by http://www.skyrocket.be/2009/05/30/php-function-naming-and-argument-order/comment-page-1
 *
 * The following functions use "needle hackstack":
 *  - array_search
 *  - in_array
 *
 * The following do it in reverse order and will be fixed with this class:
 * - strchr, stristr, strrchr, strstr
 * - strpos, strrpos, stripos, strripos, substr_count
 *
 * Also corrected is the naming of "rchr vs rrchr" by using "last" instead of "r":
 * - chr and lastChr
 * - pos, lastPos and iLastPos
 *
 */
final class Str {

	/**
	 * Avoid constructor conflicts.
	 *
	 */
	final public function __construct() {
	}

	/**
	 * Find the first occurrence of a string.
	 * Note: use iStr for CI
	 *
	 * @param mixed $needle
	 * @param string $haystack
	 * @param boolean $beforeNeedle (defaults to false)
	 * @return mixed
	 */
	final public static function str($needle, $haystack, $beforeNeedle = false) {
		return strstr($haystack, $needle, $beforeNeedle);
	}

	/**
	 * Case-insensitive strstr().
	 *
	 * @param mixed $needle
	 * @param string $haystack
	 * @param boolean $beforeNeedle (defaults to false)
	 * @return mixed
	 */
	final public static function iStr($needle, $haystack, $beforeNeedle = false) {
		return stristr($haystack, $needle, $beforeNeedle);
	}

	/**
	 * Find the first occurrence of a string - alias of strstr().
	 *
	 * @param mixed $needle
	 * @param string $haystack
	 * @param boolean $beforeNeedle (defaults to false)
	 * @return mixed
	 */
	final public static function chr($needle, $haystack, $beforeNeedle = false) {
		return strchr($haystack, $needle, $beforeNeedle);
	}

	/**
	 * Find the last occurrence of a character in a string.
	 * Note: If needle contains more than one character, only the first is used.
	 * This behavior is different from that of strstr(). This behavior is different from that of strstr().
	 * If needle is not a string, it is converted to an integer and applied as the ordinal value of a character.
	 *
	 * @param mixed $needle
	 * @param string $haystack
	 * @return mixed
	 */
	final public static function lastChr($needle, $haystack) {
		return strrchr($haystack, $needle);
	}

	/**
	 * Replace all occurrences of the search string with the replacement string.
	 * Note: use iReplace for CI
	 *
	 * @param mixed $search
	 * @param mixed $replace
	 * @param mixed $subject
	 * @param integer $count Reference to store count in
	 * @return mixed
	 */
	final public static function replace($search, $replace, $subject, &$count = null) {
		return str_replace($search, $replace, $subject, $count);
	}

	/**
	 * Case-insensitive version of str_replace().
	 *
	 * @param mixed $search
	 * @param mixed $replace
	 * @param mixed $subject
	 * @param integer $count Reference to store count in
	 * @return mixed
	 */
	final public static function iReplace($search, $replace, $subject, &$count = null) {
		return str_ireplace($search, $replace, $subject, $count);
	}

	/**
	 * Replace text within a portion of a string.
	 *
	 * @param mixed $string
	 * @param string $replacement
	 * @return mixed
	 */
	final public static function substrReplace($string, $replacement, $start, $length = null) {
		return substr_replace($string, $replacement, $start, $length);
	}

	/**
	 * Count the number of substring occurrences.
	 *
	 * @param string $needle
	 * @param string $haystack
	 * @param integer $offset
	 * @param integer $length
	 * @return integer
	 */
	final public static function count($needle, $haystack, $offset = 0, $length = null) {
		if ($length === null) {
			return substr_count($haystack, $needle, $offset);
		}
		return substr_count($haystack, $needle, $offset, $length);
	}

	/**
	 * Binary safe comparison of two strings from an offset, up to length characters.
	 * Note: use iCompare for CI (for the sake of consistency and less arguments - already enough)
	 *
	 * @param string $mainStr
	 * @param string $str
	 * @param integer $offset
	 * @param integer $length
	 * @return mixed
	 */
	final public static function compare($mainStr, $str, $offset = 0, $length = null) {
		return substr_compare($mainStr, $str, $offset, $length);
	}

	/**
	 * Binary safe comparison of two strings from an offset, up to length characters.
	 *
	 * @param string $mainStr
	 * @param string $str
	 * @param integer $offset
	 * @param integer $length
	 * @return mixed
	 */
	final public static function iCompare($mainStr, $str, $offset = 0, $length = null) {
		return substr_compare($mainStr, $str, $offset, $length, true);
	}

	/**
	 * Find the position of the first occurrence of a substring in a string.
	 * Note: use iPos for CI (for the sake of consistency and less arguments - already enough)
	 *
	 * @param string $needle
	 * @param string $haystack
	 * @param integer $offset
	 * @return mixed
	 */
	final public static function pos($needle, $haystack, $offset = 0) {
		return strpos($haystack, $needle, $offset);
	}

	/**
	 * Case-insensitive version of stripos().
	 *
	 * @param string $needle
	 * @param string $haystack
	 * @param integer $offset
	 * @return mixed
	 */
	final public static function iPos($needle, $haystack, $offset = 0) {
		return stripos($haystack, $needle, $offset);
	}

	/**
	 * Find the position of the last occurrence of a substring in a string.
	 * Note: use iLastPos for CI (for the sake of consistency and less arguments - already enough)
	 *
	 * @param string $needle
	 * @param string $haystack
	 * @param integer $offset
	 * @return mixed
	 */
	final public static function lastPos($needle, $haystack, $offset = 0) {
		return strrpos($haystack, $needle, $offset);
	}

	/**
	 * Case-insensitive version of strrpos().
	 *
	 * @param string $needle
	 * @param string $haystack
	 * @param integer $offset
	 * @return mixed
	 */
	final public static function iLastPos($needle, $haystack, $offset = 0) {
		return strripos($haystack, $needle, $offset);
	}

}
