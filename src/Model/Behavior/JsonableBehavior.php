<?php

namespace Tools\Model\Behavior;

use ArrayObject;
use Cake\Database\Type;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Exception;
use Tools\Utility\Text;

/**
 * A behavior that will json_encode (and json_decode) fields if they contain an array or specific pattern.
 *
 * Requires: PHP 5 >= 5.4.0 or PECL json >= 1.2.0
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
 *   $this->addBehavior('Tools.Jsonable', array('priority' => 11, ...));
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
	public $decoded = null;

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
			'options' => 0,
			'depth' => 512,
		],
		'decodeParams' => [ // params for json_decode
			'assoc' => true, // useful when working with multidimensional arrays
			'depth' => 512,
			'options' => 0
		]
	];

	/**
	 * JsonableBehavior::initialize()
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config = []) {
		Type::map('array', 'Tools\Database\Type\ArrayType');
		if (empty($this->_config['fields'])) {
			throw new Exception('Fields are required');
		}
		if (!is_array($this->_config['fields'])) {
			$this->_config['fields'] = (array)$this->_config['fields'];
		}
		if (!is_array($this->_config['map'])) {
			$this->_config['map'] = (array)$this->_config['map'];
		}
		if (!empty($this->_config['map']) && count($this->_config['fields']) !== count($this->_config['map'])) {
			throw new Exception('Fields and Map need to be of the same length if map is specified.');
		}
		foreach ($this->_config['fields'] as $field) {
			$this->_table->schema()->columnType($field, 'array');
		}
	}

	/**
	 * Decode the fields on after find
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Cake\ORM\Query $query
	 * @return void
	 */
	public function beforeFind(Event $event, Query $query) {
		$query->formatResults(function ($results) {
			return $results->map(function ($row) {
				if (!$row instanceOf Entity) {
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
	 * @param \Cake\ORM\Entity $entity
	 * @return void
	 */
	public function decodeItems(Entity $entity) {
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
	 * @param \Cake\Event\Event $event
	 * @param \Cake\ORM\Entity $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		$fields = $this->_getMappedFields();

		foreach ($fields as $map => $field) {
			if (!$entity->get($map)) {
				continue;
			}
			$val = $entity->get($map);
			$entity->set($field, $this->_encode($val));
		}
	}

	/**
	 * JsonableBehavior::_getMappedFields()
	 *
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
	 * JsonableBehavior::_encode()
	 *
	 * @param mixed $val
	 * @return string
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

		if (is_array($val)) {
			// Depth param added in PHP 5.5
			if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
				$val = json_encode($val, $this->_config['encodeParams']['options'], $this->_config['encodeParams']['depth']);
			} else {
				$val = json_encode($val, $this->_config['encodeParams']['options']);
			}
		}

		return $val;
	}

	/**
	 * Fields are absolutely necessary to function properly!
	 *
	 * @param mixed $val
	 * @return mixed
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
	 * @param array $val
	 * @return string
	 */
	public function _toList($val) {
		return implode($this->_config['separator'], $val);
	}

	/**
	 * @param string $val
	 *
	 * @return array
	 */
	public function _fromList($val) {
		extract($this->_config);

		return Text::tokenize($val, $separator, $leftBound, $rightBound);
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
