<?php
App::uses('ModelBehavior', 'Model');

/**
 * Improved version to go beyond the current model (joins) to populate dropdowns
 * downpoint if recursive is too high by default: too many other table entries read out as well!!! to -1 before, if only one table is needed!
 * Note: NEEDS full Model.field setup! otherwise query fails
 *
 * Example:
 *
 * $config = array(
 *   'fields' => array($Model->alias . '.first_name', $Model->alias . '.last_name'),
 *   'pattern' => '%s %s'
 * );
 *
 * Note: With cake2.x and virtual fields this is not that much needed anymore, but can
 * still be quite helpful if you want to simply concatinate fields of a query without
 * leveraging the db layer.
 *
 * @see: http://bakery.cakephp.org/articles/view/multiple-display-field-3
 * @license MIT
 * @modified Mark Scherer
 * 2009-01-06 ms
 */
class MultipleDisplayFieldsBehavior extends ModelBehavior {

	protected $_defaults = array(
		'fields' => array(),
		'displayField' => null,// defaults to current $displayField
		'defaults' => array(),
		'pattern' => null, // automatically uses `%s %s %s ...` as many times as needed
		//'on' => array('list'),
	);

	public function setup(Model $Model, $config = array()) {
		$this->settings[$Model->alias] = $this->_defaults;

		if (isset($config['fields'])) {
			$myFields = array();
			foreach ($config['fields'] as $key => $val) {
				$modelField = explode('.', $val);
				if (empty($myFields[$modelField[0]])) $myFields[$modelField[0]] = array();
				$myFields[$modelField[0]][] = $modelField[1];
			}
			$this->settings[$Model->alias]['fields'] = $myFields;
		}
		if (isset($config['pattern'])) {
			$this->settings[$Model->alias]['pattern'] = $config['pattern'];
		}
		# MOD 2009-01-06 ms
		if (isset($config['defaults'])) {
			$this->settings[$Model->alias]['defaults'] = $config['defaults'];
		}
	}

	public function afterFind(Model $Model, $results, $primary) {
		if (empty($this->settings[$Model->alias]['multiple_display_fields'])) {
			return $results;
		}
		# if displayFields is set, attempt to populate
		foreach ($results as $key => $result) {
			$displayFieldValues = array();
			$fieldsPresent = true;

			foreach ($this->settings[$Model->alias]['fields'] as $mName => $mFields) {
				if (isset($result[$mName])) {
					foreach ($mFields as $mField) {
						if (array_key_exists($mField, $result[$mName])) {
							$fieldsPresent = $fieldsPresent && true;
							$displayFieldValues[] = $result[$mName][$mField];
						} else {
							$fieldsPresent = false;
						}
					}
				} else {
					$fieldsPresent = false;
				}
			}

			if ($fieldsPresent) {
				$params = array_merge(array($this->settings[$Model->alias]['pattern']), $displayFieldValues);

				# MOD 2009-01-06 ms
				$string = '';
				if (!empty($this->settings[$Model->alias]['defaults'])) {
					foreach ($params as $k => $v) {
						if ($k > 0) {
							if (isset($this->settings[$Model->alias]['defaults'][$k-1]) && empty($v)) {
								$params[$k]=$this->settings[$Model->alias]['defaults'][$k-1];
								$string = $params[$k];
							} elseif (!empty($string)) {	# use the previous string if available (e.g. if only one value is given for all)
								$params[$k] = $string;
							}
						}
					}
				}

				$field = $Model->displayField;
				if (!empty($this->settings[$Model->alias]['displayField'])) {
					$field = $this->settings[$Model->alias]['displayField'];
				}
				$results[$key][$Model->alias][$field] = call_user_func_array('sprintf', $params);
			}
		}
		return $results;
	}

	public function beforeFind(Model $Model, $queryData) {
		if (isset($queryData['list']) && !isset($this->settings[$Model->alias]['multiple_display_fields'])) {
			# MOD 2009-01-09 ms (fixes problems with model related index functions - somehow gets triggered even on normal find queries...)
			$this->settings[$Model->alias]['multiple_display_fields'] = 1;
			//$queryData['fields'] = array();

			# substr is used to get rid of "{n}" fields' prefix...
			array_push($queryData['fields'], substr($queryData['list']['keyPath'], 4));
			foreach ($this->settings[$Model->alias]['fields'] as $mName => $mFields) {
				foreach ($mFields as $mField) {
					array_push($queryData['fields'], $mName. '.' . $mField);
				}
			}
		} else {
			# MOD 2009-01-09 ms
			$this->settings[$Model->alias]['multiple_display_fields'] = 0;
		}
		return $queryData;
	}

}
