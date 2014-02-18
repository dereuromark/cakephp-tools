<?php
App::uses('ModelBehavior', 'Model');

/**
 * Improved version to go beyond the current model (joins) to populate dropdowns
 * downpoint if recursive is too high by default: too many other table entries read out as well!!! to -1 before, if only one table is needed!
 * Important: Needs full Model.field setup and containable/recursive set properly! otherwise query might fail
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
 * The most important advantage over the db layer is that you can use custom PHP callbacks to insert
 * specific content into the values.
 *
 * It is best to attach this behavior dynamically prior to the find(list) call:
 *
 * $Model->Behaviors->load('Tools.MultipleDisplayFields', $config);
 *
 * @see: http://bakery.cakephp.org/articles/view/multiple-display-field-3
 * @license MIT
 * @modified Mark Scherer
 */
class MultipleDisplayFieldsBehavior extends ModelBehavior {

	protected $_defaults = array(
		'fields' => array(),
		'defaults' => array(), // default values in case a field is empty/null
		'pattern' => null, // automatically uses `%s %s %s ...` as many times as needed
		'displayField' => null, // defaults to current $displayField - only needed for other than find(list)
		//'callback' => null, // instead of a pattern you could also use a custom model method as callback here
		//'on' => array('list'),
	);

	/**
	 * MultipleDisplayFieldsBehavior::setup()
	 *
	 * @param Model $Model
	 * @param array $config
	 * @return void
	 */
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
		} else {
			$fields = isset($config['fields']) ? count($config['fields']) : 0;
			$this->settings[$Model->alias]['pattern'] = trim(str_repeat('%s ', $fields));
		}
		if (isset($config['defaults'])) {
			$this->settings[$Model->alias]['defaults'] = $config['defaults'];
		}
	}

	/**
	 * MultipleDisplayFieldsBehavior::afterFind()
	 *
	 * @param Model $Model
	 * @param array $results
	 * @param boolean $primary
	 * @return array Modified results
	 */
	public function afterFind(Model $Model, $results, $primary = false) {
		if (empty($this->settings[$Model->alias]['multiple_display_fields'])) {
			return $results;
		}
		// if displayFields is set, attempt to populate
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

				$string = '';
				if (!empty($this->settings[$Model->alias]['defaults'])) {
					foreach ($params as $k => $v) {
						if ($k > 0) {
							if (isset($this->settings[$Model->alias]['defaults'][$k - 1]) && empty($v)) {
								$params[$k] = $this->settings[$Model->alias]['defaults'][$k - 1];
								$string = $params[$k];
							} elseif (!empty($string)) {	// use the previous string if available (e.g. if only one value is given for all)
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

	/**
	 * MultipleDisplayFieldsBehavior::beforeFind()
	 *
	 * @param Model $Model
	 * @param array $queryData
	 * @return array Modified queryData
	 */
	public function beforeFind(Model $Model, $queryData) {
		if (isset($queryData['list']) && !isset($this->settings[$Model->alias]['multiple_display_fields'])) {
			// MOD 2009-01-09 ms (fixes problems with model related index functions - somehow gets triggered even on normal find queries...)
			$this->settings[$Model->alias]['multiple_display_fields'] = 1;

			// substr is used to get rid of "{n}" fields' prefix...
			array_push($queryData['fields'], substr($queryData['list']['keyPath'], 4));
			foreach ($this->settings[$Model->alias]['fields'] as $mName => $mFields) {
				foreach ($mFields as $mField) {
					array_push($queryData['fields'], $mName . '.' . $mField);
				}
			}
		} else {
			$this->settings[$Model->alias]['multiple_display_fields'] = 0;
		}
		return $queryData;
	}

}
