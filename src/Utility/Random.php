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
	 * Not for security relevant functionality - here use random_bytes()/random_bytes()
	 *
	 * @param int $min
	 * @param int $max
	 * @return int Random int value
	 */
	public static function int($min = 0, $max = 999) {
		return random_int($min, $max);
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
	public static function arrayValue(array $array, $minPosition = null, $maxPosition = null, $integerKeys = false) {
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
	 * Generates a password
	 *
	 * @link https://github.com/CakeDC/users/blob/master/models/user.php#L498
	 * @param int $length Password length
	 * @return string
	 */
	public static function pronounceablePwd($length = 10) {
		mt_srand((int)(float)microtime() * 1000000);
		$password = '';
		$vowels = ['a', 'e', 'i', 'o', 'u'];
		$cons = [
			'b', 'c', 'd', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'u', 'v', 'w', 'tr',
			'cr', 'br', 'fr', 'th', 'dr', 'ch', 'ph', 'wr', 'st', 'sp', 'sw', 'pr', 'sl', 'cl',
		];
		for ($i = 0; $i < $length; $i++) {
			$password .= $cons[random_int(0, 31)] . $vowels[random_int(0, 4)];
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
			$password .= $chars[random_int(0, $max)];
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
			$str .= $pool[random_int(0, $max)];
		}

		// Make sure alnum strings contain at least one letter and one digit
		if ($type === 'alnum' && $length > 1) {
			if (ctype_alpha($str)) {
				// Add a random digit
				$str[random_int(0, $length - 1)] = chr(random_int(48, 57));
			} elseif (ctype_digit($str)) {
				// Add a random letter
				$str[random_int(0, $length - 1)] = chr(random_int(65, 90));
			}
		}

		return $str;
	}

}
