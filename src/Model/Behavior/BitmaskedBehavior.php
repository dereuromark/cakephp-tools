<?php
namespace Tools\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use Cake\ORM\Query;

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
class BitmaskedBehavior extends Behavior {

	/**
	 * Default config
	 *
	 * @var array
	 */
	protected $_defaultConfig = array(
		'field' => 'status',
		'mappedField' => null, // NULL = same as above
		'bits' => null, // Method or callback
		'on' => 'validate', // on: save or validate
		'defaultValue' => null, // NULL = auto (use empty string to trigger "notEmpty" rule for "default NOT NULL" db fields)
	);

	/**
	 * Behavior configuration
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config = array()) {
		$config = $this->_config;

		if (empty($config['bits'])) {
			$config['bits'] = Inflector::pluralize($config['field']);
		}

		$entity = $this->_table->newEntity();
		//$entity = $this->_table->entityClass();
		//$entity = new $entity;

		if (is_callable($config['bits'])) {
			$config['bits'] = call_user_func($config['bits']);

		} elseif (is_string($config['bits']) && method_exists($entity, $config['bits'])) {
			$config['bits'] = $entity->{$config['bits']}();
		} elseif (!is_array($config['bits'])) {
			$config['bits'] = false;
		}
		if (empty($config['bits'])) {
			throw new \Exception('Bits not found');
		}
		ksort($config['bits'], SORT_NUMERIC);

		$this->_config = $config;
	}

	/**
	 * @param Event $event
	 * @param Query $query
	 * @return void
	 */
	public function beforeFind(Event $event, Query $query) {
		$this->encodeBitmaskConditions($query);

		$field = $this->_config['field'];
		if (!($mappedField = $this->_config['mappedField'])) {
			$mappedField = $field;
		}

		$mapper = function ($row, $key, $mr) use ($field, $mappedField) {
			//debug($mappedField);
			//debug($this->decodeBitmask($row->get($field)));
			$row->set($mappedField, $this->decodeBitmask($row->get($field)));
    };
    //$query->mapReduce($mapper);
	}

	/**
	 * @param Model $Model
	 * @param array $results
	 * @param bool $primary
	 * @return array
	 */
	public function __afterFind(Event $event, $results, $primary = false) {
		foreach ($results as $key => $result) {
			if (isset($result[$this->_table->alias()][$field])) {
				$results[$key][$this->_table->alias()][$mappedField] = $this->decodeBitmask($result[$this->_table->alias()][$field]);
			}
		}

		return $results;
	}

	/**
	 * @param Model $Model
	 * @param array $options
	 * @return bool Success
	 */
	public function beforeValidate(Event $event, Entity $entity) {
		if ($this->_config['on'] !== 'validate') {
			return true;
		}
		$this->encodeBitmaskData($entity);
		return true;
	}

	/**
	 * @param Model $Model
	 * @param array $options
	 * @return bool Success
	 */
	public function beforeSave(Event $event, Entity $entity, \ArrayObject $options) {
		if ($this->_config['on'] !== 'save') {
			return true;
		}
		$this->encodeBitmaskData($entity);
		return true;
	}

	/**
	 * @param Model $Model
	 * @param int $value Bitmask.
	 * @return array Bitmask array (from DB to APP).
	 */
	public function decodeBitmask($value) {
		$res = array();
		$value = (int)$value;
		foreach ($this->_config['bits'] as $key => $val) {
			$val = (($value & $key) !== 0) ? true : false;
			if ($val) {
				$res[] = $key;
			}
		}
		return $res;
	}

	/**
	 * @param array $value Bitmask array.
	 * @param array $defaultValue Default bitmask array.
	 * @return int Bitmask (from APP to DB).
	 */
	public function encodeBitmask($value, $defaultValue = null) {
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
	 * @return void
	 */
	public function encodeBitmaskConditions(Query $query) {
		$field = $this->_config['field'];
		if (!($mappedField = $this->_config['mappedField'])) {
			$mappedField = $field;
		}

		$where = $query->clause('where');
		if (!$where) {
			return;
		}

		$callable = function ($foo) use ($field, $mappedField) {
			if (!$foo instanceof \Cake\Database\Expression\Comparison) {
				return $foo;
			}
			$key = $foo->getField();
			if ($key === $mappedField || $key === $this->_table->alias() . '.' . $mappedField) {
				$foo->value($this->encodeBitmask($foo->getValue()));
			}
			if ($field !== $mappedField) {
				$foo->field($field);
			}

			return $foo;
		};

		$where->iterateParts($callable);
	}

	/**
	 * @param Entity $entity
	 * @return void
	 */
	public function encodeBitmaskData(Entity $entity) {
		$field = $this->_config['field'];
		if (!($mappedField = $this->_config['mappedField'])) {
			$mappedField = $field;
		}
		$default = null;
		$schema = $this->_table->schema()->column($field);

		if ($schema && isset($schema['default'])) {
			$default = $schema['default'];
		}
		if ($this->_config['defaultValue'] !== null) {
			$default = $this->_config['defaultValue'];
		}

		if ($entity->get($mappedField) !== null) {
			$entity->set($field, $this->encodeBitmask($entity->get($mappedField), $default));
		}
		if ($field !== $mappedField) {
			$entity->unsetProperty($mappedField);
		}
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @return array SQL snippet.
	 */
	public function isBit($bits) {
		$bits = (array)$bits;
		$bitmask = $this->encodeBitmask($bits);

		$field = $this->_config['field'];
		return array($this->_table->alias() . '.' . $field => $bitmask);
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @return array SQL snippet.
	 */
	public function isNotBit($bits) {
		return array('NOT' => $this->isBit($bits));
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @return array SQL snippet.
	 */
	public function containsBit($bits) {
		return $this->_containsBit($bits);
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @return array SQL snippet.
	 */
	public function containsNotBit($bits) {
		return $this->_containsBit($bits, false);
	}

	/**
	 * @param Model $Model
	 * @param mixed $bits (int, array)
	 * @param bool $contain
	 * @return array SQL snippet.
	 */
	protected function _containsBit($bits, $contain = true) {
		$bits = (array)$bits;
		$bitmask = $this->encodeBitmask($bits);

		$field = $this->_config['field'];
		$contain = $contain ? ' & ? = ?' : ' & ? != ?';
		return array('(' . $this->_table->alias() . '.' . $field . $contain . ')' => array($bitmask, $bitmask));
	}

}
