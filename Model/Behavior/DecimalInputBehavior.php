<?php
App::uses('ModelBehavior', 'Model');

/**
 * Format numeric values according to locale settings of either the system or the app.
 * You can use setlocale(LC_NUMERIC, [your-locale]); or Configure::write('Localization') to set global settings.
 * Or you can pass the localization pattern as `transform` key to the behavior directly.
 *
 * You can use strict mode to reduce errors made by converting too much automatically.
 *
 * Use `observedTypes` to define what type of db field you want to automatically track/modify.
 * You can always manually add more fields using `fields`.
 * If you want to adjust weather you want convertion for output, as well, set `output` to true.
 *
 * `before` can be 'validate' or 'safe', defaults to 'validate'.
 *
 * If you store percentages for example, you might want to allow the user to add integer percentage values (0 ... 100)
 * and convert them using `multiply`  and '0.01' as value. It will assume that this is the input rate. For output it will automatically
 * be inversed.
 *
 * Example for GERMAN:
 * IN:
 * 20,01 => 20.01 (!)
 * 11.222 => 11222 (or 11#222 in strict mode to invalidate correctly)
 * OUT:
 * 20.01 => 20,01
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.x
 * @deprecated Use NumberFormatBehavior instead!
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
		// based on input (output other direction)
		'transform' => array(
			'.' => '',
			',' => '.',
		),
		'multiply' => 0, // direction => in (revert is out)
		'transformReverse' => array(),
		'strict' => false,
	);

	public $delimiterBaseFormat = array();

	public $delimiterFromFormat = array();

	/**
	 * Adjust configs like: $Model->Behaviors-attach('Tools.DecimalInput', array('fields'=>array('xyz')))
	 * leave fields empty to auto-detect all float inputs
	 */
	public function setup(Model $Model, $config = array()) {
		$this->config[$Model->alias] = $this->_defaults;

		if (!empty($config['strict'])) {
			$this->config[$Model->alias]['transform']['.'] = '#';
		}
		if ($this->config[$Model->alias]['localeconv'] || !empty($config['localeconv'])) {
			// use locale settings
			$conv = localeconv();
			$loc = array(
				'decimals' => $conv['decimal_point'],
				'thousands' => $conv['thousands_sep']
			);
		} elseif ($configure = Configure::read('Localization')) {
			// use configure settings
			$loc = (array)$configure;
		}
		if (!empty($loc)) {
			$this->config[$Model->alias]['transform'] = array(
				$loc['thousands'] => $this->config[$Model->alias]['transform']['.'],
				$loc['decimals'] => $this->config[$Model->alias]['transform'][','],
			);
		}
		//debug($this->config[$Model->alias]);

		$this->config[$Model->alias] = array_merge($this->config[$Model->alias], $config);

		$numberFields = array();
		$schema = $Model->schema();
		foreach ($schema as $key => $values) {
			if (isset($values['type']) && !in_array($key, $this->config[$Model->alias]['fields']) && in_array($values['type'], $this->config[$Model->alias]['observedTypes'])) {
				array_push($numberFields, $key);
			}
		}
		$this->config[$Model->alias]['fields'] = array_merge($this->config[$Model->alias]['fields'], $numberFields);
	}

	public function beforeValidate(Model $Model, $options = array()) {
		if ($this->config[$Model->alias]['before'] !== 'validate') {
			return true;
		}

		$this->prepInput($Model, $Model->data); //direction is from interface to database
		return true;
	}

	public function beforeSave(Model $Model, $options = array()) {
		if ($this->config[$Model->alias]['before'] !== 'save') {
			return true;
		}

		$this->prepInput($Model, $Model->data); //direction is from interface to database
		return true;
	}

	public function afterFind(Model $Model, $results, $primary = false) {
		if (!$this->config[$Model->alias]['output'] || empty($results)) {
			return $results;
		}

		$results = $this->prepOutput($Model, $results); //direction is from database to interface
		return $results;
	}

	/**
	 * @param array $results (by reference)
	 * @return void
	 */
	public function prepInput(Model $Model, &$data) {
		foreach ($data[$Model->alias] as $key => $field) {
			if (in_array($key, $this->config[$Model->alias]['fields'])) {
				$data[$Model->alias][$key] = $this->formatInputOutput($Model, $field, 'in');
			}
		}
	}

	/**
	 * @param array $results
	 * @return array results
	 */
	public function prepOutput(Model $Model, $data) {
		foreach ($data as $datakey => $record) {
			if (!isset($record[$Model->alias])) {
				return $data;
			}
			foreach ($record[$Model->alias] as $key => $value) {
				if (in_array($key, $this->config[$Model->alias]['fields'])) {
					$data[$datakey][$Model->alias][$key] = $this->formatInputOutput($Model, $value, 'out');
				}
			}
		}
		return $data;
	}

	/**
	 * Perform a single transformation
	 *
	 * @return string cleanedValue
	 */
	public function formatInputOutput(Model $Model, $value, $dir = 'in') {
		$this->_setTransformations($Model, $dir);
		if ($dir === 'out') {
			if ($this->config[$Model->alias]['multiply']) {
				$value *= (float)(1 / $this->config[$Model->alias]['multiply']);
			}

			$value = str_replace($this->delimiterFromFormat, $this->delimiterBaseFormat, (string)$value);
		} else {
			$value = str_replace(' ', '', $value);
			$value = str_replace($this->delimiterFromFormat, $this->delimiterBaseFormat, $value);
			if (is_numeric($value)) {
				$value = (float)$value;

				if ($this->config[$Model->alias]['multiply']) {
					$value *= $this->config[$Model->alias]['multiply'];
				}
			}
		}
		return $value;
	}

	/**
	 * Prep the transformation chars
	 *
	 * @return void
	 */
	protected function _setTransformations(Model $Model, $dir) {
		$from = array();
		$base = array();
		$transform = $this->config[$Model->alias]['transform'];
		if (!empty($this->config[$Model->alias]['transformReverse'])) {
			$transform = $this->config[$Model->alias]['transformReverse'];
		} else {
			if ($dir === 'out') {
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

		if ($dir === 'out') {
			$this->delimiterFromFormat = $base;
			$this->delimiterBaseFormat = $from;
		} else {
			$this->delimiterFromFormat = $from;
			$this->delimiterBaseFormat = $base;
		}
	}

}
