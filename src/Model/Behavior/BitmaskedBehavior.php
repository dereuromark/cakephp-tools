<?php

namespace Tools\Model\Behavior;

use ArrayObject;
use BackedEnum;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
use ReflectionEnum;
use ReflectionException;
use RuntimeException;

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
 * @author Mark Scherer
 * @license MIT
 * @link https://www.dereuromark.de/2012/02/26/bitmasked-using-bitmasks-in-cakephp/
 */
class BitmaskedBehavior extends Behavior {

	/**
	 * Default config
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'field' => 'status',
		'mappedField' => null, // NULL = same as above
		'bits' => null, // Enum, method or callback to get the bits data
		'enum' => null, // Set to Enum class to use backed enum collection instead of plain scalar array, false to disable auto-detect
		'on' => 'beforeMarshal', // or beforeRules or beforeSave
		'defaultValue' => null, // NULL = auto (use empty string to trigger "notEmpty" rule for "default NOT NULL" db fields)
		'implementedFinders' => [
			'bits' => 'findBitmasked',
		],
		'type' => null, // Auto-defaults to current default "exact", set to "contain" for contain mode
		'containMode' => 'or', // Use "and" when a record must match all bits
	];

	/**
	 * Behavior configuration
	 *
	 * @param array $config
	 * @throws \RuntimeException
	 * @return void
	 */
	public function initialize(array $config): void {
		$config += $this->_config;
		if (empty($config['bits'])) {
			$config['bits'] = Inflector::variable(Inflector::pluralize($config['field']));
		}

		$entity = $this->_table->newEmptyEntity();
		$enumClass = false;

		if (is_string($config['bits'])) {
			try {
				$reflectionEnum = new ReflectionEnum($config['bits']);
				$cases = [];
				foreach ($reflectionEnum->getCases() as $case) {
					/** @var \BackedEnum $intBackedEnum */
					$intBackedEnum = $case->getValue();
					$cases[$intBackedEnum->value] = $intBackedEnum->name;
				}
				$enumClass = $config['bits'];
				$config['bits'] = $cases;
			} catch (ReflectionException) {
			}
		}
		if ($config['enum'] === null) {
			$config['enum'] = $enumClass;
		}

		if (is_callable($config['bits'])) {
			$config['bits'] = call_user_func($config['bits']);
		} elseif (is_string($config['bits']) && method_exists($entity, $config['bits'])) {
			$method = $config['bits'];
			$config['bits'] = $entity::$method();
		} elseif (is_string($config['bits']) && method_exists($this->_table, $config['bits'])) {
			// Deprecated: Will be removed in the next major, use Entity instead.
			$table = $this->_table;
			$method = $config['bits'];
			$config['bits'] = $table::$method();
		} elseif (!is_array($config['bits'])) {
			$config['bits'] = false;
		}
		if (empty($config['bits'])) {
			$method = Inflector::variable(Inflector::pluralize($config['field'])) . '()';

			throw new RuntimeException('Bits not found for field ' . $config['field'] . ', expected pluralized static method ' . $method . ' on the entity.');
		}
		ksort($config['bits'], SORT_NUMERIC);

		$this->_config = $config;
	}

	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array<int> $bits
	 * @param array<string, mixed> $options
	 * @throws \InvalidArgumentException If the 'slug' key is missing in options
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findBitmasked(SelectQuery $query, array $bits, array $options = []): SelectQuery {
		$options += [
			'type' => $this->_config['type'] ?? 'exact',
			'containMode' => $this->_config['containMode'],
		];

		if ($options['type'] === 'contain') {
			if (!$bits) {
				$field = $this->_config['field'];

				return $query->where([$this->_table->getAlias() . '.' . $field => $this->_getDefaultValue($field)]);
			}

			if ($options['containMode'] === 'and') {
				$encodedBits = $this->encodeBitmask($bits);

				return $query->where($this->containsBit($encodedBits));
			}

			$conditions = [];
			foreach ($bits as $bit) {
				$conditions[] = $this->containsBit($bit);
			}

			return $query->where(['OR' => $conditions]);
		}

		$encodedBits = $this->encodeBitmask($bits);
		if ($encodedBits === null) {
			$field = $this->getConfig('field');
			$encodedBits = $this->_getDefaultValue($field);
		}

		return $query->where([$this->_table->getAlias() . '.' . $this->_config['field'] . ' IS' => $encodedBits]);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \ArrayObject $options
	 * @param bool $primary
	 *
	 * @return void
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, $primary): void {
		$this->encodeBitmaskConditions($query);

		$field = $this->_config['field'];
		$mappedField = $this->_config['mappedField'];
		if (!$mappedField) {
			$mappedField = $field;
		}

		$mapper = function ($row, $key, $mr) use ($field, $mappedField) {
			/**
			 * @var \Cake\Collection\Iterator\MapReduce $mr
			 * @var \Cake\Datasource\EntityInterface|array $row
			 */
			if (!is_object($row)) {
				if (isset($row[$field])) {
					$row[$mappedField] = $this->decodeBitmask($row[$field]);
				}
				$mr->emit($row);

				return;
			}

			/** @var \Cake\Datasource\EntityInterface $entity */
			$entity = $row;
			if ($entity->has($field)) {
				$entity->set($mappedField, $this->decodeBitmask($entity->get($field)));
				$entity->setDirty($mappedField, false);
			}
			$mr->emit($entity);
		};
		$query->mapReduce($mapper);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		if ($this->_config['on'] !== 'beforeMarshal') {
			return;
		}
		$this->encodeBitmaskDataRaw($data);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function afterMarshal(EventInterface $event, EntityInterface $entity, ArrayObject $data, ArrayObject $options): void {
		if ($this->_config['on'] !== 'afterMarshal') {
			return;
		}
		$this->encodeBitmaskData($entity);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @param string $operation
	 *
	 * @return void
	 */
	public function beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, $operation): void {
		if ($this->_config['on'] !== 'beforeRules' || !$options['checkRules']) {
			return;
		}
		$this->encodeBitmaskData($entity);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if ($this->_config['on'] !== 'beforeSave') {
			return;
		}
		$this->encodeBitmaskData($entity);
	}

	/**
	 * @param string|int $value Bitmask.
	 * @return array<int|\BackedEnum> Bitmask array (from DB to APP).
	 */
	public function decodeBitmask($value): array {
		$res = [];
		$value = (int)$value;

		$enum = $this->_config['enum'];
		foreach ($this->_config['bits'] as $key => $val) {
			$val = ($value & $key) !== 0;
			if ($val) {
				$res[] = $enum ? $enum::tryFrom($key) : $key;
			}
		}

		return $res;
	}

	/**
	 * @param array<int|string>|string $value Bitmask array.
	 * @param int|null $defaultValue Default bitmask value.
	 * @return int|null Bitmask (from APP to DB).
	 */
	public function encodeBitmask(array|string $value, $defaultValue = null): ?int {
		$res = 0;
		if (!$value) {
			return $defaultValue;
		}

		foreach ((array)$value as $val) {
			if ($val instanceof BackedEnum) {
				$val = $val->value;
			}

			$res |= (int)$val;
		}
		if ($res === 0) {
			return $defaultValue; // Make sure notEmpty validation rule triggers
		}

		return $res;
	}

	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return void
	 */
	public function encodeBitmaskConditions(SelectQuery $query) {
		$field = $this->_config['field'];
		$mappedField = $this->_config['mappedField'];
		if (!$mappedField) {
			$mappedField = $field;
		}

		$where = $query->clause('where');
		if (!$where) {
			return;
		}

		$callable = function ($comparison) use ($field, $mappedField) {
			if (!$comparison instanceof ComparisonExpression) {
				return $comparison;
			}
			$key = $comparison->getField();

			if ($key !== $mappedField && $key !== $this->_table->getAlias() . '.' . $mappedField) {
				return $comparison;
			}

			$bitmask = $this->encodeBitmask($comparison->getValue());
			$comparison->setValue((array)$bitmask);
			if ($field !== $mappedField) {
				$comparison->setField($field);
			}

			return $comparison;
		};

		$where->iterateParts($callable);
	}

	/**
	 * @param \ArrayObject $data
	 * @return void
	 */
	public function encodeBitmaskDataRaw(ArrayObject $data) {
		$field = $this->_config['field'];
		$mappedField = $this->_config['mappedField'];
		if (!$mappedField) {
			$mappedField = $field;
		}
		$default = $this->_getDefault($field);

		if (!isset($data[$mappedField])) {
			return;
		}

		$data[$field] = $this->encodeBitmask($data[$mappedField], $default);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public function encodeBitmaskData(EntityInterface $entity) {
		$field = $this->_config['field'];
		$mappedField = $this->_config['mappedField'];
		if (!$mappedField) {
			$mappedField = $field;
		}
		$default = $this->_getDefault($field);

		if ($entity->get($mappedField) === null) {
			return;
		}

		$entity->set($field, $this->encodeBitmask($entity->get($mappedField), $default));
		if ($field !== $mappedField) {
			$entity->unset($mappedField);
		}
	}

	/**
	 * @param string $field
	 *
	 * @return int|null
	 */
	protected function _getDefault(string $field): ?int {
		$default = null;
		$schema = $this->_table->getSchema()->getColumn($field);

		if ($schema && isset($schema['default'])) {
			$default = $schema['default'];
		}
		if ($this->_config['defaultValue'] !== null) {
			$default = $this->_config['defaultValue'];
		}

		return $default;
	}

	/**
	 * @param array<int>|int $bits
	 * @return array SQL snippet.
	 */
	public function isBit($bits) {
		$bits = (array)$bits;
		$bitmask = $this->encodeBitmask($bits);

		$field = $this->_config['field'];

		return [$this->_table->getAlias() . '.' . $field => $bitmask];
	}

	/**
	 * @param array<int>|int $bits
	 * @return array SQL snippet.
	 */
	public function isNotBit($bits) {
		return ['NOT' => $this->isBit($bits)];
	}

	/**
	 * @param array<int>|int $bits
	 * @return array SQL snippet.
	 */
	public function containsBit($bits) {
		return $this->_containsBit($bits);
	}

	/**
	 * @param array<int>|int $bits
	 * @return array SQL snippet.
	 */
	public function containsNotBit($bits) {
		return $this->_containsBit($bits, false);
	}

	/**
	 * @param array<int>|int $bits
	 * @param bool $contain
	 * @return array SQL snippet.
	 */
	protected function _containsBit($bits, $contain = true) {
		$bits = (array)$bits;
		$bitmask = $this->encodeBitmask($bits);

		$field = $this->_config['field'];
		if ($bitmask === null) {
			$emptyValue = $this->_getDefaultValue($field);

			return [$this->_table->getAlias() . '.' . $field . ' IS' => $emptyValue];
		}

		$contain = $contain ? ' & %s = %s' : ' & %s != %s';
		$contain = sprintf($contain, (string)$bitmask, (string)$bitmask);

		// Hack for Postgres for now
		$connection = $this->_table->getConnection();
		$config = $connection->config();
		if ((str_contains($config['driver'], 'Postgres'))) {
			return ['("' . $this->_table->getAlias() . '"."' . $field . '"' . $contain . ')'];
		}

		return ['(' . $this->_table->getAlias() . '.' . $field . $contain . ')'];
	}

	/**
	 * @param string $field
	 *
	 * @return int|null
	 */
	protected function _getDefaultValue(string $field): ?int {
		$schema = $this->_table->getSchema()->getColumn($field);

		return $schema['default'] ?: 0;
	}

}
