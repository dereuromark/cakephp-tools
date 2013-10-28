<?php
App::uses('ModelBehavior', 'Model');

/**
 * Allows the model to reset all records as batch command.
 * This way any slugging, geocoding or other beforeValidate, beforeSave, ... callbacks
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
 *    $this->Model->Behaviors->load('Tools.Reset', array(...));
 *    $this->Model->resetRecords();
 *
 * If you want to provide a callback function/method, you can either use object methods or
 * static functions/methods:
 *
 *    'callback' => array($this, 'methodName')
 *
 * and
 *
 *    public function methodName($data, &$fields) {}
 *
 * For tables with lots of records you might want to use a shell and the CLI to invoke the reset/update process.
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.x
 * @version 1.0
 */
class ResetBehavior extends ModelBehavior {

	protected $_defaults = array(
		'limit' => 100, // batch of records per loop
		'timeout' => null, // in seconds
		'fields' => array(), // if not displayField
		'updateFields' => array(), // if saved fields should be different from fields
		'validate' => true, // trigger beforeValidate callback
		'updateTimestamp' => false, // update modified/updated timestamp
		'scope' => array(), // optional conditions
		'callback' => null,
	);

	/**
	 * Configure the behavior through the Model::actsAs property
	 *
	 * @param object $Model
	 * @param array $config
	 */
	public function setup(Model $Model, $config = null) {
		if (is_array($config)) {
			$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
		} else {
			$this->settings[$Model->alias] = $this->_defaults;
		}
	}

	/**
	 * Regenerate all records (including possible beforeValidate/beforeSave callbacks).
	 *
	 * @param Model $Model
	 * @param array $conditions
	 * @param integer $recursive
	 * @return integer Modified records
	 */
	public function resetRecords(Model $Model, $params = array()) {
		$recursive = -1;
		extract($this->settings[$Model->alias]);

		$defaults = array(
			'page' => 1,
			'limit' => $limit,
			'fields' => array(),
			'order' => $Model->alias . '.' . $Model->primaryKey . ' ASC',
			'conditions' => $scope,
			'recursive' => $recursive,
		);
		if (!empty($fields)) {
			if (!$Model->hasField($fields)) {
				throw new CakeException('Model does not have fields ' . print_r($fields, true));
			}
			$defaults['fields'] = array_merge(array($Model->primaryKey), $fields);
		} else {
			$defaults['fields'] = array($Model->primaryKey);
			if ($Model->displayField !== $Model->primaryKey) {
				$defaults['fields'][] = $Model->displayField;
			}
		}
		if (!$updateTimestamp) {
			$fields = array('modified', 'updated');
			foreach ($fields as $field) {
				if ($Model->schema($field)) {
					$defaults['fields'][] = $field;
					break;
				}
			}
		}

		$params = array_merge($defaults, $params);
		$count = $Model->find('count', compact('conditions'));
		$max = ini_get('max_execution_time');
		if ($max) {
			set_time_limit(max($max, $count / $limit));
		}

		$modifed = 0;
		while ($rows = $Model->find('all', $params)) {
			foreach ($rows as $row) {
				$Model->create();
				$fields = $params['fields'];
				if (!empty($updateFields)) {
					$fields = $updateFields;
				}
				if ($fields && !in_array($Model->primaryKey, $fields)) {
					$fields[] = $Model->primaryKey;
				}

				if ($callback) {
					if (is_callable($callback)) {
						$parameters = array(&$row, &$fields);
						$row = call_user_func_array($callback, $parameters);
					} else {
						$row = $Model->{$callback}($row, $fields);
					}
					if (!$row) {
						continue;
					}
				}

				$res = $Model->save($row, $validate, $fields);
				if (!$res) {
					throw new CakeException(print_r($Model->validationErrors, true));
				}
				$modifed++;
			}
			$params['page']++;
			if ($timeout) {
				sleep((int)$timeout);
			}
		}
		return $modifed;
	}

}
