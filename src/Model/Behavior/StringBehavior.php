<?php

namespace Tools\Model\Behavior;

use ArrayObject;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;

/**
 * A behavior that will apply basic string operations for your input.
 *
 * Note that most string modification should be done once, on save.
 * Prevent using output modification if possible as it is done on every fetch.
 *
 * Tip: If you have other behaviors that might modify the array data prior to saving, better use a higher priority:
 *   $this->addBehavior('Tools.String', ['priority' => 11, ...]);
 * So that it is run last.
 *
 * Usage: See docs
 *
 * @author Mark Scherer
 * @license MIT
 */
class StringBehavior extends Behavior {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'fields' => [], // Fields to convert
		'clean' => false, // On beforeMarshal() to prepare for validation
		'input' => [], // Basic input filters
		'output' => [], // Basic output filters
	];

	/**
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
	}

	/**
	 * Decode the fields on after find
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \ArrayObject $options
	 * @param bool $primary
	 *
	 * @return void
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void {
		if (!$this->_config['output']) {
			//return;
		}

		$query->formatResults(function (CollectionInterface $results) {
			return $results->map(function ($row) {
				$this->processItems($row, 'output');

				return $row;
			});
		});
	}

	/**
	 * Decodes the fields of an array/entity (if the value itself was encoded)
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $type Type (input/output)
	 * @return void
	 */
	protected function processItems(EntityInterface $entity, string $type = 'input'): void {
		$fields = $this->fieldsFromMap($type);
		$customMap = true;
		if (!$fields) {
			$fields = $this->_config['fields'];
			$customMap = false;
		}

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
				$map = $customMap ? $this->_config[$type][$field] : $this->_config[$type];
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
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		$this->processItems($entity, 'input');
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void {
		if (!$this->getConfig('clean')) {
			return;
		}

		$fields = $this->_config['fields'];
		foreach ($fields as $field) {
			if (!isset($data[$field]) || !is_string($data[$field])) {
				continue;
			}

			$data[$field] = $this->clean($data[$field]);
		}
	}

	/**
	 * @param string $text
	 * @return string
	 */
	protected function clean(string $text): string {
		$text = (string)str_replace(["\r\n", "\r", "\n"], ' ', $text);
		$text = trim($text);
		$text = (string)preg_replace('/ {2,}/', ' ', $text);

		return $text;
	}

	/**
	 * Process val via map
	 *
	 * @param string $val
	 * @param array $map
	 * @return string
	 */
	protected function _process($val, array $map) {
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

	/**
	 * @param string $type
	 * @return array<string>
	 */
	protected function fieldsFromMap(string $type): array {
		$fields = [];
		foreach ($this->_config[$type] as $field => $map) {
			if (is_string($field) && is_array($map)) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

}
