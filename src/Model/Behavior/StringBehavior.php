<?php

namespace Tools\Model\Behavior;

use ArrayObject;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * A behavior that will apply basic string operations for your input.
 *
 * Note that most string modification should be done once, on save.
 * Prevent using output modification if possible as it is done on every fetch.
 *
 * Tip: If you have other behaviors that might modify the array data prior to saving, better use a higher priority:
 *   $this->addBehavior('Tools.String', array('priority' => 11, ...));
 * So that it is run last.
 *
 * Usage: See docs
 *
 * @author Mark Scherer
 * @license MIT
 */
class StringBehavior extends Behavior {

	/**
	 * //TODO: json input/ouput directly, clean
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'fields' => [], // Fields to convert
		'input' => [], // Basic input filters
		'output' => [], // Basic output filters
	];

	/**
	 * JsonableBehavior::initialize()
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config = []) {
	}

	/**
	 * Decode the fields on after find
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Cake\ORM\Query $query
	 * @return void
	 */
	public function beforeFind(Event $event, Query $query) {
		$query->formatResults(function (ResultSetInterface $results) {
			return $results->map(function ($row) {
				//TODO?
				//$this->processItems($row, 'output');
				return $row;
			});
		});
	}

	/**
	 * Decodes the fields of an array/entity (if the value itself was encoded)
	 *
	 * @param \Cake\ORM\Entity $entity
	 * @param string $type Type (input/output)
	 * @return void
	 */
	public function processItems(Entity $entity, $type = 'input') {
		$fields = $this->_config['fields'];

		foreach ($fields as $field => $map) {
			if (is_numeric($field)) {
				$field = $map;
				$map = [];
			} else {
				$map = (array)$map;
			}

			$val = $entity->get($field);
			if (!$val && !is_numeric($val)) {
				continue;
			}

			if (!$map) {
				$map = $this->_config[$type];
			}
			if (!$map) {
				continue;
			}

			$entity->set($field, $this->_process($val, $map));
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
		$this->processItems($entity, 'input');
	}

	/**
	 * Process val via map
	 *
	 * @param string $val
	 * @param array $map
	 * @return string
	 */
	public function _process($val, $map) {
		foreach ($map as $m => $arg) {
			if (is_numeric($m)) {
				$m = $arg;
				$arg = null;
			}

			if ($arg !== null) {
				$ret = call_user_func($m, $val, $arg);
			} else {
				$ret = call_user_func($m, $val);
			}

			if ($ret !== false) {
				$val = $ret;
			}
		}
		return $val;
	}

}
