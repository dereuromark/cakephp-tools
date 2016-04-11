<?php

namespace Tools\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Exception;

/**
 * Allows the model to reset all records as batch command.
 * This way any slugging, geocoding or other beforeRules, beforeSave, ... callbacks
 * can be retriggered for them.
 *
 * By default it will not update the modified timestamp and will re-save id and displayName.
 * If you need more fields, you need to specify them manually.
 *
 * You can also disable validate callback or provide a conditions scope to match only a subset
 * of records.
 *
 * For performance and memory reasons the records will only be processed in loops (not all at once).
 * If you have time-sensitive data, you can modify the limit of records per loop as well as the
 * timeout in between each loop.
 * Remember to raise set_time_limit() if you do not run this via CLI.
 *
 * It is recommended to attach this behavior dynamically where needed:
 *
 *    $table->addBehavior('Tools.Reset', array(...));
 *    $table->resetRecords();
 *
 * If you want to provide a callback function/method, you can either use object methods or
 * static functions/methods:
 *
 *    'callback' => array($this, 'methodName')
 *
 * and
 *
 *    public function methodName(Entity $entity, &$fields) {}
 *
 * For tables with lots of records you might want to use a shell and the CLI to invoke the reset/update process.
 *
 * @author Mark Scherer
 * @license MIT
 * @version 1.0
 */
class ResetBehavior extends Behavior {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'limit' => 100, // batch of records per loop
		'timeout' => null, // in seconds
		'fields' => [], // if not displayField
		'updateFields' => [], // if saved fields should be different from fields
		'validate' => true, // trigger beforeRules callback
		'updateTimestamp' => false, // update modified/updated timestamp
		'scope' => [], // optional conditions
		'callback' => null,
	];

	/**
	 * Adding validation rules
	 * also adds and merges config settings (direct + configure)
	 *
	 * @param \Cake\ORM\Table $table
	 * @param array $config
	 */
	public function __construct(Table $table, array $config = []) {
		$defaults = $this->_defaultConfig;
		$configureDefaults = Configure::read('Reset');
		if ($configureDefaults) {
			$defaults = $configureDefaults + $defaults;
		}
		$config += $defaults;
		parent::__construct($table, $config);
	}

	/**
	 * Regenerate all records (including possible beforeRules/beforeSave callbacks).
	 *
	 * @param array $params
	 * @return int Modified records
	 */
	public function resetRecords($params = []) {
		$defaults = [
			'page' => 1,
			'limit' => $this->_config['limit'],
			'fields' => [],
			'order' => $this->_table->alias() . '.' . $this->_table->primaryKey() . ' ASC',
			'conditions' => $this->_config['scope'],
		];
		if (!empty($this->_config['fields'])) {
			foreach ((array)$this->_config['fields'] as $field) {
				if (!$this->_table->hasField($field)) {
					throw new Exception('Table does not have field ' . $field);
				}
			}
			$defaults['fields'] = array_merge([$this->_table->alias() . '.' . $this->_table->primaryKey()], $this->_config['fields']);
		} else {
			$defaults['fields'] = [$this->_table->alias() . '.' . $this->_table->primaryKey()];
			if ($this->_table->displayField() !== $this->_table->primaryKey()) {
				$defaults['fields'][] = $this->_table->alias() . '.' . $this->_table->displayField();
			}
		}
		if (!$this->_config['updateTimestamp']) {
			$fields = ['modified', 'updated'];
			foreach ($fields as $field) {
				if ($this->_table->schema()->column($field)) {
					$defaults['fields'][] = $field;
					break;
				}
			}
		}

		$params += $defaults;
		$count = $this->_table->find('count', compact('conditions'));
		$max = ini_get('max_execution_time');
		if ($max) {
			set_time_limit(max($max, $count / $this->_config['limit']));
		}

		$modified = 0;
		while (($records = $this->_table->find('all', $params)->toArray())) {
			foreach ($records as $record) {
				$fieldList = $params['fields'];
				if (!empty($updateFields)) {
					$fieldList = $updateFields;
				}
				if ($fieldList && !in_array($this->_table->primaryKey(), $fieldList)) {
					$fieldList[] = $this->_table->primaryKey();
				}

				if ($this->_config['callback']) {
					if (is_callable($this->_config['callback'])) {
						$parameters = [$record, &$fieldList];
						$record = call_user_func_array($this->_config['callback'], $parameters);
					} else {
						$record = $this->_table->{$this->_config['callback']}($record, $fieldList);
					}
					if (!$record) {
						continue;
					}
				}

				$res = $this->_table->save($record, compact('validate', 'fieldList'));
				if (!$res) {
					throw new Exception(print_r($this->_table->errors(), true));
				}
				$modified++;
			}
			$params['page']++;
			if ($this->_config['timeout']) {
				sleep((int)$this->_config['timeout']);
			}
		}
		return $modified;
	}

}
