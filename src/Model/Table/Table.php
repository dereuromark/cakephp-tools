<?php

namespace Tools\Model\Table;

use Cake\Routing\Router;
use Cake\Validation\Validation;
use Shim\Model\Table\Table as ShimTable;
use Tools\Utility\Time;
use Tools\Utility\Utility;

/**
 * @mixin \Tools\Model\Behavior\PasswordableBehavior
 * @mixin \Tools\Model\Behavior\JsonableBehavior
 * @mixin \Tools\Model\Behavior\BitmaskedBehavior
 * @mixin \Tools\Model\Behavior\SluggedBehavior
 * @mixin \Tools\Model\Behavior\NeighborBehavior
 * @mixin \Tools\Model\Behavior\StringBehavior
 * @mixin \Tools\Model\Behavior\ConfirmableBehavior
 * @mixin \Tools\Model\Behavior\ResetBehavior
 */
class Table extends ShimTable {

	/**
	 * @param array $entities
	 * @return bool
	 */
	public function validateAll(array $entities): bool {
		foreach ($entities as $entity) {
			if ($entity->getErrors()) {
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
		$query = "SHOW TABLE STATUS WHERE name = '" . $this->getTable() . "'";
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
		/** @var \Cake\Database\Schema\SqlGeneratorInterface $schema */
		$schema = $this->getSchema();
		$sql = $schema->truncateSql($this->_connection);
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
	 * @return \Cake\ORM\Query
	 */
	public function getRelatedInUse($tableName, $groupField = null, $type = 'all', array $options = []) {
		if ($groupField === null) {
			/** @var string $groupField */
			$groupField = $this->getAssociation($tableName)->getForeignKey();
		}
		$defaults = [
			'contain' => [$tableName],
			'group' => $groupField,
			'order' => isset($this->$tableName->order) ? $this->$tableName->order : [$tableName . '.' . $this->$tableName->getDisplayField() => 'ASC'],
		];
		if ($type === 'list') {
			$propertyName = $this->getAssociation($tableName)->getProperty();
			$defaults['fields'] = [$tableName . '.' . $this->$tableName->getPrimaryKey(), $tableName . '.' . $this->$tableName->getDisplayField()];
			$defaults['keyField'] = $propertyName . '.' . $this->$tableName->getPrimaryKey();
			$defaults['valueField'] = $propertyName . '.' . $this->$tableName->getDisplayField();

			if ($this->$tableName->getPrimaryKey() === $this->$tableName->getDisplayField()) {
				$defaults['group'] = [$tableName . '.' . $this->$tableName->getDisplayField()];
			} else {
				$defaults['group'] = [$tableName . '.' . $this->$tableName->getPrimaryKey(), $tableName . '.' . $this->$tableName->getDisplayField()];
			}
		}
		$options += $defaults;

		return $this->find($type, $options);
	}

	/**
	 * Get all fields that have been used so far.
	 *
	 * Warning: This only works on ONLY_FULL_GROUP_BY disabled (and not in Postgres right now).
	 *
	 * @param string $groupField Field to group by
	 * @param string $type Find type
	 * @param array $options
	 * @return \Cake\ORM\Query
	 */
	public function getFieldInUse($groupField, $type = 'all', array $options = []) {
		$defaults = [
			'group' => $groupField,
			'order' => [$this->getDisplayField() => 'ASC'],
		];
		if ($type === 'list') {
			$defaults['fields'] = [$this->getPrimaryKey(), $this->getDisplayField(), $groupField];
			$defaults['keyField'] = $this->getPrimaryKey();
			$defaults['valueField'] = $this->getDisplayField();
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
	 * @param array|string $options
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
		if (!$url) {
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
	 * @return string URL
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
	public function validateDateTime($value, array $options = [], array $context = []) {
		if (!$value) {
			if (!empty($options['allowEmpty'])) {
				return true;
			}
			return false;
		}
		$format = !empty($options['dateFormat']) ? $options['dateFormat'] : 'ymd';

		/** @var \Cake\I18n\Time $time */
		$time = $value;
		if (!is_object($value)) {
			$time = new Time($value);
		}
		$pieces = $time->format(FORMAT_DB_DATETIME);
		$dateTime = explode(' ', $pieces, 2);
		$datePart = $dateTime[0];
		$timePart = (!empty($dateTime[1]) ? $dateTime[1] : '');

		if (!empty($options['allowEmpty']) && (empty($datePart) && empty($timePart))) {
			return true;
		}

		if (Validation::date($datePart, $format) && Validation::time($timePart)) {
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
			$compareValue = clone $time;

			if (!empty($options['after'])) {
				$compare = $compareValue->subSeconds($seconds);
				if ($options['after']->gt($compare)) {
					return false;
				}
				if (!empty($options['max'])) {
					$after = $options['after']->addSeconds($options['max']);
					if ($time->gt($after)) {
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
					if ($time->lt($after)) {
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
	public function validateDate($value, array $options = [], array $context = []) {
		if (!$value) {
			if (!empty($options['allowEmpty'])) {
				return true;
			}
			return false;
		}
		$format = !empty($options['format']) ? $options['format'] : 'ymd';

		$dateTime = $value;
		if (!is_object($value)) {
			$dateTime = new Time($value);
		}
		if (!empty($options['allowEmpty']) && empty($dateTime)) {
			return true;
		}

		if (Validation::date($value, $format)) {
			// after/before?
			$days = !empty($options['min']) ? $options['min'] : 0;
			if (!empty($options['after']) && isset($context['data'][$options['after']])) {
				$compare = $dateTime->subDays($days);
				/** @var \Cake\I18n\Time $after */
				$after = $context['data'][$options['after']];
				if (!is_object($after)) {
					$after = new Time($after);
				}
				if ($after->gt($compare)) {
					return false;
				}
			}
			if (!empty($options['before']) && isset($context['data'][$options['before']])) {
				$compare = $dateTime->addDays($days);
				/** @var \Cake\I18n\Time $before */
				$before = $context['data'][$options['before']];
				if (!is_object($before)) {
					$before = new Time($before);
				}
				if ($before->lt($compare)) {
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
	public function validateTime($value, array $options = [], array $context = []) {
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

}
