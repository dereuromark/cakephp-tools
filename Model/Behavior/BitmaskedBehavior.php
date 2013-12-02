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
 * @license MIT
 * @link http://www.dereuromark.de/2012/02/26/bitmasked-using-bitmasks-in-cakephp/
 */
class BitmaskedBehavior extends ModelBehavior {

	/**
	 * Settings defaults
	 *
	 * @var array
	 */
	protected $_defaults = array(
		'field' => 'status',
		'mappedField' => null, // NULL = same as above
		'bits' => null,
		'before' => 'validate', // on: save or validate
		'defaultValue' => null, // NULL = auto (use empty string to trigger "notEmpty" rule for "default NOT NULL" db fields)
	);

	/**
	 * Behavior configuration
	 *
	 * @param Model $Model
	 * @param array $config
	 * @return void
	 */
	public function setup(Model $Model, $config = array()) {
		$config = array_merge($this->_defaults, $config);

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

		$this->settings[$Model->alias] = $config;
	}

	/**
	 * @param Model $Model
	 * @param array $query
	 * @return array
	 */
	public function beforeFind(Model $Model, $query) {
		$field = $this->settings[$Model->alias]['field'];

		if (isset($query['conditions']) && is_array($query['conditions'])) {
			$query['conditions'] = $this->encodeBitmaskConditions($Model, $query['conditions']);
		}

		return $query;
	}

	/**
	 * @param Model $Model
	 * @param array $results
	 * @param boolean $primary
	 * @return array
	 */
	public function afterFind(Model $Model, $results, $primary = false) {
		$field = $this->settings[$Model->alias]['field'];
		if (!($mappedField = $this->settings[$Model->alias]['mappedField'])) {
			$mappedField = $field;
		}

		foreach ($results as $key => $result) {
			if (isset($result[$Model->alias][$field])) {
				$results[$key][$Model->alias][$mappedField] = $this->decodeBitmask($Model, $result[$Model->alias][$field]);
			}
		}

		return $results;
	}

	/**
	 * @param Model $Model
	 * @param array $options
	 * @return boolean Success
	 */
	public function beforeValidate(Model $Model, $options = array()) {
		if ($this->settings[$Model->alias]['before'] !== 'validate') {
			return true;
		}
		$this->encodeBitmaskData($Model);
		return true;
	}

	/**
	 * @param Model $Model
	 * @param array $options
	 * @return boolean Success
	 */
	public function beforeSave(Model $Model, $options = array()) {
		if ($this->settings[$Model->alias]['before'] !== 'save') {
			return true;
		}
		$this->encodeBitmaskData($Model);
		return true;
	}

	/**
	 * @param Model $Model
	 * @param integer $value Bitmask.
	 * @return array Bitmask array (from DB to APP).
	 */
	public function decodeBitmask(Model $Model, $value) {
		$res = array();
		$value = (int)$value;
		foreach ($this->settings[$Model->alias]['bits'] as $key => $val) {
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
	 * @param array $defaultValue Default bitmask array.
	 * @return integer Bitmask (from APP to DB).
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
		$field = $this->settings[$Model->alias]['field'];
		if (!($mappedField = $this->settings[$Model->alias]['mappedField'])) {
			$mappedField = $field;
		}

		foreach ($conditions as $key => $val) {
			if ($key === $mappedField) {
				$conditions[$field] = $this->encodeBitmask($Model, $val);
				if ($field !== $mappedField) {
					unset($conditions[$mappedField]);
				}
				continue;
			} elseif ($key === $Model->alias . '.' . $mappedField) {
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
		return $conditions;
	}

	/**
	 * @param Model $Model
	 * @return void
	 */
	public function encodeBitmaskData(Model $Model) {
		$field = $this->settings[$Model->alias]['field'];
		if (!($mappedField = $this->settings[$Model->alias]['mappedField'])) {
			$mappedField = $field;
		}
		$default = null;
		$schema = $Model->schema($field);
		if ($schema && isset($schema['default'])) {
			$default = $schema['default'];
		}
		if ($this->settings[$Model->alias]['defaultValue'] !== null) {
			$default = $this->settings[$Model->alias]['defaultValue'];
		}

		if (isset($Model->data[$Model->alias][$mappedField])) {
			$Model->data[$Model->alias][$field] = $this->encodeBitmask($Model, $Model->data[$Model->alias][$mappedField], $default);
		}
		if ($field !== $mappedField) {
			unset($Model->data[$Model->alias][$mappedField]);
		}
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @return array SQL snippet.
	 */
	public function isBit(Model $Model, $bits) {
		$bits = (array)$bits;
		$bitmask = $this->encodeBitmask($Model, $bits);

		$field = $this->settings[$Model->alias]['field'];
		return array($Model->alias . '.' . $field => $bitmask);
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @return array SQL snippet.
	 */
	public function isNotBit(Model $Model, $bits) {
		return array('NOT' => $this->isBit($Model, $bits));
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @return array SQL snippet.
	 */
	public function containsBit(Model $Model, $bits) {
		return $this->_containsBit($Model, $bits);
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @return array SQL snippet.
	 */
	public function containsNotBit(Model $Model, $bits) {
		return $this->_containsBit($Model, $bits, false);
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @param boolean $contain
	 * @return array SQL snippet.
	 */
	protected function _containsBit(Model $Model, $bits, $contain = true) {
		$bits = (array)$bits;
		$bitmask = $this->encodeBitmask($Model, $bits);

		$field = $this->settings[$Model->alias]['field'];
		$contain = $contain ? ' & ? = ?' : ' & ? != ?';
		return array('(' . $Model->alias . '.' . $field . $contain . ')' => array($bitmask, $bitmask));
	}

}
