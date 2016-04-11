<?php

namespace Tools\Model\Entity;

use Cake\ORM\Entity as CakeEntity;

class Entity extends CakeEntity {

	/**
	 * The main method for any enumeration, should be called statically
	 * Now also supports reordering/filtering
	 *
	 * @link http://www.dereuromark.de/2010/06/24/static-enums-or-semihardcoded-attributes/
	 * @param string $value or array $keys or NULL for complete array result
	 * @param array $options (actual data)
	 * @param string|null $default
	 * @return mixed string/array
	 */
	public static function enum($value, array $options, $default = null) {
		if ($value !== null && !is_array($value)) {
			if (array_key_exists($value, $options)) {
				return $options[$value];
			}
			return $default;
		}
		if ($value !== null) {
			$newOptions = [];
			foreach ($value as $v) {
				$newOptions[$v] = $options[$v];
			}
			return $newOptions;
		}
		return $options;
	}

}
