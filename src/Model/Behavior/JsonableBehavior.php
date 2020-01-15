<?php

namespace Tools\Model\Behavior;

use ArrayObject;
use Cake\Collection\CollectionInterface;
use Cake\Database\Type;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use RuntimeException;
use Shim\Database\Type\ArrayType;
use Tools\Utility\Text;

/**
 * A behavior that will json_encode (and json_decode) fields if they contain an array or specific pattern.
 *
 * This is a port of the Serializeable behavior by Matsimitsu (http://www.matsimitsu.nl)
 * Modified by Mark Scherer (http://www.dereuromark.de)
 *
 * Supports different input/output formats:
 * - "list" is useful as some kind of pseudo enums or simple lists
 * - "params" is useful for multiple key/value pairs
 * - can be used to create dynamic forms (and tables)
 * Also automatically cleans lists and works with custom separators etc
 *
 * Tip: If you have other behaviors that might modify the array data prior to saving, better use a higher priority:
 *   $this->addBehavior('Tools.Jsonable', ['priority' => 11, ...]);
 * So that it is run last.
 *
 * Usage: See docs
 *
 * @author PJ Hile (http://www.pjhile.com)
 * @author Mark Scherer
 * @license MIT
 */
class JsonableBehavior extends Behavior {

	/**
	 * @var string|false|null
	 */
	protected $decoded;

	/**
	 * //TODO: json input/ouput directly, clean
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'fields' => [], // Fields to convert
		'input' => 'array', // json, array, param, list (param/list only works with specific fields)
		'output' => 'array', // json, array, param, list (param/list only works with specific fields)
		'separator' => '|', // only for param or list
		'keyValueSeparator' => ':', // only for param
		'leftBound' => '{', // only for list
		'rightBound' => '}', // only for list
		'clean' => true, // only for param or list (autoclean values on insert)
		'sort' => false, // only for list
		'unique' => true, // only for list (autoclean values on insert),
		'map' => [], // map on a different DB field
		'encodeParams' => [ // params for json_encode
			'options' => null,
			'depth' => 512,
		],
		'decodeParams' => [ // params for json_decode
			'assoc' => true, // useful when working with multidimensional arrays
			'depth' => 512,
			'options' => 0,
		],
	];

	/**
	 * @param array $config
	 * @throws \RuntimeException
	 * @return void
	 */
	public function initialize(array $config): void {
		if (empty($this->_config['fields'])) {
			throw new RuntimeException('Fields are required');
		}
		if (!is_array($this->_config['fields'])) {
			$this->_config['fields'] = (array)$this->_config['fields'];
		}
		if (!is_array($this->_config['map'])) {
			$this->_config['map'] = (array)$this->_config['map'];
		}
		if (!empty($this->_config['map']) && count($this->_config['fields']) !== count($this->_config['map'])) {
			throw new RuntimeException('Fields and Map need to be of the same length if map is specified.');
		}
		foreach ($this->_config['fields'] as $field) {
			$this->_table->getSchema()->setColumnType($field, 'array');
		}
		if ($this->_config['encodeParams']['options'] === null) {
			$options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_ERROR_INF_OR_NAN | JSON_PARTIAL_OUTPUT_ON_ERROR;
			$this->_config['encodeParams']['options'] = $options;
		}

		Type::map('array', ArrayType::class);
	}

	/**
	 * Decode the fields on after find
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\ORM\Query $query
	 * @param \ArrayObject $options
	 * @param bool $primary
	 *
	 * @return void
	 */
	public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, $primary) {
		$query->formatResults(function (CollectionInterface $results) {
			return $results->map(function ($row) {
				if (!$row instanceof Entity) {
					return $row;
				}

				$this->decodeItems($row);
				return $row;
			});
		});
	}

	/**
	 * Decodes the fields of an array/entity (if the value itself was encoded)
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public function decodeItems(EntityInterface $entity) {
		$fields = $this->_getMappedFields();

		foreach ($fields as $map => $field) {
			$val = $entity->get($field);
			if ($this->isEncoded($val)) {
				$entity->set($map, $this->decoded);
			}
		}
	}

	/**
	 * Saves all fields that do not belong to the current Model into 'with' helper model.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
		$fields = $this->_getMappedFields();

		foreach ($fields as $map => $field) {
			if ($entity->get($map) === null) {
				continue;
			}
			$val = $entity->get($map);
			$entity->set($field, $this->_encode($val));
		}
	}

	/**
	 * @return array
	 */
	protected function _getMappedFields() {
		$usedFields = $this->_config['fields'];
		$mappedFields = $this->_config['map'];
		if (empty($mappedFields)) {
			$mappedFields = $usedFields;
		}

		$fields = [];

		foreach ($mappedFields as $index => $map) {
			if (empty($map) || $map == $usedFields[$index]) {
				$fields[$usedFields[$index]] = $usedFields[$index];
				continue;
			}
			$fields[$map] = $usedFields[$index];
		}
		return $fields;
	}

	/**
	 * @param array|string $val
	 * @return string|null
	 */
	public function _encode($val) {
		if (!empty($this->_config['fields'])) {
			if ($this->_config['input'] === 'param') {
				$val = $this->_fromParam($val);
			} elseif ($this->_config['input'] === 'list') {
				$val = $this->_fromList($val);
				if ($this->_config['unique']) {
					$val = array_unique($val);
				}
				if ($this->_config['sort']) {
					sort($val);
				}
			}
		}

		if (!is_array($val)) {
			return null;
		}

		return json_encode($val, $this->_config['encodeParams']['options'], $this->_config['encodeParams']['depth']);
	}

	/**
	 * Fields are absolutely necessary to function properly!
	 *
	 * @param string|array|null $val
	 * @return array|false|null
	 */
	public function _decode($val) {
		if (!is_string($val)) {
			return $val;
		}

		$decoded = json_decode($val, $this->_config['decodeParams']['assoc'], $this->_config['decodeParams']['depth'], $this->_config['decodeParams']['options']);

		if ($decoded === false) {
			return false;
		}
		if ($this->_config['decodeParams']['assoc']) {
			$decoded = (array)$decoded;
		}
		if ($this->_config['output'] === 'param') {
			$decoded = $this->_toParam($decoded);
		} elseif ($this->_config['output'] === 'list') {
			$decoded = $this->_toList($decoded);
		}
		return $decoded;
	}

	/**
	 * array() => param1:value1|param2:value2|...
	 *
	 * @param array $val
	 * @return string
	 */
	public function _toParam($val) {
		$res = [];
		foreach ($val as $key => $v) {
			$res[] = $key . $this->_config['keyValueSeparator'] . $v;
		}
		return implode($this->_config['separator'], $res);
	}

	/**
	 * @param string $val
	 *
	 * @return array
	 */
	public function _fromParam($val) {
		$leftBound = $this->_config['leftBound'];
		$rightBound = $this->_config['rightBound'];
		$separator = $this->_config['separator'];

		$res = [];
		$pieces = Text::tokenize($val, $separator, $leftBound, $rightBound);
		foreach ($pieces as $piece) {
			$subpieces = Text::tokenize($piece, $this->_config['keyValueSeparator'], $leftBound, $rightBound);
			if (count($subpieces) < 2) {
				continue;
			}
			$res[$subpieces[0]] = $subpieces[1];
		}
		return $res;
	}

	/**
	 * array() => value1|value2|value3|...
	 *
	 * @param string[] $val
	 * @return string
	 */
	public function _toList($val) {
		return implode($this->_config['separator'], $val);
	}

	/**
	 * @param string $val
	 *
	 * @return string[]
	 */
	public function _fromList($val) {
		$separator = $this->_config['separator'];
		$leftBound = $this->_config['leftBound'];
		$rightBound = $this->_config['rightBound'];

		return (array)Text::tokenize($val, $separator, $leftBound, $rightBound);
	}

	/**
	 * Checks if string is encoded array/object
	 *
	 * @param string $str String to check
	 * @return bool
	 */
	public function isEncoded($str) {
		$this->decoded = $this->_decode($str);

		if ($this->decoded !== false) {
			return true;
		}
		return false;
	}

}
