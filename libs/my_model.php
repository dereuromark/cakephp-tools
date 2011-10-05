<?php

class MyModel extends Model {

	var $recursive = -1;
	var $actsAs = array('Containable');



	/**
	 * @return string Error message with error number
	 * 2010-11-06 ms
	 */
	public function lastError() {
		$db = $this->getDataSource();
		return $db->lastError();
	}



/** Validation Functions **/


	/**
	 * validates a primary or foreign key depending on the current schema data for this field
	 * recognizes uuid (char36) and aiid (int10 unsigned) - not yet mixed (varchar36)
	 * more useful than using numeric or notEmpty which are type specific
	 * @param array $data
	 * @param array $options
	 * - allowEmpty
	 * 2011-06-21 ms
	 */
	function validateKey($data = array(), $options = array()) {
		$key = array_shift(array_keys($data));
		$value = array_shift($data);

		$schema = $this->schema($key);
		if (!$schema) {
			return true;
		}
		
		$defaults = array(
			'allowEmpty' => false,
		);
		$options = am($defaults, $options);
		
		if ($schema['type'] != 'integer') {
			if ($options['allowEmpty'] && $value === '') {
				return true;
			}
			return Validation::uuid($value);
		}
		if ($options['allowEmpty'] && $value === 0) {
			return true;
		}
		return is_numeric($value) && (int)$value == $value && $value > 0;
	}

	/**
	 * checks if the passed enum value is valid
	 * 2010-02-09 ms
	 */
	function validateEnum($field = array(), $enum = null, $additionalKeys = array()) {
		$valueKey = array_shift(array_keys($field)); # auto-retrieve
		$value = $field[$valueKey];
		$keys = array();
		if ($enum === true) {
			$enum = $valueKey;
		}
		if ($enum !== null) {
			if (!method_exists($this, $enum)) {
				trigger_error('Enum method \'' . $enum . '()\' not exists', E_USER_ERROR);
				return false;
			}
			//TODO: make static
			$keys = $this->{$enum}();
		}
		$keys = array_merge($additionalKeys, array_keys($keys));
		if (!empty($keys) && in_array($value, $keys)) {
			return true;
		}
		return false;
	}


	/**
	 * checks if the content of 2 fields are equal
	 * Does not check on empty fields! Return TRUE even if both are empty (secure against empty in another rule)!
	 * 2009-01-22 ms
	 */
	function validateIdentical($data = array(), $compareWith = null, $options = array()) {
		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}
		$compareValue = $this->data[$this->alias][$compareWith];

		$matching = array('string' => 'string', 'int' => 'integer', 'float' => 'float', 'bool' => 'boolean');
		if (!empty($options['cast']) && array_key_exists($options['cast'], $matching)) {
			# cast values to string/int/float/bool if desired
			settype($compareValue, $matching[$options['cast']]);
			settype($value, $matching[$options['cast']]);
		}
		return ($compareValue === $value);
	}


	/**
	 * checks a record, if it is unique - depending on other fields in this table (transfered as array)
	 * example in model: 'rule' => array ('validateUnique',array('belongs_to_table_id','some_id','user_id')),
	 * if all keys (of the array transferred) match a record, return false, otherwise true
	 * @param ARRAY other fields
	 * TODO: add possibity of deep nested validation (User -> Comment -> CommentCategory: UNIQUE comment_id, Comment.user_id)
	 * 2010-01-30 ms
	 */
	function validateUnique($data, $fields = array(), $options = array()) {
		$id = (!empty($this->data[$this->alias]['id']) ? $this->data[$this->alias]['id'] : 0);

		foreach ($data as $key => $value) {
			$fieldName = $key;
			$fieldValue = $value; // equals: $this->data[$this->alias][$fieldName]
		}

		if (empty($fieldName) || empty($fieldValue)) { // return true, if nothing is transfered (check on that first)
			return true;
		}

		$conditions = array($this->alias . '.' . $fieldName => $fieldValue, // Model.field => $this->data['Model']['field']
			$this->alias . '.id !=' => $id, );

		# careful, if fields is not manually filled, the options will be the second param!!! big problem...
		foreach ((array )$fields as $dependingField) {
			if (isset($this->data[$this->alias][$dependingField])) { // add ONLY if some content is transfered (check on that first!)
				$conditions[$this->alias . '.' . $dependingField] = $this->data[$this->alias][$dependingField];

			} elseif (!empty($this->data['Validation'][$dependingField])) { // add ONLY if some content is transfered (check on that first!
				$conditions[$this->alias . '.' . $dependingField] = $this->data['Validation'][$dependingField];

			} elseif (!empty($id)) {
				# manual query! (only possible on edit)
				$res = $this->find('first', array('fields' => array($this->alias.'.'.$dependingField), 'conditions' => array($this->alias.'.id' => $this->data[$this->alias]['id'])));
				if (!empty($res)) {
					$conditions[$this->alias . '.' . $dependingField] = $res[$this->alias][$dependingField];
				}
			}
		}

		$this->recursive = -1;
		if (count($conditions) > 2) {
			$this->recursive = 0;
		}
		$res = $this->find('first', array('fields' => array($this->alias . '.id'), 'conditions' => $conditions));
		if (!empty($res)) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $data
	 * @param array $options
	 * - scope (array of other fields as scope - isUnique dependent on other fields of the table)
	 * - batch (defaults to true, remembers previous values in order to validate batch imports)
	 * example in model: 'rule' => array ('validateUniqueExt', array('scope'=>array('belongs_to_table_id','some_id','user_id'))),
	 * http://groups.google.com/group/cake-php/browse_thread/thread/880ee963456739ec
	 * //TODO: test!!!
	 * 2011-03-27 ms
	 */
	function validateUniqueExt($data, $options = array()) {
		foreach ($data as $key => $value) {
			$fieldName = $key;
			$fieldValue = $value;
		}
		$defaults = array('batch' => true, 'scope' => array());
		$options = array_merge($defaults, $options);

		# for batch
		if ($options['batch'] !== false && !empty($this->batchRecords)) {
			if (array_key_exists($value, $this->batchRecords[$fieldName])) {
				return $options['scope'] === $this->batchRecords[$fieldName][$value];
			}
		}

		# continue with validation
		if (!$this->validateUnique($data, $options['scope'])) {
			return false;
		}

		# for batch
		if ($options['batch'] !== false) {
			if (!isset($this->batchRecords)) {
				$this->batchRecords = array();
			}
			$this->batchRecords[$fieldName][$value] = $scope;
		}
		return true;
	}


	/**
	 * checks if a url is valid AND accessable (returns false otherwise)
	 * @param array/string $data: full url(!) starting with http://...
	 * @options
	 * - allowEmpty TRUE/FALSE (TRUE: if empty => return TRUE)
	 * - required TRUE/FALSE (TRUE: overrides allowEmpty)
	 * - autoComplete (default: TRUE)
	 * - deep (default: TRUE)
	 * 2010-10-18 ms
	 */
	function validateUrl($data, $options = array()) {
		//$arguments = func_get_args();

		if (is_array($data)) {
			$url = array_shift($data);
		} else {
			$url = $data;
		}

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

		# validation
		if (!Validation::url($url, $options['strict'])) {
			return false;
		}
		
		# same domain?
		if (!empty($options['sameDomain']) && !empty($_SERVER['HTTP_HOST'])) {
			$is = parse_url($url, PHP_URL_HOST);
			$expected = $_SERVER['HTTP_HOST'];
			if (mb_strtolower($is) !== mb_strtolower($expected)) {
				return false;
			}
		}

		if (isset($options['deep']) && $options['deep'] === false) {
			return true;
		}
		return $this->_validUrl($url);
	}

	function _autoCompleteUrl($url) {
		if (mb_strpos($url, '://') === false && mb_strpos($url, 'www.') === 0) {
			$url = 'http://' . $url;
		} elseif (mb_strpos($url, '/') === 0) {
			$url = Router::url($url, true);
		}
		return $url;
	}


	/**
	 * checks if a url is valid
	 * @param string url
	 * 2009-02-27 ms
	 */
	function _validUrl($url = null) {
		App::import('Component', 'Tools.Common');
		$headers = CommonComponent::getHeaderFromUrl($url);
		if ($headers !== false) {

			$headers = implode("\n", $headers);

			return ((bool)preg_match('#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers) && !(bool)preg_match('#^HTTP/.*\s+[(404|999)]+\s#i', $headers));
		}
		return false;
	}


	/**
	 * Validation of DateTime Fields (both Date and Time together)
	 * @param options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min/max (defaults to >= 1 - at least 1 minute apart)
	 * 2011-03-02 ms
	 */
	function validateDateTime($data, $options = array()) {
		$format = !empty($options['dateFormat']) ? $options['dateFormat'] : 'ymd';

		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}

		$dateTime = explode(' ', trim($value), 2);
		$date = $dateTime[0];
		$time = (!empty($dateTime[1]) ? $dateTime[1] : '');

		if (!empty($options['allowEmpty']) && (empty($date) && empty($time) || $date == DEFAULT_DATE && $time == DEFAULT_TIME || $date == DEFAULT_DATE && empty($time))) {
			return true;
		}
		/*
		if ($this->validateDate($date, $options) && $this->validateTime($time, $options)) {
			return true;
		}
		*/
		if (Validation::date($date, $format) && Validation::time($time)) {
			# after/before?
			$minutes = isset($options['min']) ? $options['min'] : 1;
			if (!empty($options['after']) && isset($this->data[$this->alias][$options['after']])) {
				if (strtotime($this->data[$this->alias][$options['after']]) > strtotime($value) - $minutes) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($this->data[$this->alias][$options['before']])) {
				if (strtotime($this->data[$this->alias][$options['before']]) < strtotime($value) + $minutes) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Validation of Date Fields (as the core one is buggy!!!)
	 * @param options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min (defaults to 0 - equal is OK too)
	 * 2011-03-02 ms
	 */
	function validateDate($data, $options = array()) {
		$format = !empty($options['format']) ? $options['format'] : 'ymd';
		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}

		$dateTime = explode(' ', trim($value), 2);
		$date = $dateTime[0];

		if (!empty($options['allowEmpty']) && (empty($date) || $date == DEFAULT_DATE)) {
			return true;
		}
		if (Validation::date($date, $format)) {
			# after/before?
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
	 * @param options
	 * - timeFormat (defaults to 'hms')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min/max (defaults to >= 1 - at least 1 minute apart)
	 * 2011-03-02 ms
	 */
	function validateTime($data, $options = array()) {
		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}

		$dateTime = explode(' ', trim($value), 2);
		$value = array_pop($dateTime);

		if (Validation::time($value)) {
			# after/before?
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


	//TODO
	/**
	 * Validation of Date Fields (>= minDate && <= maxDate)
	 * @param options
	 * - min/max (TODO!!)
	 * 2010-01-20 ms
	 */
	function validateDateRange($data, $options = array()) {

	}

	//TODO
	/**
	 * Validation of Time Fields (>= minTime && <= maxTime)
	 * @param options
	 * - min/max (TODO!!)
	 * 2010-01-20 ms
	 */
	function validateTimeRange($data, $options = array()) {

	}


	/**
	 * model validation rule for email addresses
	 * 2010-01-14 ms
	 */
	function validateUndisposable($data, $proceed = false) {
		$email = array_shift($data);
		if (empty($email)) {
			return true;
		}
		return $this->isUndisposableEmail($email, false, $proceed);
	}

	/**
	 * NOW: can be set to work offline only (if server is down etc)
	 *
	 * checks if a email is not from a garbige hoster
	 * @param string email (neccessary)
	 * @return boolean true if valid, else false
	 * 2009-03-09 ms
	 */
	function isUndisposableEmail($email, $onlineMode = false, $proceed = false) {
		if (!isset($this->UndisposableEmail)) {
			App::import('Vendor', 'undisposable');
			$this->UndisposableEmail = new UndisposableEmail();
		}
		if (!$onlineMode) {
			# crashed with white screen of death otherwise... (if foreign page is 404)
			$this->UndisposableEmail->useOnlineList(false);
		}
		if (!class_exists('Validation')) {
			App::import('Core', 'Validation');
		}
		if (!Validation::email($email)) {
			return false;
		}
		if ($this->UndisposableEmail->isUndisposableEmail($email) === false) {
			# trigger log
			$this->log('Disposable Email detected: ' . h($email).' (IP '.env('REMOTE_ADDR').')', 'undisposable');
			if ($proceed === true) {
				return true;
			}
			return false;
		}
		return true;
	}


	/**
	 * //TODO: move outside of MyModel? use more generic "blocked" plugin!
	 * is blocked email?
	 * 2009-12-22 ms
	 */
	function validateNotBlocked($params) {
		foreach ($params as $key => $value) {
			$email = $value;
		}
		if (!isset($this->BlockedEmail)) {
			if (!App::import('Model', 'Tools.BlockedEmail')) {
				trigger_error('Model Tools.BlockedEmail not available');
				return true;
			}
			$this->BlockedEmail = ClassRegistry::init('Tools.BlockedEmail');
		}
		if ($this->BlockedEmail->isBlocked($email)) {
			return false;
		}
		return true;
	}


	/**
	 * Overrides the Core invalidate function from the Model class
	 * with the addition to use internationalization (I18n and L10n)
	 * @param string $field Name of the table column
	 * @param mixed $value The message or value which should be returned
	 * @param bool $translate If translation should be done here
	 * 2010-01-22 ms
	 */
	function invalidate($field, $value = null, $translate = true) {
		if (!is_array($this->validationErrors)) {
			$this->validationErrors = array();
		}
		if (empty($value)) {
			$value = true;
		} else {
			$value = (array)$value;
		}

		if (is_array($value)) {
			$value[0] = $translate ? __($value[0], true) : $value[0];
			
			$args = array_slice($value, 1);
			$value = vsprintf($value[0], $args);
		}
		$this->validationErrors[$field] = $value;
	}



/** DEPRECATED STUFF - will be removed in stage 2 **/

	/**
	 * @param string $value or array $keys or NULL for complete array result
	 * @return string/array
	 * static enums
	 * @deprecated
	 * 2009-11-05 ms
	 */
	public static function enum($value, $options, $default = '') {
		if ($value !== null && !is_array($value)) {
			if (array_key_exists($value, $options)) {
				return $options[$value];
			}
			return $default;
		} elseif ($value !== null) {
			$newOptions = array();
			foreach ($value as $v) {
				$newOptions[$v] = $options[$v];
			}
			return $newOptions;
		}
		return $options;
	}

}
