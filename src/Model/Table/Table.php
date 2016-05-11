<?php

namespace Tools\Model\Table;

use Cake\Routing\Router;
use Cake\Validation\Validation;
use Shim\Model\Table\Table as ShimTable;
use Tools\Utility\Time;
use Tools\Utility\Utility;

class Table extends ShimTable {

	/**
	 * @param array $entities
	 * @return bool
	 */
	public function validateAll(array $entities) {
		foreach ($entities as $entity) {
			if ($entity->errors()) {
				return false;
			}
		}

		return true;
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
	 *    'unique' => ['rule' => 'validateUnique', 'provider' => 'table']
	 * ])
	 * }}}
	 *
	 * Unique validation can be scoped to the value of another column:
	 *
	 * {{{
	 * $validator->add('email', [
	 *    'unique' => [
	 *        'rule' => ['validateUnique', ['scope' => 'site_id']],
	 *        'provider' => 'table'
	 *    ]
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
	 * @param string|null $groupField Field to group by
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
	public function getFieldInUse($groupField, $type = 'all', array $options = []) {
		$defaults = [
			'group' => $groupField,
			'order' => [$this->displayField() => 'ASC'],
		];
		if ($type === 'list') {
			$defaults['fields'] = ['' . $this->primaryKey(), '' . $this->displayField()];
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
	 * @param array $context
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
		return $compareValue === $value;
	}

	/**
	 * Checks if a URL is valid AND accessible (returns false otherwise)
	 *
	 * Options:
	 * - allowEmpty TRUE/FALSE (TRUE: if empty => return TRUE)
	 * - required TRUE/FALSE (TRUE: overrides allowEmpty)
	 * - autoComplete (default: TRUE)
	 * - deep (default: TRUE)
	 *
	 * @param array|string $url Full URL starting with http://...
	 * @param array $options
	 * @param array $context
	 * @return bool Success
	 */
	public function validateUrl($url, array $options = [], array $context = []) {
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
	 * @param string $url
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
	 * @param mixed $value
	 * @param array $options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min/max (defaults to >= 1 - at least 1 minute apart)
	 * @param array $context
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

		if (!empty($options['allowEmpty']) && (empty($date) && empty($time))) {
			return true;
		}

		if (Validation::date($date, $format) && Validation::time($time)) {
			// after/before?
			$seconds = isset($options['min']) ? $options['min'] : 1;
			if (!empty($options['after'])) {
				if (!is_object($options['after']) && isset($context['data'][$options['after']])) {
					$options['after'] = $context['data'][$options['after']];
					if (!is_object($options['after'])) {
						$options['after'] = new Time($options['after']);
					}
				} elseif (!is_object($options['after'])) {
					return false;
				}
			}
			if (!empty($options['before'])) {
				if (!is_object($options['before']) && isset($context['data'][$options['before']])) {
					$options['before'] = $context['data'][$options['before']];
					if (!is_object($options['before'])) {
						$options['before'] = new Time($options['before']);
					}
				} elseif (!is_object($options['before'])) {
					return false;
				}
			}

			// We need this for those not using immutable objects just yet
			$compareValue = clone $value;

			if (!empty($options['after'])) {
				$compare = $compareValue->subSeconds($seconds);
				if ($options['after']->gt($compare)) {
					return false;
				}
				if (!empty($options['max'])) {
					$after = $options['after']->addSeconds($options['max']);
					if ($value->gt($after)) {
						return false;
					}
				}
			}
			if (!empty($options['before'])) {
				$compare = $compareValue->addSeconds($seconds);
				if ($options['before']->lt($compare)) {
					return false;
				}
				if (!empty($options['max'])) {
					$after = $options['before']->subSeconds($options['max']);
					if ($value->lt($after)) {
						return false;
					}
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Validation of Date fields (as the core one is buggy!!!)
	 *
	 * @param mixed $value
	 * @param array $options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min (defaults to 0 - equal is OK too)
	 * @param array $context
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
	 * @param mixed $value
	 * @param array $options
	 * - timeFormat (defaults to 'hms')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min/max (defaults to >= 1 - at least 1 minute apart)
	 * @param array $context
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
	 * @param mixed $value
	 * @param array $options
	 * - min/max (TODO!!)
	 * @param array $context
	 * @return bool
	 */
	public function validateDateRange($value, $options = [], array $context = []) {
	}

	/**
	 * Validation of Time Fields (>= minTime && <= maxTime)
	 *
	 * @param mixed $value
	 * @param array $options
	 * - min/max (TODO!!)
	 * @param array $context
	 * @return bool
	 */
	public function validateTimeRange($value, $options = [], array $context = []) {
	}

}
