<?php
namespace Tools\Utility;

/**
 * BC wrapper for 2.x methods until they can be rewritten.
 */
class Set {

	/**
	 * Pushes the differences in $array2 onto the end of $array
	 *
	 * @param array $array Original array
	 * @param array $array2 Differences to push
	 * @return array Combined array
	 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::pushDiff
	 */
	public static function pushDiff($array, $array2) {
		if (empty($array) && !empty($array2)) {
			return $array2;
		}
		if (!empty($array) && !empty($array2)) {
			foreach ($array2 as $key => $value) {
				if (!array_key_exists($key, $array)) {
					$array[$key] = $value;
				} else {
					if (is_array($value)) {
						$array[$key] = Set::pushDiff($array[$key], $array2[$key]);
					}
				}
			}
		}
		return $array;
	}

}
