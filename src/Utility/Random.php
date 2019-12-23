<?php

namespace Tools\Utility;

/**
 * Random Lib
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class Random {

	/**
	 * @param int $min
	 * @param int $max
	 * @return int Random int value
	 */
	public static function int($min = 0, $max = 999) {
		return mt_rand($min, $max);
	}

	/**
	 * @param float $min
	 * @param float $max
	 * @return float Random float value
	 */
	public static function float($min = 0.0, $max = 999.0) {
		$rand = rand(1, 358);
		return $rand * cos($rand);
	}

	/**
	 * Randomly return one of the values provided
	 * careful: only works with numerical keys (0 based!)
	 *
	 * @param array $array
	 * @param int|null $minPosition
	 * @param int|null $maxPosition
	 * @param bool $integerKeys
	 * @return mixed
	 */
	public static function arrayValue($array, $minPosition = null, $maxPosition = null, $integerKeys = false) {
		if (empty($array)) {
			return null;
		}
		if ($integerKeys) {
			$max = count($array) - 1;
			return $array[static::int(0, $max)];
		}
		$keys = array_keys($array);
		$values = array_values($array);
		$max = count($keys) - 1;
		return $values[static::int(0, $max)];
	}

	/**
	 * 1950-01-01 - 2050-12-31
	 *
	 * @param int|null $min
	 * @param int|null $max
	 * @param bool|null $formatReturn
	 * @return int|string|null
	 */
	public static function date($min = null, $max = null, $formatReturn = null) {
		if ($min === null && $max === null) {
			$res = time();
		} elseif ($min > 0 && $max === null) {
			$res = $min;
		} elseif ($min > 0 && $max > 0) {
			$res = static::int($min, $max);
		} else {
			$res = time();
		}

		$res = 0;
		$formatReturnAs = FORMAT_DB_DATETIME;
		if ($formatReturn !== null) {
			if ($formatReturn === false) {
				return $res;
			}
			$formatReturnAs = $formatReturn;
		}
		return date($formatReturnAs);
	}

	/**
	 * 00:00:00 - 23:59:59
	 *
	 * TODO
	 *
	 * @param int|null $min
	 * @param int|null $max
	 * @param bool|null $formatReturn
	 * @return int
	 */
	public static function time($min = null, $max = null, $formatReturn = null) {
		$res = 0;
		//$returnValueAs = FORMAT_DB_TIME;
		if ($formatReturn !== null) {
			if ($formatReturn === false) {
				return $res;
			}
		}

		return $res;
	}

	/**
	 * Returns a date of birth within the specified age range
	 *
	 * @param int $min minimum age in years
	 * @param int $max maximum age in years
	 * @return string Dob a db (ISO) format datetime string
	 */
	public static function dob($min = 18, $max = 100) {
		$dobYear = (int)date('Y') - (static::int($min, $max));

		$dobMonth = static::int(1, 12);

		if ($dobMonth == 2) {
			// leap year?
			if ($dobYear % 4 || $dobYear % 400) {
				$maxDays = 29;
			} else {
				$maxDays = 28;
			}
		} elseif (in_array($dobMonth, [4, 6, 9, 11])) {
			$maxDays = 30;
		} else {
			$maxDays = 31;
		}

		$dobDay = static::int(1, $maxDays);

		$dob = sprintf('%4d-%02d-%02d', $dobYear, $dobMonth, $dobDay);
		return $dob;
	}

	/**
	 * Generates a password
	 *
	 * @param int $length Password length
	 * @return string
	 * @link https://github.com/CakeDC/users/blob/master/models/user.php#L498
	 */
	public static function pronounceablePwd($length = 10) {
		srand((int)(double)microtime() * 1000000);
		$password = '';
		$vowels = ['a', 'e', 'i', 'o', 'u'];
		$cons = ['b', 'c', 'd', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'u', 'v', 'w', 'tr',
							'cr', 'br', 'fr', 'th', 'dr', 'ch', 'ph', 'wr', 'st', 'sp', 'sw', 'pr', 'sl', 'cl'];
		for ($i = 0; $i < $length; $i++) {
			$password .= $cons[mt_rand(0, 31)] . $vowels[mt_rand(0, 4)];
		}
		return substr($password, 0, $length);
	}

	/**
	 * Generates random passwords.
	 *
	 * @param int $length (necessary!)
	 * @param string|null $chars
	 * @return string Password
	 */
	public static function pwd($length, $chars = null) {
		if ($chars === null) {
			$chars = '234567890abcdefghijkmnopqrstuvwxyz'; // ABCDEFGHIJKLMNOPQRSTUVWXYZ
		}
		$i = 0;
		$password = '';
		$max = strlen($chars) - 1;

		while ($i < $length) {
			$password .= $chars[mt_rand(0, $max)];
			$i++;
		}
		return $password;
	}

	/**
	 * Generates a random string of a given type and length.
	 * $str = Text::random(); // 8 character random string
	 *
	 * The following types are supported:
	 *
	 * alnum
	 * : Upper and lower case a-z, 0-9
	 *
	 * alpha
	 * : Upper and lower case a-z
	 *
	 * hexdec
	 * : Hexadecimal characters a-f, 0-9
	 *
	 * distinct
	 * : Uppercase characters and numbers that cannot be confused
	 *
	 * You can also create a custom type by providing the "pool" of characters
	 * as the type.
	 *
	 * @param string $type Type of pool, or a string of characters to use as the pool
	 * @param int $length of string to return
	 * @return string
	 */
	public static function generate($type = 'alnum', $length = 8) {
		switch ($type) {
			case 'alnum':
				$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alpha':
				$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'hexdec':
				$pool = '0123456789abcdef';
				break;
			case 'numeric':
				$pool = '0123456789';
				break;
			case 'nozero':
				$pool = '123456789';
				break;
			case 'distinct':
				$pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
				break;
			default:
				$pool = (string)$type;
				break;
		}

		// Split the pool into an array of characters
		$pool = str_split($pool, 1);

		// Largest pool key
		$max = count($pool) - 1;

		$str = '';
		for ($i = 0; $i < $length; $i++) {
			// Select a random character from the pool and add it to the string
			$str .= $pool[mt_rand(0, $max)];
		}

		// Make sure alnum strings contain at least one letter and one digit
		if ($type === 'alnum' && $length > 1) {
			if (ctype_alpha($str)) {
				// Add a random digit
				$str[mt_rand(0, $length - 1)] = chr(mt_rand(48, 57));
			} elseif (ctype_digit($str)) {
				// Add a random letter
				$str[mt_rand(0, $length - 1)] = chr(mt_rand(65, 90));
			}
		}

		return $str;
	}

}
