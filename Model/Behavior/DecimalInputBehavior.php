<?php
App::uses('ModelBehavior', 'Model');

/**
 * //ALREADY exists as number_format in a slightly different way!
 *
 * IN:
 * 20,01 => 20.01 (!)
 * 11.222 => 11222 (or 11#222 in strict mode to invalidate correctly)
 *
 * OUT:
 * 20.01 => 20,01
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.0
 *
 * TODO: rename to NumberFormat Behavior?
 * 2011-06-21 ms
 */
class DecimalInputBehavior extends ModelBehavior {

	protected $_defaults = array(
		'before' => 'validate', // save or validate
		'input' => true, // true = activated
		'output' => false, // true = activated
		'fields' => array(
		),
		'observedTypes' => array(
			'float'
		),
		'localeconv' => false,
		# based on input (output other direction)
		'transform' => array(
			'.' => '',
			',' => '.',
			//'multiply' => 0
		),
		'transformReverse' => array(),
		'strict' => false,
	);

	public $delimiterBaseFormat = array();
	public $delimiterFromFormat = array();


	/**
	* adjust configs like: $Model->Behaviors-attach('Tools.DecimalInput', array('fields'=>array('xyz')))
	* leave fields empty to auto-detect all float inputs
	*/
	public function setup(Model $Model, $config = array()) {
		$this->config[$Model->alias] = $this->_defaults;

		if (!empty($config['strict'])) {
			$this->config[$Model->alias]['transform']['.'] = '#';
		}
		$this->config[$Model->alias] = array_merge($this->config[$Model->alias], $config);

		$numberFields = array();
		$schema = $Model->schema();
		foreach ($schema as $key => $values) {
			if (isset($values['type']) && !in_array($key, $this->config[$Model->alias]['fields']) && in_array($values['type'], $this->config[$Model->alias]['observedTypes'])) {
				array_push($numberFields, $key);
			}
		}

		$this->config[$Model->alias]['fields'] = array_merge($this->config[$Model->alias]['fields'], $numberFields);

		/*
		if ($this->config[$Model->alias]['localeconv']) {
			# use locale settings
			$loc = localeconv();
		} else {
			# use configure settings
			$loc = (array)Configure::read('Localization');
		}
		*/
		//TODO: remove to avoid conflicts
		$this->Model = $Model;
	}

	public function beforeValidate(Model $Model) {
		if ($this->config[$Model->alias]['before'] != 'validate') {
			return true;
		}

		$this->prepInput($Model->data); //direction is from interface to database
		return true;
	}

	public function beforeSave(Model $Model) {
		if ($this->config[$Model->alias]['before'] != 'save') {
			return true;
		}

		$this->prepInput($Model->data); //direction is from interface to database
		return true;
	}

	public function afterFind(Model $Model, $results, $primary) {
		if (!$this->config[$Model->alias]['output'] || empty($results)) {
			return $results;
		}

		$results = $this->prepOutput($results); //direction is from database to interface
		return $results;
	}

	/**
	 * @param array $results (by reference)
	 * @return void
	 */
	public function prepInput(&$data) {
		foreach ($data[$this->Model->alias] as $key => $field) {
			if (in_array($key, $this->config[$this->Model->alias]['fields'])) {
				$data[$this->Model->alias][$key] = $this->formatInputOutput(null, $field, 'in');
			}
		}
	}

	/**
	 * @param array $results
	 * @return array $results
	 */
	public function prepOutput($data) {
		foreach ($data as $datakey => $record) {
			if (!isset($record[$this->Model->alias])) {
				return $data;
			}
			foreach ($record[$this->Model->alias] as $key => $field) {
				if (in_array($key, $this->config[$this->Model->alias]['fields'])) {
					$data[$datakey][$this->Model->alias][$key] = $this->formatInputOutput(null, $field, 'out');
				}
			}
		}
		return $data;
	}

	/**
	 * perform a single transformation
	 * @return string $cleanedValue
	 */
	public function formatInputOutput(Model $model = null, $value, $dir = 'in') {
		$this->_setTransformations($dir);
		if ($dir == 'out') {
			$value = str_replace($this->delimiterFromFormat, $this->delimiterBaseFormat, (String)$value);
		} else {
			$value = str_replace(' ', '', $value);
			$value = str_replace($this->delimiterFromFormat, $this->delimiterBaseFormat, $value);
			if (is_numeric($value)) {
				$value = (float)$value;
			}
		}
		return $value;
	}

	/**
	 * prep the transformation chars
	 * @return void
	 */
	protected function _setTransformations($dir) {
		$from = array();
		$base = array();
		$transform = $this->config[$this->Model->alias]['transform'];
		if (!empty($this->config[$this->Model->alias]['transformReverse'])) {
			$transform = $this->config[$this->Model->alias]['transformReverse'];
		} else {
			if ($dir == 'out') {
				$transform = array_reverse($transform, true);
			}
		}
		$first = true;
		foreach ($transform as $key => $value) {
			/*
			if ($first) {
				$from[] = $key;
				$base[] = '#';
				$key = '#';
				$first = false;
			}
			*/
			$from[] = $key;
			$base[] = $value;
		}

		if ($dir == 'out') {
			$this->delimiterFromFormat = $base;
			$this->delimiterBaseFormat = $from;
		} else {
			$this->delimiterFromFormat = $from;
			$this->delimiterBaseFormat = $base;
		}
	}

}