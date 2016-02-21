<?php

namespace Tools\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type;
use PDO;

/**
 * Experimental year type (MySQL)
 *
 * Needs:
 * - Type::map('year', 'Tools\Database\Type\YearType'); in bootstrap
 * - Manual FormHelper $this->Form->input('published', ['type' => 'year']);
 */
class YearType extends Type {

	/**
	 * Date format for DateTime object
	 *
	 * @var string
	 */
	protected $_format = 'Y';

	/**
	 * Convert binary data into the database format.
	 *
	 * Binary data is not altered before being inserted into the database.
	 * As PDO will handle reading file handles.
	 *
	 * @param string|resource $value The value to convert.
	 * @param \Cake\Database\Driver $driver The driver instance to convert with.
	 * @return string|resource
	 */
	public function toDatabase($value, Driver $driver) {
		if (is_array($value)) {
			$value = $value['year'];
		}
		if ($value === null || !(int)$value) {
			return null;
		}
		return $value;
	}

	/**
	 * Convert binary into resource handles
	 *
	 * @param null|string|resource $value The value to convert.
	 * @param \Cake\Database\Driver $driver The driver instance to convert with.
	 * @return resource|null
	 * @throws \Cake\Core\Exception\Exception
	 */
	public function toPHP($value, Driver $driver) {
		if ($value === null || !(int)$value) {
			return null;
		}
		return $value;
	}

	/**
	 * Get the correct PDO binding type for Year data.
	 *
	 * @param mixed $value The value being bound.
	 * @param \Cake\Database\Driver $driver The driver.
	 * @return int
	 */
	public function toStatement($value, Driver $driver) {
		return PDO::PARAM_INT;
	}

}
