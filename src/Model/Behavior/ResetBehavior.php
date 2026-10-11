<?php

namespace Tools\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use RuntimeException;

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
 *    $table->getBehavior('Reset')->resetRecords();
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
 */
class ResetBehavior extends Behavior {

	/**
	 * @var array<string, mixed>
	 */
	protected array $defaultConfig = [
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
		$defaults = $this->defaultConfig;
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
	 * @throws \RuntimeException
	 * @return int Modified records
	 */
	public function resetRecords(array $params = []) {
		/** @var string $primaryKey */
		$primaryKey = $this->table->getPrimaryKey();
		$defaults = [
			'page' => 1,
			'limit' => $this->config['limit'],
			'fields' => [],
			'order' => [$this->table->getAlias() . '.' . $primaryKey => 'ASC'],
			'conditions' => $this->config['scope'],
			'validate' => $this->config['validate'],
		];
		if (!empty($this->config['fields'])) {
			foreach ((array)$this->config['fields'] as $field) {
				if (!$this->table->hasField($field)) {
					throw new RuntimeException('Table does not have field ' . $field);
				}
			}
			$defaults['fields'] = array_merge([$this->table->getAlias() . '.' . $primaryKey], $this->config['fields']);
		} else {
			$defaults['fields'] = [$this->table->getAlias() . '.' . $primaryKey];
			/** @var string $displayField */
			$displayField = $this->table->getDisplayField();
			if ($displayField !== $primaryKey) {
				$defaults['fields'][] = $this->table->getAlias() . '.' . $displayField;
			}
		}

		$updateFields = [];
		if (!$this->config['updateTimestamp']) {
			$fields = ['modified', 'updated'];
			foreach ($fields as $field) {
				if ($this->table->getSchema()->getColumn($field)) {
					$defaults['fields'][] = $field;
					$updateFields[] = $field;

					break;
				}
			}
		}

		$params += $defaults;

		$conditions = $params['conditions'];
		$count = $this->table->find()->where($conditions)->count();
		$max = (int)ini_get('max_execution_time');
		if ($max) {
			set_time_limit(max($max, $count));
		}

		$modified = 0;
		while (($records = $this->table->find('all', ...$params)->all()->toArray())) {
			/** @var \Cake\Datasource\EntityInterface $record */
			foreach ($records as $record) {
				$fields = (array)$params['fields'];
				if ($this->getConfig('updateFields')) {
					$fields = (array)$this->getConfig('updateFields');
					if (!$this->config['updateTimestamp']) {
						$fields = array_merge($updateFields, $fields);
					}
				}
				if ($fields && !in_array($this->table->getPrimaryKey(), $fields)) {
					$fields[] = $this->table->getPrimaryKey();
				}

				if ($this->config['callback']) {
					if (is_callable($this->config['callback'])) {
						$parameters = [$record, &$fields];
						/** @var \Cake\Datasource\EntityInterface $record */
						$record = call_user_func_array($this->config['callback'], $parameters);
					} else {
						/** @var \Cake\Datasource\EntityInterface $record */
						$record = $this->table->{$this->config['callback']}($record, $fields);
					}
					if (!$record) {
						continue;
					}
				}

				$validate = $params['validate'];
				$res = $this->table->save($record, ['validate' => $validate, 'fields' => $fields]);
				if (!$res) {
					throw new RuntimeException(print_r($record->getErrors(), true));
				}
				$modified++;
			}
			$params['page']++;
			if ($this->config['timeout']) {
				sleep((int)$this->config['timeout']);
			}
		}

		return $modified;
	}

}
