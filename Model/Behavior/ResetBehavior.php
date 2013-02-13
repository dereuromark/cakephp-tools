<?php
App::uses('ModelBehavior', 'Model');

/**
 * Allows the model to reset all records as batch command.
 * This way any slugging, geocoding or other beforeValidate, beforeSave, ... callbacks
 * can be retriggered for them.
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.0
 * @version 1
 * 2011-12-06 ms
 */
class ResetBehavior extends ModelBehavior {

	protected $_defaults = array(
		'limit' => 100,
		'auto' => false,
		'fields' => array(),
		'model' => null,
		'notices' => true,
		'validate' => true,
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
	 * resetRecords method
	 *
	 * Regenerate all records (run beforeValidate/beforeSave callbacks).
	 *
	 * @param Model $Model
	 * @param array $conditions
	 * @param int $recursive
	 * @return bool true on success false otherwise
	 */
	public function resetRecords(Model $Model, $params = array()) {
		$recursive = -1;
		extract($this->settings[$Model->alias]);
		/*
		if ($notices && !$Model->hasField($fields)) {
			return false;
		}
		*/
		$defaults = array(
			'page' => 1,
			'limit' => $limit,
			'fields' => array(),
			'order' => $Model->alias.'.'.$Model->displayField . ' ASC',
			'conditions' => array(),
			'recursive' => $recursive,
		);
		if (!empty($fields)) {
			$defaults['fields'] = array_merge(array($Model->primaryKey), $fields);
		} else {
			$defaults['fields'] = array($Model->primaryKey, $Model->displayField);
		}

		$params = array_merge($defaults, $params);
		$count = $Model->find('count', compact('conditions'));
		$max = ini_get('max_execution_time');
		if ($max) {
			set_time_limit (max($max, $count / $limit));
		}
		while ($rows = $Model->find('all', $params)) {
			foreach ($rows as $row) {
				$Model->create();
				$res = $Model->save($row, $validate, $params['fields']);
				if (!$res) {
					throw new CakeException(print_r($Model->validationErrors, true));
				}
			}
			$params['page']++;
		}
		return true;
	}

}
