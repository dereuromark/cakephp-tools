<?php

namespace Tools\View\Helper;

use Cake\Utility\Hash;
use Cake\View\Helper\NumberHelper as CakeNumberHelper;

/**
 * Ovewrite to allow usage of own Number class.
 */
class NumberHelper extends CakeNumberHelper {

	/**
	 * NumberHelper::__construct()
	 *
	 * ### Settings:
	 *
	 * - `engine` Class name to use to replace Number functionality.
	 *            The class needs to be placed in the `Utility` directory.
	 *
	 * @param \Cake\View\View|null $View The View this helper is being attached to.
	 * @param array $options Configuration settings for the helper
	 * @throws \Cake\Core\Exception\Exception When the engine class could not be found.
	 */
	public function __construct($View = null, $options = []) {
		$options = Hash::merge(['engine' => 'Tools.Number'], $options);
		parent::__construct($View, $options);
	}


	/**
	 * Converts filesize from human readable string to bytes
	 *
	 * @param string $size Size in human readable string like '5MB', '5M', '500B', '50kb' etc.
	 * @param mixed $default Value to be returned when invalid size was used, for example 'Unknown type'
	 * @return mixed Number of bytes as integer on success, `$default` on failure if not false
	 * @throws CakeException On invalid Unit type.
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::fromReadableSize
	 */
	public static function fromReadableSize($size, $default = false) {
		if (ctype_digit($size)) {
			return (int)$size;
		}
		$size = strtoupper($size);

		$l = -2;
		$i = array_search(substr($size, -2), array('KB', 'MB', 'GB', 'TB', 'PB'));
		if ($i === false) {
			$l = -1;
			$i = array_search(substr($size, -1), array('K', 'M', 'G', 'T', 'P'));
		}
		if ($i !== false) {
			$size = substr($size, 0, $l);
			return $size * pow(1024, $i + 1);
		}

		if (substr($size, -1) === 'B' && ctype_digit(substr($size, 0, -1))) {
			$size = substr($size, 0, -1);
			return (int)$size;
		}

		if ($default !== false) {
			return $default;
		}
		throw new CakeException(__d('cake_dev', 'No unit type.'));
	}



}
