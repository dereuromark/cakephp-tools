<?php
App::uses('ModelBehavior', 'Model');

/**
 * KeyValue Behavior
 *
 * TODO: long text? separate table or not at all?
 * TODO: caching
 *
 * @license MIT
 * @modified Mark Scherer
 */
class KeyValueBehavior extends ModelBehavior {

	/**
	 * Storage model for all key value pairs
	 */
	public $KeyValue = null;

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $_defaults = array(
		'foreignKeyField' => 'foreign_id',
		'keyField' => 'key',
		'valueField' => 'value',
		'defaults' => null, // looks for `public $keyValueDefaults` property in the model,
		'validate' => null, // looks for `public $keyValueValidate` property in the model
		'defaultOnEmpty' => false, // if nothing is posted, delete (0 is not nothing)
		'deleteIfDefault' => false, // if default value is posted, delete
	);

	/**
	 * Setup
	 *
	 * @param object AppModel
	 * @param array $config
	 */
	public function setup(Model $Model, $config = array()) {
		$settings = array_merge($this->_defaults, $config);
		$this->settings[$Model->alias] = $settings;
		if (!$this->KeyValue) {
			$this->KeyValue = ClassRegistry::init('Tools.KeyValue');
		}
		/*
		if ($this->settings[$Model->alias]['validate']) {
			foreach ($this->settings[$Model->alias]['validate'] as $key => $validate) {
				$this->KeyValue->validate[$key] = $validate;
			}
		}
		*/
	}

	/**
	 * Returns details for named section
	 *
	 * @var string
	 * @var string
	 * @return mixed Flat array or direct value
	 */
	public function getSection(Model $Model, $foreignKey, $section = null, $key = null) {
		extract($this->settings[$Model->alias]);
		$results = $this->KeyValue->find('all', array(
			'recursive' => -1,
			'conditions' => array($foreignKeyField => $foreignKey),
			'fields' => array('key', 'value')
		));

		$defaultValues = $this->defaultValues($Model);

		$detailArray = array();
		foreach ($results as $value) {
			$keyArray = preg_split('/\./', $value[$this->KeyValue->alias]['key'], 2);
			$detailArray[$keyArray[0]][$keyArray[1]] = $value[$this->KeyValue->alias]['value'];
		}

		foreach ($defaultValues as $model => $values) {
			foreach ($values as $valueKey => $val) {
				if (isset($detailArray[$model][$valueKey])) {
					continue;
				}
				$detailArray[$model][$valueKey] = $val;
			}
		}

		if ($section === null) {
			return $detailArray;
		}
		if (empty($detailArray[$section])) {
			return array();
		}
		if ($key === null) {
			return $detailArray[$section];
		}
		if (!isset($detailArray[$section][$key])) {
			return null;
		}
		return $detailArray[$section][$key];
	}

	/**
	 * Save details for named section
	 *
	 * TODO: validate
	 *
	 * @var string
	 * @var array
	 * @var string
	 * @return boolean Success
	 */
	public function saveSection(Model $Model, $foreignKey, $data, $section = null, $validate = true) {
		if ($validate && !$this->validateSection($Model, $data)) {
			return false;
		}

		extract($this->settings[$Model->alias]);
		foreach ($data as $model => $details) {
			if ($section && $section !== $model) {
				continue;
			}

			foreach ($details as $field => $value) {
				$newDetail = array();
				$section = $section ? $section : $model;
				$key = $section . '.' . $field;

				if ($defaultOnEmpty && (string)$value === '' || $deleteIfDefault && (string)$value === (string)$this->defaultValues($Model, $section, $field)) {
					return $this->resetSection($Model, $foreignKey, $section, $field);
				}

				$tmp = $this->KeyValue->find('first', array(
					'recursive' => -1,
					'conditions' => array($foreignKeyField => $foreignKey, $keyField => $key),
					'fields' => array('id')));

				if ($tmp) {
					$newDetail[$this->KeyValue->alias]['id'] = $tmp[$this->KeyValue->alias]['id'];
				} else {
					$this->KeyValue->create();
				}

				$newDetail[$this->KeyValue->alias][$foreignKeyField] = $foreignKey;
				$newDetail[$this->KeyValue->alias][$keyField] = $key;
				$newDetail[$this->KeyValue->alias][$valueField] = $value;
				$newDetail[$this->KeyValue->alias]['model'] = $Model->alias;
				$this->KeyValue->save($newDetail, false);
			}
		}
		return true;
	}

	/**
	 * @return boolean Success
	 */
	public function validateSection(Model $Model, $data, $section = null) {
		$validate = $this->settings[$Model->alias]['validate'];
		if ($validate === null) {
			$validate = 'keyValueValidate';
		}
		if (empty($Model->{$validate})) {
			return true;
		}
		$rules = $Model->{$validate};
		$res = true;
		foreach ($data as $model => $array) {
			if ($section && $section !== $model) {
				continue;
			}
			if (empty($rules[$model])) {
				continue;
			}
			$this->KeyValue->{$model} = ClassRegistry::init(array('class' => 'AppModel', 'alias' => $model, 'table' => false));
			$this->KeyValue->{$model}->validate = $rules[$model];
			$this->KeyValue->{$model}->set($array);
			$res = $res && $this->KeyValue->{$model}->validates();
		}
		return $res;
	}

	/**
	 * KeyValueBehavior::defaultValues()
	 *
	 * @param Model $Model
	 * @param mixed $section
	 * @param mixed $key
	 * @return array
	 */
	public function defaultValues(Model $Model, $section = null, $key = null) {
		$defaults = $this->settings[$Model->alias]['defaults'];
		if ($defaults === null) {
			$defaults = 'keyValueDefaults';
		}
		$defaultValues = array();
		if (!empty($Model->{$defaults})) {
			$defaultValues = $Model->{$defaults};
		}
		if ($section !== null) {
			if ($key !== null) {
				return isset($defaultValues[$section][$key]) ? $defaultValues[$section][$key] : null;
			}
			return isset($defaultValues[$section]) ? $defaultValues[$section] : null;
		}
		return $defaultValues;
	}

	/**
	 * Resets the custom data for the specific domains (model, foreign_id)
	 * careful: passing both null values will result in a complete truncate command
	 *
	 * @return boolean Success
	 */
	public function resetSection(Model $Model, $foreignKey = null, $section = null, $key = null) {
		extract($this->settings[$Model->alias]);
		$conditions = array();
		if ($foreignKey !== null) {
			$conditions[$foreignKeyField] = $foreignKey;
		}
		if ($section !== null) {
			if ($key !== null) {
				$conditions[$keyField] = $section . '.' . $key;
			} else {
				$conditions[$keyField . ' LIKE'] = $section . '.%';
			}
		}
		if (empty($conditions)) {
			return $this->KeyValue->truncate();
		}
		return (bool)$this->KeyValue->deleteAll($conditions, false);
	}

}
