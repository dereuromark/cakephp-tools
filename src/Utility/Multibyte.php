<?php

namespace Tools\Utility;

/**
 * Multibyte handling methods.
 *
 * Shim class for 3.x built from 2.x core file.
 */

/**
 * Multibyte handling methods.
 */
class Multibyte {

	/**
	 * Converts a multibyte character string
	 * to the decimal value of the character
	 *
	 * @param string $string String to convert.
	 * @return array
	 */
	public static function utf8($string) {
		$map = [];

		$values = [];
		$find = 1;
		$length = strlen($string);

		for ($i = 0; $i < $length; $i++) {
			$value = ord($string[$i]);

			if ($value < 128) {
				$map[] = $value;
			} else {
				if (empty($values)) {
					$find = ($value < 224) ? 2 : 3;
				}
				$values[] = $value;

				if (count($values) === $find) {
					if ($find == 3) {
						$map[] = (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64);
					} else {
						$map[] = (($values[0] % 32) * 64) + ($values[1] % 64);
					}
					$values = [];
					$find = 1;
				}
			}
		}
		return $map;
	}

	/**
	 * Converts the decimal value of a multibyte character string
	 * to a string
	 *
	 * @param array $array Values array.
	 * @return string
	 */
	public static function ascii($array) {
		$ascii = '';

		foreach ($array as $utf8) {
			if ($utf8 < 128) {
				$ascii .= chr($utf8);
			} elseif ($utf8 < 2048) {
				$ascii .= chr(192 + (($utf8 - ($utf8 % 64)) / 64));
				$ascii .= chr(128 + ($utf8 % 64));
			} else {
				$ascii .= chr(224 + (($utf8 - ($utf8 % 4096)) / 4096));
				$ascii .= chr(128 + ((($utf8 % 4096) - ($utf8 % 64)) / 64));
				$ascii .= chr(128 + ($utf8 % 64));
			}
		}
		return $ascii;
	}

}
