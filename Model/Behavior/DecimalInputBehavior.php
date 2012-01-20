<?php
/**
 * //ALREADY exists as number_format in a slightly different way!
 *
 * 20,01 => 20.01 (!)
 * 11.222 => 11222
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.0
 * 
 * TODO: rename to NumberFormat Behavior?
 * 2011-06-21 ms
 */
class DecimalInputBehavior extends ModelBehavior {

	public $default = array(
		'before' => 'validate', // safe or validate
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
	);

	public $delimiterBaseFormat = array();
	public $delimiterFromFormat = array();


	/**
	* adjust configs like: $Model->Behaviors-attach('DecimalInput', array('fields'=>array('xyz')))
	*/
	public function setup(Model $Model, $config = array()) {
		$this->config[$Model->alias] = $this->default;
		$this->config[$Model->alias] = array_merge($this->config[$Model->alias], $config);

		$numberFields = array();
		if (!empty($Model->_schema)) {
			foreach ($Model->_schema as $key => $values) {
				if (isset($values['type']) && !in_array($key, $this->config[$Model->alias]['fields']) && in_array($values['type'], $this->config[$Model->alias]['observedTypes'])) {
					array_push($numberFields, $key);
				}
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
		$this->Model = $Model;
	}


	//Function before save.
	public function beforeValidate(Model $Model) {
		if ($this->config[$Model->alias]['before'] != 'validate') {
			return true;
		}

		$this->prepInput($Model->data); //direction is from interface to database
		return true;
	}


	//Function before save.
	public function beforeSave(Model $Model) {
		if ($this->config[$Model->alias]['before'] != 'save') {
			return true;
		}

		$this->prepInput($Model->data); //direction is from interface to database
		return true;
	}


	public function afterFind(Model $Model, $results) {
		if (!$this->config[$Model->alias]['output'] || empty($results)) {
			return $results;
		}

		$results = $this->prepOutput($results); //direction is from database to interface
		return $results;
	}


	public function prepInput(&$data) {
		foreach ($data[$this->Model->alias] as $key => $field) {
			if (in_array($key, $this->config[$this->Model->alias]['fields'])) {
				$data[$this->Model->alias][$key] = $this->_format($field, 'in');
			}
		}
	}

	public function prepOutput($data) {
		foreach ($data as $datakey => $record) {
			if (!isset($record[$this->Model->alias])) {
				return $data;
			}
			foreach ($record[$this->Model->alias] as $key => $field) {
				if (in_array($key, $this->config[$this->Model->alias]['fields'])) {
					$data[$datakey][$this->Model->alias][$key] = $this->_format($field, 'out');
				}
			}
		}
		return $data;
	}


	protected function _format($value, $dir = 'in') {
		$this->_setTransformations($dir);
		if ($dir == 'out') {
			$value = str_replace($this->delimiterFromFormat, $this->delimiterBaseFormat, (String)$value);
		} else {
			$value = str_replace(' ', '', $value);
			$value = (float)str_replace($this->delimiterFromFormat, $this->delimiterBaseFormat, $value);
		}
		return $value;
	}


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
		foreach ($transform as $key => $value) {
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


/*
beforeValidate
$Model->data[$Model->alias][$field] = str_replace($loc['decimal_point'], "#", $Model->data[$Model->alias][$field]);
$Model->data[$Model->alias][$field] = str_replace($loc['thousands_sep'], "", $Model->data[$Model->alias][$field]);
$Model->data[$Model->alias][$field] = str_replace("#", ".", $Model->data[$Model->alias][$field]);

afterFind
$m[$Model->alias][$field] = str_replace('.', '#', $m[$Model->alias][$field]);
$m[$Model->alias][$field] = str_replace(',', $loc['thousands_sep'], $m[$Model->alias][$field]);
$m[$Model->alias][$field] = str_replace('#', $loc['decimal_point'], $m[$Model->alias][$field]);
*/


}

