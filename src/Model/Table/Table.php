<?php

namespace Tools\Model\Table;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Validation\Validation;
use InvalidArgumentException;
use Shim\Model\Table\Table as ShimTable;
use Tools\I18n\Date;
use Tools\I18n\DateTime;
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
 *
 * @template TBehaviors of array<string, \Cake\ORM\Behavior> = array{}
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
	 * @override To allow multiple scoped fields with NULL values.
	 *
	 * @param mixed $value The value of column to be checked for uniqueness
	 * @param array<string, mixed> $options The options array, optionally containing the 'scope' key
	 * @param array|null $context The validation context as provided by the validation routine
	 * @return bool true if the value is unique
	 */
	public function validateUniqueExt($value, array $options = [], ?array $context = null) {
		$data = $context['data'] ?? null;
		if ($data) {
			foreach ($data as $field => $value) {
				if (empty($options['scope']) || !in_array($field, $options['scope'], true)) {
					continue;
				}

				if ($value !== '') {
					continue;
				}

				$data[$field] = null;
			}
			$context['data'] = $data;
		}

		return parent::validateUnique($value, $options, $context);
	}

	/**
	 * truncate()
	 *
	 * @return void
	 */
	public function truncate() {
		/** @var \Cake\Database\Schema\TableSchema $schema */
		$schema = $this->getSchema();
		assert($this->_connection !== null);
		$sql = $schema->truncateSql($this->_connection);
		foreach ($sql as $snippet) {
			$this->_connection->execute($snippet);
		}
	}

	/**
	 * Get all related entries that have been used so far
	 *
	 * For optional columns used for relations, make sure to set IS NOT NULL conditions here.
	 *   ->getRelatedInUse('Related', null, 'list', ['conditions' => ['optional_relation_id IS NOT' => null]])->toArray();
	 *
	 * @param string $tableName The related model
	 * @param string|null $groupField Field to group by
	 * @param string $type Find type
	 * @param array<string, mixed> $options
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function getRelatedInUse($tableName, $groupField = null, $type = 'all', array $options = []) {
		if ($groupField === null) {
			/** @var string $groupField */
			$groupField = $this->getAssociation($tableName)->getForeignKey();
		}
		$tableClass = $this->$tableName->getClassName();
		$table = TableRegistry::getTableLocator()->get($tableClass);
		/** @var string $displayField */
		$displayField = $table->getDisplayField();
		$order = property_exists($table, 'order') ? $table->order : [$tableName . '.' . $displayField => 'ASC'];

		$defaults = [
			'contain' => [$tableName],
			'group' => $groupField,
			'order' => $order,
		];
		if (!empty($options['conditions'])) {
			$defaults['conditions'] = $options['conditions'];
		}

		if ($type === 'list') {
			$propertyName = $this->getAssociation($tableName)->getProperty();
			$defaults['fields'] = [$tableName . '.' . $this->$tableName->getPrimaryKey(), $tableName . '.' . $displayField];
			$defaults['keyField'] = $propertyName . '.' . $this->$tableName->getPrimaryKey();
			$defaults['valueField'] = $propertyName . '.' . $this->$tableName->getDisplayField();

			if ($this->$tableName->getPrimaryKey() === $this->$tableName->getDisplayField()) {
				$defaults['group'] = [$tableName . '.' . $this->$tableName->getDisplayField()];
			} else {
				$defaults['group'] = [$tableName . '.' . $this->$tableName->getPrimaryKey(), $tableName . '.' . $this->$tableName->getDisplayField()];
			}

			$options += $defaults;
		}

		$options += $defaults;

		return $this->find($type, ...$options);
	}

	/**
	 * Get all fields that have been used so far.
	 *
	 * Warning: This only works on ONLY_FULL_GROUP_BY disabled (and not in Postgres right now).
	 *
	 * @deprecated Use getRelatedInUse() instead?
	 * @param string $groupField Field to group by
	 * @param string $type Find type
	 * @param array<string, mixed> $options
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function getFieldInUse($groupField, $type = 'all', array $options = []) {
		/** @var string $displayField */
		$displayField = $this->getDisplayField();
		$defaults = [
			'group' => $groupField,
			'order' => [$displayField => 'ASC'],
		];
		if ($type === 'list') {
			$defaults['fields'] = [$this->getPrimaryKey(), $this->getDisplayField(), $groupField];
			$defaults['keyField'] = $this->getPrimaryKey();
			$defaults['valueField'] = $this->getDisplayField();
		}
		$options += $defaults;

		return $this->find($type, ...$options);
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
	 * @param array<string, mixed> $options
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
			if (!is_string($url)) {
				throw new InvalidArgumentException('Can only accept string for autoComplete case');
			}
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
			if (!is_string($url)) {
				throw new InvalidArgumentException('Can only accept string for sameDomain case');
			}
			/** @var string $is */
			$is = parse_url($url, PHP_URL_HOST);
			$expected = (string)env('HTTP_HOST');
			if (mb_strtolower($is) !== mb_strtolower($expected)) {
				return false;
			}
		}

		if (isset($options['deep']) && $options['deep'] === false) {
			return true;
		}

		if (!is_string($url)) {
			throw new InvalidArgumentException('Can only accept string for deep case');
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
	 * @param array<string, mixed> $options
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

		/** @var \Cake\Chronos\Chronos|mixed $datetime */
		$datetime = $value;
		if (!is_object($value)) {
			$datetime = new DateTime($value);
		}
		$pieces = $datetime->format(FORMAT_DB_DATETIME);
		$dateTimeParts = explode(' ', $pieces, 2);
		$datePart = $dateTimeParts[0];
		$timePart = (!empty($dateTimeParts[1]) ? $dateTimeParts[1] : '');

		if (!empty($options['allowEmpty']) && (empty($datePart) && empty($timePart))) {
			return true;
		}

		if (Validation::date($datePart, $format) && Validation::time($timePart)) {
			// after/before?
			$seconds = $options['min'] ?? 1;

			$after = $options['after'] ?? null;
			if ($after && !is_object($after)) {
				$after = $context['data'][$options['after']] ?? null;
			}
			if ($after) {
				if (!is_object($after)) {
					$after = new DateTime($after);
				} elseif ($after instanceof \DateTimeInterface && !($after instanceof DateTime)) {
					$after = new DateTime($after);
				}
			}

			$before = $options['before'] ?? null;
			if ($before && !is_object($before)) {
				$before = $context['data'][$options['before']] ?? null;
			}
			if ($before) {
				if (!is_object($before)) {
					$before = new DateTime($before);
				} elseif ($before instanceof \DateTimeInterface && !($before instanceof DateTime)) {
					$before = new DateTime($before);
				}
			}

			// We need this for those not using immutable objects just yet
			/** @var \Tools\I18n\DateTime $compareValue */
			$compareValue = clone $datetime;

			if ($after) {
				/** @var \Tools\I18n\DateTime $after */
				$compare = $compareValue->subSeconds($seconds);
				if ($after->greaterThan($compare)) {
					return false;
				}
				if (!empty($options['max'])) {
					$afterMax = $after->addSeconds($options['max']);
					if ($datetime->greaterThan($afterMax)) {
						return false;
					}
				}
			}
			if ($before) {
				/** @var \Tools\I18n\DateTime $before */
				$compare = $compareValue->addSeconds($seconds);
				if ($before->lessThan($compare)) {
					return false;
				}
				if (!empty($options['max'])) {
					$beforeMax = $before->subSeconds($options['max']);
					if ($datetime->lessThan($beforeMax)) {
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
	 * @param array<string, mixed> $options
	 * - dateFormat (defaults to 'ymd')
	 * - allowEmpty
	 * - after/before (fieldName to validate against)
	 * - min (defaults to 0 - equal is OK too)
	 * @param array $context
	 * @return bool Success
	 */
	public function validateDate($value, array $options = [], array $context = []) {
		if (!$value || (is_array($value) && empty($value['year']) && empty($value['month']) && empty($value['day']))) {
			if (!empty($options['allowEmpty'])) {
				return true;
			}

			return false;
		}

		$format = !empty($options['format']) ? $options['format'] : 'ymd';

		/** @var \Cake\Chronos\ChronosDate|mixed $date */
		$date = $value;
		if (!is_object($value)) {
			$date = new Date($value);
		} elseif ($value instanceof \DateTimeInterface) {
			$date = new Date($value);
		}

		if (Validation::date($value, $format)) {
			// after/before?
			$days = !empty($options['min']) ? $options['min'] : 0;

			$after = $options['after'] ?? null;
			if ($after && !is_object($after)) {
				$after = $context['data'][$options['after']] ?? null;
			}
			if ($after) {
				$compare = $date->subDays($days);
				/** @var \Cake\I18n\DateTime $after */
				if (!is_object($after)) {
					$after = new Date($after);
				} elseif ($after instanceof \DateTimeInterface) {
					$after = new Date($after);
				}
				if ($after->greaterThan($compare)) {
					return false;
				}
			}

			$before = $options['before'] ?? null;
			if ($before && !is_object($before)) {
				$before = $context['data'][$options['before']] ?? null;
			}
			if ($before) {
				$compare = $date->addDays($days);
				/** @var \Cake\I18n\DateTime $before */
				if (!is_object($before)) {
					$before = new Date($before);
				} elseif ($before instanceof \DateTimeInterface) {
					$before = new Date($before);
				}
				if ($before->lessThan($compare)) {
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
	 * @param array<string, mixed> $options
	 * - timeFormat (defaults to 'hms')
	 * - allowEmpty
	 * - after/before (fieldName to validate against or Time object)
	 * - min (defaults to 1 - at least 1 second apart)
	 * - max (maximum seconds apart)
	 * @param array $context
	 * @return bool Success
	 */
	public function validateTime($value, array $options = [], array $context = []) {
		if (!$value) {
			if (!empty($options['allowEmpty'])) {
				return true;
			}

			return false;
		}

		// Extract time string for validation
		$timeString = $value;
		if (is_object($value)) {
			if ($value instanceof \DateTimeInterface) {
				$timeString = $value->format('H:i:s');
			}
		} else {
			// Extract time part if datetime string is provided
			$dateTimeParts = explode(' ', (string)$value, 2);
			$timeString = array_pop($dateTimeParts);
		}

		// Validate time format first before creating Time object
		if (!Validation::time($timeString)) {
			return false;
		}

		// Convert to Time object for comparisons
		if (is_object($value) && $value instanceof Time) {
			$time = $value;
		} else {
			$time = new Time($timeString);
		}

		// after/before?
		$minSeconds = $options['min'] ?? 1;

		$after = $options['after'] ?? null;
		if ($after && !is_object($after)) {
			$after = $context['data'][$options['after']] ?? null;
		}
		if ($after) {
			if (!is_object($after)) {
				// Extract time part if string contains datetime
				$dateTimeParts = explode(' ', (string)$after, 2);
				$timeString = array_pop($dateTimeParts);
				$after = new Time($timeString);
			} elseif ($after instanceof \DateTimeInterface && !($after instanceof Time)) {
				$after = new Time($after->format('H:i:s'));
			}

			/** @var \Cake\I18n\Time $after */
			// Convert to total seconds for comparison
			$timeInSeconds = $time->getHours() * 3600 + $time->getMinutes() * 60 + $time->getSeconds();
			$afterInSeconds = $after->getHours() * 3600 + $after->getMinutes() * 60 + $after->getSeconds();

			// Check if time is after the threshold (after + min seconds)
			if ($afterInSeconds + $minSeconds > $timeInSeconds) {
				return false;
			}

			// Check max range if specified
			if (!empty($options['max'])) {
				if ($timeInSeconds > $afterInSeconds + $options['max']) {
					return false;
				}
			}
		}

		/** @var \Cake\I18n\Time|string|null $before */
		$before = $options['before'] ?? null;
		if ($before && !is_object($before)) {
			/** @var \Cake\I18n\Time|string|null $before */
			$before = $context['data'][$options['before']] ?? null;
		}
		if ($before) {
			if (!is_object($before)) {
				// Extract time part if string contains datetime
				$dateTimeParts = explode(' ', (string)$before, 2);
				$timeString = array_pop($dateTimeParts);
				$before = new Time($timeString);
			} elseif ($before instanceof \DateTimeInterface && !($before instanceof Time)) {
				$before = new Time($before->format('H:i:s'));
			}

			// Convert to total seconds for comparison
			$timeInSeconds = $time->getHours() * 3600 + $time->getMinutes() * 60 + $time->getSeconds();
			$beforeInSeconds = $before->getHours() * 3600 + $before->getMinutes() * 60 + $before->getSeconds();

			// Check if time is before the threshold (before - min seconds)
			if ($beforeInSeconds - $minSeconds < $timeInSeconds) {
				return false;
			}

			// Check max range if specified
			if (!empty($options['max'])) {
				if ($timeInSeconds < $beforeInSeconds - $options['max']) {
					return false;
				}
			}
		}

		return true;
	}

}
