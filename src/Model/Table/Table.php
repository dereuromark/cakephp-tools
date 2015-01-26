<?php

namespace Tools\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\Table as CakeTable;
use Cake\Utility\Inflector;
use Cake\Validation\Validation;
use Cake\Validation\Validator;
use Tools\Utility\Utility;
use Cake\ORM\Query;
use Cake\Event\Event;
use Tools\Utility\Time;

class Table extends CakeTable {

	public $order = null;

	/**
	 * initialize()
	 *
	 * All models will automatically get Timestamp behavior attached
	 * if created or modified exists.
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

		$this->prefixOrderProperty();

		if (isset($this->actsAs)) {
			foreach ($this->actsAs as $name => $options) {
				if (is_numeric($name)) {
					$name = $options;
					$options = [];
				}
				$this->addBehavior($name, $options);
			}
		}

		if ($this->hasField('created') || $this->hasField('modified')) {
			$this->addBehavior('Timestamp');
		}
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
					$v = [];
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
					$v = [];
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
					$v = [];
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
					$v = [];
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
					$rules = [];
				}
				foreach ((array)$rules as $key => $rule) {
					if (isset($rule['required'])) {
						$validator->requirePresence($field, $rule['required']);
						unset($rule['required']);
					}
					if (isset($rule['allowEmpty'])) {
						$validator->allowEmpty($field, $rule['allowEmpty']);
						unset($rule['allowEmpty']);
					}
					if (isset($rule['message'])) {
						$rules[$key]['message'] = __($rule['message']);
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
	public function validateUniqueExt($value, array $options, array $context = []) {
		$context += $options;
		return parent::validateUnique($value, $context);
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
	 * @deprecated NOT WORKING anymore, use core validateUnique
	 */
	public function validateUniqueOld($fieldValue, $fields = [], $options = []) {
		$id = (!empty($this->data[$this->primaryKey]) ? $this->data[$this->primaryKey] : 0);
		if (!$id && $this->id) {
			$id = $this->id;
		}

		$conditions = [
			$this->alias . '.' . $fieldName => $fieldValue,
			$this->alias . '.id !=' => $id];

		$fields = (array)$fields;
		if (!array_key_exists('allowEmpty', $fields)) {
			foreach ($fields as $dependingField) {
				if (isset($this->data[$dependingField])) { // add ONLY if some content is transfered (check on that first!)
					$conditions[$this->alias . '.' . $dependingField] = $this->data[$dependingField];
				} elseif (isset($this->data['Validation'][$dependingField])) { // add ONLY if some content is transfered (check on that first!
					$conditions[$this->alias . '.' . $dependingField] = $this->data['Validation'][$dependingField];
				} elseif (!empty($id)) {
					// manual query! (only possible on edit)
					$res = $this->find('first', ['fields' => [$this->alias . '.' . $dependingField], 'conditions' => [$this->alias . '.id' => $id]]);
					if (!empty($res)) {
						$conditions[$this->alias . '.' . $dependingField] = $res[$dependingField];
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
		$options = ['fields' => [$this->alias . '.' . $this->primaryKey], 'conditions' => $conditions];
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
		if ($type === 'count') {
			return parent::find('all', $options)->count();
		}
		return parent::find($type, $options);
	}

	/**
	 * Convenience wrapper inspired by 2.x field() method. Only difference: full $options array
	 * instead of just $conditions array.
	 *
	 * @param string $name
	 * @param array $options
	 * @return mixed Field value or null if not available
	 */
	public function field($name, array $options = []) {
		$result = $this->find('all', $options)->first();
		if (!$result) {
			return null;
		}
		return $result->get($name);
	}

	/**
	 * Shim of 2.x field() method.
	 *
	 * @param string $name
	 * @param array $conditions
	 * @return mixed Field value or null if not available
	 * @deprecated Port to field() with full $options array
	 */
	public function fieldByConditions($name, array $conditions = []) {
		return $this->field($name, ['conditions' => $conditions]);
	}

	/**
	 * Sets the default ordering as 2.x shim.
	 *
	 * If you don't want that, don't call parent when overwriting it in extending classes.
	 *
	 * @param Event $event
	 * @param Query $query
	 * @param array $options
	 * @param boolean $primary
	 * @return Query
	 */
	public function beforeFind(Event $event, Query $query, $options, $primary) {
		$order = $query->clause('order');
		if (($order === null || !count($order)) && !empty($this->order)) {
			$query->order($this->order);
		}

		return $query;
	}

	/**
	 * Prefixes the order property with the actual alias if its a string or array.
	 *
	 * The core fails on using the proper prefix when building the query with two
	 * different tables.
	 *
	 * @return void
	 */
	public function prefixOrderProperty() {
		if (is_string($this->order)) {
			$this->order = $this->_prefixAlias($this->order);
		}
		if (is_array($this->order)) {
			foreach ($this->order as $key => $value) {
				if (is_numeric($key)) {
					$this->order[$key] = $this->_prefixAlias($value);
				} else {
					$newKey = $this->_prefixAlias($key);
					$this->order[$newKey] = $value;
					if ($newKey !== $key) {
						unset($this->order[$key]);
					}
				}
			}
		}
	}

	/**
	 * Checks if a string of a field name contains a dot if not it will add it and add the alias prefix.
	 *
	 * @param string
	 * @return string
	 */
	protected function _prefixAlias($string) {
		if (strpos($string, '.') === false) {
			return $this->alias() . '.' . $string;
		}
		return $string;
	}

	/**
	 * Return the next auto increment id from the current table
	 * UUIDs will return false
	 *
	 * @return int|bool next auto increment value or False on failure
	 */
	public function getNextAutoIncrement() {
		$query = "SHOW TABLE STATUS WHERE name = '" . $this->table() . "'";
		$statement = $this->_connection->execute($query);
		$result = $statement->fetch();
		if (!isset($result[10])) {
			return false;
		}
		return (int)$result[10];
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
	 * @param string $tableName The related model
	 * @param string $groupField Field to group by
	 * @param string $type Find type
	 * @param array $options
	 * @return array
	 */
	public function getRelatedInUse($tableName, $groupField = null, $type = 'all', $options = []) {
		if ($groupField === null) {
			$groupField = $this->belongsTo[$tableName]['foreignKey'];
		}
		$defaults = [
			'contain' => [$tableName],
			'group' => $groupField,
			'order' => isset($this->$tableName->order) ? $this->$tableName->order : [$tableName . '.' . $this->$tableName->displayField() => 'ASC'],
		];
		if ($type === 'list') {
			$defaults['fields'] = [$tableName . '.' . $this->$tableName->primaryKey(), $tableName . '.' . $this->$tableName->displayField()];
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
	public function getFieldInUse($groupField, $type = 'all', $options = []) {
		$defaults = [
			'group' => $groupField,
			'order' => [$this->alias . '.' . $this->displayField => 'ASC'],
		];
		if ($type === 'list') {
			$defaults['fields'] = [$this->alias . '.' . $this->primaryKey, $this->alias . '.' . $this->displayField];
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
	public function validateIdentical($value, $options = [], array $context = []) {
		if (!is_array($options)) {
			$options = ['compare' => $options];
		}
		if (!isset($context['data'][$options['compare']])) {
			return false;
		}
		$compareValue = $context['data'][$options['compare']];

		$matching = ['string' => 'string', 'int' => 'integer', 'float' => 'float', 'bool' => 'boolean'];
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
	public function validateUrl($url, $options = [], array $context = []) {
		if (empty($url)) {
			if (!empty($options['allowEmpty']) && empty($options['required'])) {
				return true;
			}
			return false;
		}
		if (!isset($options['autoComplete']) || $options['autoComplete'] !== false) {
			$url = $this->_autoCompleteUrl($url);
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
	public function validateDateTime($value, $options = [], array $context = []) {
		if (!$value) {
			if (!empty($options['allowEmpty'])) {
				return true;
			}
			return false;
		}
		$format = !empty($options['dateFormat']) ? $options['dateFormat'] : 'ymd';

		if (!is_object($value)) {
			$value = new Time($value);
		}
		$pieces = $value->format(FORMAT_DB_DATETIME);
		$dateTime = explode(' ', $pieces, 2);
		$date = $dateTime[0];
		$time = (!empty($dateTime[1]) ? $dateTime[1] : '');

		if (!empty($options['allowEmpty']) && (empty($date) && empty($time) || $date === DEFAULT_DATE && $time === DEFAULT_TIME || $date === DEFAULT_DATE && empty($time))) {
			return true;
		}

		//TODO: cleanup
		if (Validation::date($date, $format) && Validation::time($time)) {
			// after/before?
			$seconds = isset($options['min']) ? $options['min'] : 1;
			if (!empty($options['after']) && isset($context['data'][$options['after']])) {
				$compare = $value->subSeconds($seconds);
				if (!is_object($context['data'][$options['after']])) {
					$context['data'][$options['after']] = new Time($context['data'][$options['after']]);
				}
				if ($context['data'][$options['after']]->gt($compare)) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($context['data'][$options['before']])) {
				$compare = $value->addSeconds($seconds);
				if (!is_object($context['data'][$options['before']])) {
					$context['data'][$options['before']] = new Time($context['data'][$options['before']]);
				}
				if ($context['data'][$options['before']]->lt($compare)) {
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
	public function validateDate($value, $options = [], array $context = []) {
		if (!$value) {
			if (!empty($options['allowEmpty'])) {
				return true;
			}
			return false;
		}
		$format = !empty($options['format']) ? $options['format'] : 'ymd';
		if (!is_object($value)) {
			$value = new Time($value);
		}
		$date = $value->format(FORMAT_DB_DATE);

		if (!empty($options['allowEmpty']) && (empty($date) || $date == DEFAULT_DATE)) {
			return true;
		}
		if (Validation::date($date, $format)) {
			// after/before?
			$days = !empty($options['min']) ? $options['min'] : 0;
			if (!empty($options['after']) && isset($context['data'][$options['after']])) {
				$compare = $value->subDays($days);
				if ($context['data'][$options['after']]->gt($compare)) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($context['data'][$options['before']])) {
				$compare = $value->addDays($days);
				if ($context['data'][$options['before']]->lt($compare)) {
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
	public function validateTime($value, $options = [], array $context = []) {
		if (!$value) {
			return false;
		}
		$dateTime = explode(' ', $value, 2);
		$value = array_pop($dateTime);

		if (Validation::time($value)) {
			// after/before?
			if (!empty($options['after']) && isset($context['data'][$options['after']])) {
				if ($context['data'][$options['after']] >= $value) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($context['data'][$options['before']])) {
				if ($context['data'][$options['before']] <= $value) {
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
	public function validateDateRange($value, $options = [], array $context = []) {
	}

	/**
	 * Validation of Time Fields (>= minTime && <= maxTime)
	 *
	 * @param options
	 * - min/max (TODO!!)
	 */
	public function validateTimeRange($value, $options = [], array $context = []) {
	}

}
