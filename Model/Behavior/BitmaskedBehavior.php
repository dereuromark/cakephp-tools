<?php
App::uses('ModelBehavior', 'Model');

/**
 * BitmaskedBehavior
 *
 * An implementation of bitwise masks for row-level operations.
 * You can submit/register flags in different ways. The easiest way is using a static model function.
 * It should contain the bits like so (starting with 1):
 *   1 => w, 2 => x, 4 => y, 8 => z, ... (bits as keys - names as values)
 * The order doesn't matter, as long as no bit is used twice.
 *
 * The theoretical limit for a 64-bit integer would be 64 bits (2^64).
 * But if you actually seem to need more than a hand full you
 * obviously do something wrong and should better use a joined table etc.
 *
 * @version 1.1
 * @author Mark Scherer
 * @cake 2.x
 * @license http://opensource.org/licenses/mit-license.php MIT
 * @link http://www.dereuromark.de/2012/02/26/bitmasked-using-bitmasks-in-cakephp/
 */
class BitmaskedBehavior extends ModelBehavior {

	/**
	 * Default config
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'field' => 'status',
		'mappedField' => null, // NULL = same as above
		'bits' => null,
		'before' => 'validate', // on: save or validate
		'defaultValue' => null, // NULL = auto (use empty string to trigger "notEmpty" rule for "default NOT NULL" db fields)
	];

	/**
	 * Behavior configuration
	 * Setup example:
	 * <code>
	 * public $actsAs = array(
	 * 	'Tools.Bitmasked' => [
	 * 		[
	 * 			'mappedField' => 'weekdays',
	 * 			'field' => 'weekday'
	 * 		],
	 * 		[
	 * 			'mappedField' => 'monthdays',
	 * 			'field' => 'monthday'
	 * 		]
	 * 	]
	 * ];
	 * </code>
	 *
	 * @param Model $Model
	 * @param array $config
	 * @return void
	 */
	public function setup(Model $Model, $config = []) {
		if (is_array(reset($config))) {
			foreach ($config as $fieldConfig) {
				$fieldConfig += $this->_defaultConfig;
				$fieldName = $fieldConfig['field'];
				$this->settings[$Model->alias][$fieldName] = $this->_getFieldConfig($Model, $fieldConfig);
			}
		} else {
			$config += $this->_defaultConfig;
			$fieldName = $config['field'];
			$this->settings[$Model->alias][$fieldName] = $this->_getFieldConfig($Model, $config);
		}
	}

	/**
	 * Generates settings array for a single bitmasked field.
	 *
	 * @param Model $Model
	 * @param array $config configuration of a single bitmasked field.
	 * @throws InternalErrorException
	 * @return array
	 */
	protected function _getFieldConfig(Model $Model, $config) {
		if (empty($config['bits'])) {
			$config['bits'] = Inflector::pluralize($config['field']);
		}
		if (is_callable($config['bits'])) {
			$config['bits'] = call_user_func($config['bits']);
		} elseif (is_string($config['bits']) && method_exists($Model, $config['bits'])) {
			$config['bits'] = $Model->{$config['bits']}();
		} elseif (!is_array($config['bits'])) {
			$config['bits'] = false;
		}
		if (empty($config['bits'])) {
			throw new InternalErrorException('Bits not found');
		}
		ksort($config['bits'], SORT_NUMERIC);
		return $config;
	}

	/**
	 * @param Model $Model
	 * @param array $query
	 * @return array
	 */
	public function beforeFind(Model $Model, $query) {
		if (isset($query['conditions']) && is_array($query['conditions'])) {
			$query['conditions'] = $this->encodeBitmaskConditions($Model, $query['conditions']);
		}
		return $query;
	}

	/**
	 * @param Model $Model
	 * @param array $results
	 * @param bool $primary
	 * @return array
	 */
	public function afterFind(Model $Model, $results, $primary = false) {
		foreach ($this->settings[$Model->alias] as $fieldConfig) {
			$field = $fieldConfig['field'];
			if (empty($fieldConfig['mappedField'])) {
				$mappedField = $field;
			} else {
				$mappedField = $fieldConfig['mappedField'];
			}
			foreach ($results as $key => $result) {
				if (isset($result[$Model->alias][$field])) {
					$results[$key][$Model->alias][$mappedField] = $this->decodeBitmask($Model, $result[$Model->alias][$field], $field);
				}
			}
		}
		return $results;
	}

	/**
	 * @param Model $Model
	 * @param array $options
	 * @return bool Success
	 */
	public function beforeValidate(Model $Model, $options = []) {
		foreach ($this->settings[$Model->alias] as $fieldConfig) {
			if ($fieldConfig['before'] === 'validate') {
				$this->encodeBitmaskData($Model);
			}
		}
		return true;
	}

	/**
	 * @param Model $Model
	 * @param array $options
	 * @return bool Success
	 */
	public function beforeSave(Model $Model, $options = []) {
		foreach ($this->settings[$Model->alias] as $fieldConfig) {
			if ($fieldConfig['before'] === 'save') {
				$this->encodeBitmaskData($Model);
			}
		}
		return true;
	}

	/**
	 * Gets the name of the first field name.
	 *
	 * @param Model $Model
	 * @return string
	 */
	protected function _getFieldName(Model $Model) {
		$firstField = reset($this->settings[$Model->alias]);
		return $firstField['field'];
	}

	/**
	 * @param Model $Model
	 * @param int $value Bitmask.
	 * @param string|null $fieldName field name.
	 * @return array Bitmask array (from DB to APP).
	 */
	public function decodeBitmask(Model $Model, $value, $fieldName = null) {
		if (empty($fieldName)) {
			$fieldName = $this->_getFieldName($Model);
		}
		$res = [];
		$value = (int)$value;
		foreach ($this->settings[$Model->alias][$fieldName]['bits'] as $key => $val) {
			$val = (($value & $key) !== 0) ? true : false;
			if ($val) {
				$res[] = $key;
			}
		}
		return $res;
	}

	/**
	 * @param Model $Model
	 * @param array $value Bitmask array.
	 * @param int|null $defaultValue Default bitmask value.
	 * @return int|null Bitmask (from APP to DB).
	 */
	public function encodeBitmask(Model $Model, $value, $defaultValue = null) {
		$res = 0;
		if (empty($value)) {
			return $defaultValue;
		}
		foreach ((array)$value as $key => $val) {
			$res |= (int)$val;
		}
		if ($res === 0) {
			return $defaultValue; // make sure notEmpty validation rule triggers
		}
		return $res;
	}

	/**
	 * @param Model $Model
	 * @param array $conditions
	 * @return array Conditions.
	 */
	public function encodeBitmaskConditions(Model $Model, $conditions) {
		foreach ($this->settings[$Model->alias] as $fieldConfig) {
			$field = $fieldConfig['field'];
			if (empty($fieldConfig['mappedField'])) {
				$mappedField = $field;
			} else {
				$mappedField = $fieldConfig['mappedField'];
			}
			foreach ($conditions as $key => $val) {
				if ($key === $mappedField) {
					$conditions[$field] = $this->encodeBitmask($Model, $val);
					if ($field !== $mappedField) {
						unset($conditions[$mappedField]);
					}
					continue;
				}
				if ($key === $Model->alias . '.' . $mappedField) {
					$conditions[$Model->alias . '.' . $field] = $this->encodeBitmask($Model, $val);
					if ($field !== $mappedField) {
						unset($conditions[$Model->alias . '.' . $mappedField]);
					}
					continue;
				}
				if (!is_array($val)) {
					continue;
				}
				$conditions[$key] = $this->encodeBitmaskConditions($Model, $val);
			}
		}
		return $conditions;
	}

	/**
	 * @param Model $Model
	 * @return void
	 */
	public function encodeBitmaskData(Model $Model) {
		foreach ($this->settings[$Model->alias] as $fieldConfig) {
			$field = $fieldConfig['field'];
			$mappedField = $fieldConfig['mappedField'];
			if (!$mappedField) {
				$mappedField = $field;
			}
			$default = null;
			$schema = $Model->schema($field);
			if ($schema && isset($schema['default'])) {
				$default = $schema['default'];
			}
			if ($fieldConfig['defaultValue'] !== null) {
				$default = $fieldConfig['defaultValue'];
			}
			if (isset($Model->data[$Model->alias][$mappedField])) {
				$Model->data[$Model->alias][$field] = $this->encodeBitmask($Model, $Model->data[$Model->alias][$mappedField], $default);
			}
			if ($field !== $mappedField) {
				unset($Model->data[$Model->alias][$mappedField]);
			}
		}
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @param string|null $fieldName field name.
	 * @return array SQL snippet.
	 */
	public function isBit(Model $Model, $bits, $fieldName = null) {
		if (empty($fieldName)) {
			$fieldName = $this->_getFieldName($Model);
		}
		$bits = (array)$bits;
		$bitmask = $this->encodeBitmask($Model, $bits);
		return [$Model->alias . '.' . $fieldName => $bitmask];
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @param string|null $fieldName field name.
	 * @return array SQL snippet.
	 */
	public function isNotBit(Model $Model, $bits, $fieldName = null) {
		return ['NOT' => $this->isBit($Model, $bits, $fieldName)];
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @param string|null $fieldName field name.
	 * @return array SQL snippet.
	 */
	public function containsBit(Model $Model, $bits, $fieldName = null) {
		return $this->_containsBit($Model, $bits, $fieldName);
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @param string|null $fieldName field name.
	 * @return array SQL snippet.
	 */
	public function containsNotBit(Model $Model, $bits, $fieldName = null) {
		return $this->_containsBit($Model, $bits, $fieldName, false);
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @param string $fieldName field name.
	 * @param bool $contain
	 * @return array SQL snippet.
	 */
	protected function _containsBit(Model $Model, $bits, $fieldName, $contain = true) {
		if (empty($fieldName)) {
			$fieldName = $this->_getFieldName($Model);
		}
		$bits = (array)$bits;
		$bitmask = $this->encodeBitmask($Model, $bits);
		$contain = $contain ? ' & ? = ?' : ' & ? != ?';
		return ['(' . $Model->alias . '.' . $fieldName . $contain . ')' => [$bitmask, $bitmask]];
	}

}
