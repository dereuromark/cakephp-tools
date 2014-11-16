<?php

namespace Tools\Model\Table;

use Cake\ORM\Table as CakeTable;
use Cake\Validation\Validator;
use Cake\Validation\Validation;
use Cake\Utility\Inflector;
use Cake\Core\Configure;

class Table extends CakeTable {

	/**
	 * initialize()
	 *
	 * @param mixed $config
	 * @return void
	 */
	public function initialize(array $config) {
		// Shims
		if (isset($this->primaryKey)) {
			$this->primaryKey($this->primaryKey);
		}
		if (isset($this->displayField)) {
			$this->displayField($this->displayField);
		}
		$this->_shimRelations();

		$this->addBehavior('Timestamp');
	}

	/**
	 * Shim the 2.x way of class properties for relations.
	 *
	 * @return void
	 */
	protected function _shimRelations() {
		if (!empty($this->belongsTo)) {
			foreach ($this->belongsTo as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (!empty($v['className'])) {
					$v['className'] = Inflector::pluralize($v['className']);
				}
				$v = array_filter($v);
				$this->belongsTo(Inflector::pluralize($k), $v);
			}
		}
		if (!empty($this->hasOne)) {
			foreach ($this->hasOne as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (!empty($v['className'])) {
					$v['className'] = Inflector::pluralize($v['className']);
				}
				$v = array_filter($v);
				$this->hasOne(Inflector::pluralize($k), $v);
			}
		}
		if (!empty($this->hasMany)) {
			foreach ($this->hasMany as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (!empty($v['className'])) {
					$v['className'] = Inflector::pluralize($v['className']);
				}
				$v = array_filter($v);
				$this->hasMany(Inflector::pluralize($k), $v);
			}
		}
		if (!empty($this->hasAndBelongsToMany)) {
			foreach ($this->hasAndBelongsToMany as $k => $v) {
				if (is_int($k)) {
					$k = $v;
					$v = array();
				}
				if (!empty($v['className'])) {
					$v['className'] = Inflector::pluralize($v['className']);
				}
				$v = array_filter($v);
				$this->belongsToMany(Inflector::pluralize($k), $v);
			}
		}
	}

	/**
	 * Shim the 2.x way of validate class properties.
	 *
	 * @param Validator $validator
	 * @return Validator
	 */
	public function validationDefault(Validator $validator) {
		if (!empty($this->validate)) {
			foreach ($this->validate as $field => $rules) {
				if (is_int($field)) {
					$field = $rules;
					$rules = array();
				}
				foreach ((array)$rules as $rule) {
					if (isset($rule['required'])) {
						$validator->requirePresence($field, $rule['required']);
						unset($rule['required']);
					}
					if (isset($rule['allowEmpty'])) {
						$validator->allowEmpty($field, $rule['allowEmpty']);
						unset($rule['allowEmpty']);
					}
				}
				$validator->add($field, $rules);
			}
		}

		return $validator;
	}

/**
 * Validator method used to check the uniqueness of a value for a column.
 * This is meant to be used with the validation API and not to be called
 * directly.
 *
 * ### Example:
 *
 * {{{
 * $validator->add('email', [
 *	'unique' => ['rule' => 'validateUnique', 'provider' => 'table']
 * ])
 * }}}
 *
 * Unique validation can be scoped to the value of another column:
 *
 * {{{
 * $validator->add('email', [
 *	'unique' => [
 *		'rule' => ['validateUnique', ['scope' => 'site_id']],
 *		'provider' => 'table'
 *	]
 * ]);
 * }}}
 *
 * In the above example, the email uniqueness will be scoped to only rows having
 * the same site_id. Scoping will only be used if the scoping field is present in
 * the data to be validated.
 *
 * @override To allow multiple scoped values
 *
 * @param mixed $value The value of column to be checked for uniqueness
 * @param array $options The options array, optionally containing the 'scope' key
 * @param array $context The validation context as provided by the validation routine
 * @return bool true if the value is unique
 */
	public function validateUnique($value, array $options, array $context = []) {
		if (empty($context)) {
			$context = $options;
		}

		$conditions = [$context['field'] => $value];
		if (!empty($options['scope'])) {
			foreach ((array)$options['scope'] as $scope) {
				if (!isset($context['data'][$scope])) {
					continue;
				}
				$scopedValue = $context['data'][$scope];
				$conditions[$scope] = $scopedValue;
			}
		}

		if (!$context['newRecord']) {
			$keys = (array)$this->primaryKey();
			$not = [];
			foreach ($keys as $key) {
				if (isset($context['data'][$key])) {
					$not[$key] = $context['data'][$key];
				}
			}
			$conditions['NOT'] = $not;
		}

		return !$this->exists($conditions);
	}

	/**
	 * Checks a record, if it is unique - depending on other fields in this table (transfered as array)
	 * example in model: 'rule' => array ('validateUnique', array('belongs_to_table_id','some_id','user_id')),
	 * if all keys (of the array transferred) match a record, return false, otherwise true
	 *
	 * @param array $fields Other fields to depend on
	 * TODO: add possibity of deep nested validation (User -> Comment -> CommentCategory: UNIQUE comment_id, Comment.user_id)
	 * @param array $options
	 * - requireDependentFields Require all dependent fields for the validation rule to return true
	 * @return bool Success
	 */
	public function validateUniqueExt($fieldValue, $fields = array(), $options = array()) {
		$id = (!empty($this->data[$this->alias][$this->primaryKey]) ? $this->data[$this->alias][$this->primaryKey] : 0);
		if (!$id && $this->id) {
			$id = $this->id;
		}

		$conditions = array(
			$this->alias . '.' . $fieldName => $fieldValue,
			$this->alias . '.id !=' => $id);

		$fields = (array)$fields;
		if (!array_key_exists('allowEmpty', $fields)) {
			foreach ($fields as $dependingField) {
				if (isset($this->data[$this->alias][$dependingField])) { // add ONLY if some content is transfered (check on that first!)
					$conditions[$this->alias . '.' . $dependingField] = $this->data[$this->alias][$dependingField];

				} elseif (isset($this->data['Validation'][$dependingField])) { // add ONLY if some content is transfered (check on that first!
					$conditions[$this->alias . '.' . $dependingField] = $this->data['Validation'][$dependingField];

				} elseif (!empty($id)) {
					// manual query! (only possible on edit)
					$res = $this->find('first', array('fields' => array($this->alias . '.' . $dependingField), 'conditions' => array($this->alias . '.id' => $id)));
					if (!empty($res)) {
						$conditions[$this->alias . '.' . $dependingField] = $res[$this->alias][$dependingField];
					}
				} else {
					if (!empty($options['requireDependentFields'])) {
						trigger_error('Required field ' . $dependingField . ' for validateUnique validation not present');
						return false;
					}
					return true;
				}
			}
		}

		$this->recursive = -1;
		if (count($conditions) > 2) {
			$this->recursive = 0;
		}
		$options = array('fields' => array($this->alias . '.' . $this->primaryKey), 'conditions' => $conditions);
		$res = $this->find('first', $options);
		return empty($res);
	}

	/**
	 * Shim to provide 2.x way of find('first') for easier upgrade.
	 *
	 * @param string $type
	 * @param array $options
	 * @return Query
	 */
	public function find($type = 'all', $options = []) {
		if ($type === 'first') {
			return parent::find('all', $options)->first();
		}
		return parent::find($type, $options);
	}

	/**
	 * Table::field()
	 *
	 * @param string $name
	 * @param array $options
	 * @return mixed Field value or null if not available
	 */
	public function field($name, array $options = array()) {
		$result = $this->find('all', $options)->first();
		if (!$result) {
			return null;
		}
		return $result->get($name);
	}

	/**
	 * Overwrite to allow markNew => auto
	 *
	 * @param array $data The data to build an entity with.
	 * @param array $options A list of options for the object hydration.
	 * @return \Cake\Datasource\EntityInterface
	 */
	public function newEntity(array $data = [], array $options = []) {
		if (Configure::read('Entity.autoMarkNew')) {
			$options += ['markNew' => 'auto'];
		}
		if (isset($options['markNew']) && $options['markNew'] === 'auto') {
			$this->_primaryKey = (array)$this->primaryKey();
			$this->_primaryKey = $this->_primaryKey[0];
			$options['markNew'] = !empty($data[$this->_primaryKey]);
		}
		return parent::newEntity($data, $options);
	}

	/**
	 * truncate()
	 *
	 * @return void
	 */
	public function truncate() {
		$sql = $this->schema()->truncateSql($this->_connection);
		foreach ($sql as $snippet) {
			$this->_connection->execute($snippet);
		}
	}

	/**
	 * Get all related entries that have been used so far
	 *
	 * @param string $modelName The related model
	 * @param string $groupField Field to group by
	 * @param string $type Find type
	 * @param array $options
	 * @return array
	 */
	public function getRelatedInUse($modelName, $groupField = null, $type = 'all', $options = array()) {
		if ($groupField === null) {
			$groupField = $this->belongsTo[$modelName]['foreignKey'];
		}
		$defaults = array(
			'contain' => array($modelName),
			'group' => $groupField,
			'order' => $this->$modelName->order ? $this->$modelName->order : array($modelName . '.' . $this->$modelName->displayField => 'ASC'),
		);
		if ($type === 'list') {
			$defaults['fields'] = array($modelName . '.' . $this->$modelName->primaryKey, $modelName . '.' . $this->$modelName->displayField);
		}
		$options += $defaults;
		return $this->find($type, $options);
	}

	/**
	 * Get all fields that have been used so far
	 *
	 * @param string $groupField Field to group by
	 * @param string $type Find type
	 * @param array $options
	 * @return array
	 */
	public function getFieldInUse($groupField, $type = 'all', $options = array()) {
		$defaults = array(
			'group' => $groupField,
			'order' => array($this->alias . '.' . $this->displayField => 'ASC'),
		);
		if ($type === 'list') {
			$defaults['fields'] = array($this->alias . '.' . $this->primaryKey, $this->alias . '.' . $this->displayField);
		}
		$options += $defaults;
		return $this->find($type, $options);
	}

	/**
	 * Checks if the content of 2 fields are equal
	 * Does not check on empty fields! Return TRUE even if both are empty (secure against empty in another rule)!
	 *
	 * Options:
	 * - compare: field to compare to
	 * - cast: if casting should be applied to both values
	 *
	 * @param mixed $value
	 * @param array $options
	 * @return bool Success
	 */
	public function validateIdentical($value, array $options, array $context = []) {
		if (!is_array($options)) {
			$options = array('compare' => $options);
		}
		if (!isset($context['data'][$options['compare']])) {
			return false;
		}
		$compareValue = $context['data'][$options['compare']];

		$matching = array('string' => 'string', 'int' => 'integer', 'float' => 'float', 'bool' => 'boolean');
		if (!empty($options['cast']) && array_key_exists($options['cast'], $matching)) {
			// cast values to string/int/float/bool if desired
			settype($compareValue, $matching[$options['cast']]);
			settype($value, $matching[$options['cast']]);
		}
		return ($compareValue === $value);
	}

	/**
	 * Checks if a url is valid AND accessable (returns false otherwise)
	 *
	 * @param array/string $data: full url(!) starting with http://...
	 * @options array
	 * - allowEmpty TRUE/FALSE (TRUE: if empty => return TRUE)
	 * - required TRUE/FALSE (TRUE: overrides allowEmpty)
	 * - autoComplete (default: TRUE)
	 * - deep (default: TRUE)
	 * @return bool Success
	 */
	public function validateUrl($url, $options = array()) {
		if (empty($url)) {
			if (!empty($options['allowEmpty']) && empty($options['required'])) {
				return true;
			}
			return false;
		}
		if (!isset($options['autoComplete']) || $options['autoComplete'] !== false) {
			$url = $this->_autoCompleteUrl($url);
			if (isset($key)) {
				$this->data[$this->alias][$key] = $url;
			}
		}

		if (!isset($options['strict']) || $options['strict'] !== false) {
			$options['strict'] = true;
		}

		// validation
		if (!Validation::url($url, $options['strict']) && env('REMOTE_ADDR') && env('REMOTE_ADDR') !== '127.0.0.1') {
			return false;
		}
		// same domain?
		if (!empty($options['sameDomain']) && env('HTTP_HOST')) {
			$is = parse_url($url, PHP_URL_HOST);
			$expected = env('HTTP_HOST');
			if (mb_strtolower($is) !== mb_strtolower($expected)) {
				return false;
			}
		}

		if (isset($options['deep']) && $options['deep'] === false) {
			return true;
		}
		return $this->_validUrl($url);
	}

	/**
	 * Prepend protocol if missing
	 *
	 * @param string $url
	 * @return string Url
	 */
	protected function _autoCompleteUrl($url) {
		if (mb_strpos($url, '/') === 0) {
			$url = Router::url($url, true);
		} elseif (mb_strpos($url, '://') === false && mb_strpos($url, 'www.') === 0) {
			$url = 'http://' . $url;
		}
		return $url;
	}

	/**
	 * Checks if a url is valid
	 *
	 * @param string url
	 * @return bool Success
	 */
	protected function _validUrl($url) {
		$headers = Utility::getHeaderFromUrl($url);
		if ($headers === false) {
			return false;
		}
		$headers = implode("\n", $headers);
		$protocol = mb_strpos($url, 'https://') === 0 ? 'HTTP' : 'HTTP';
		if (!preg_match('#^' . $protocol . '/.*?\s+[(200|301|302)]+\s#i', $headers)) {
			return false;
		}
		if (preg_match('#^' . $protocol . '/.*?\s+[(404|999)]+\s#i', $headers)) {
			return false;
		}
		return true;
	}

	/**
	 * Validation of DateTime Fields (both Date and Time together)
	 *
	 * @param options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min/max (defaults to >= 1 - at least 1 minute apart)
	 * @return bool Success
	 */
	public function validateDateTime($value, $options = array(), $config = array()) {
		$format = !empty($options['dateFormat']) ? $options['dateFormat'] : 'ymd';

		$pieces = $value->format(FORMAT_DB_DATETIME);
		$dateTime = explode(' ', $pieces, 2);
		$date = $dateTime[0];
		$time = (!empty($dateTime[1]) ? $dateTime[1] : '');

		if (!empty($options['allowEmpty']) && (empty($date) && empty($time) || $date == DEFAULT_DATE && $time == DEFAULT_TIME || $date == DEFAULT_DATE && empty($time))) {
			return true;
		}

		//TODO: cleanup
		if (Validation::date($date, $format) && Validation::time($time)) {
			// after/before?
			$minutes = isset($options['min']) ? $options['min'] : 1;
			if (!empty($options['after']) && isset($config['data'][$options['after']])) {
				$compare = $value->subMinutes($minutes);
				if ($config['data'][$options['after']]->gt($compare)) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($config['data'][$options['before']])) {
				$compare = $value->addMinutes($minutes);
				if ($config['data'][$options['before']]->lt($compare)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Validation of Date fields (as the core one is buggy!!!)
	 *
	 * @param options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min (defaults to 0 - equal is OK too)
	 * @return bool Success
	 */
	public function validateDate($value, $options = array()) {
		$format = !empty($options['format']) ? $options['format'] : 'ymd';

		$dateTime = explode(' ', $value, 2);
		$date = $dateTime[0];

		if (!empty($options['allowEmpty']) && (empty($date) || $date == DEFAULT_DATE)) {
			return true;
		}
		if (Validation::date($date, $format)) {
			// after/before?
			$days = !empty($options['min']) ? $options['min'] : 0;
			if (!empty($options['after']) && isset($this->data[$this->alias][$options['after']])) {
				if ($this->data[$this->alias][$options['after']] > date(FORMAT_DB_DATE, strtotime($date) - $days * DAY)) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($this->data[$this->alias][$options['before']])) {
				if ($this->data[$this->alias][$options['before']] < date(FORMAT_DB_DATE, strtotime($date) + $days * DAY)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Validation of Time fields
	 *
	 * @param array $options
	 * - timeFormat (defaults to 'hms')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min/max (defaults to >= 1 - at least 1 minute apart)
	 * @return bool Success
	 */
	public function validateTime($value, $options = array()) {
		$dateTime = explode(' ', $value, 2);
		$value = array_pop($dateTime);

		if (Validation::time($value)) {
			// after/before?
			if (!empty($options['after']) && isset($this->data[$this->alias][$options['after']])) {
				if ($this->data[$this->alias][$options['after']] >= $value) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($this->data[$this->alias][$options['before']])) {
				if ($this->data[$this->alias][$options['before']] <= $value) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Validation of Date Fields (>= minDate && <= maxDate)
	 *
	 * @param options
	 * - min/max (TODO!!)
	 */
	public function validateDateRange($value, $options = array()) {
	}

	/**
	 * Validation of Time Fields (>= minTime && <= maxTime)
	 *
	 * @param options
	 * - min/max (TODO!!)
	 */
	public function validateTimeRange($value, $options = array()) {
	}

}
