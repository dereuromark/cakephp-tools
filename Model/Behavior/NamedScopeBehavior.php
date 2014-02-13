<?php
App::uses('ModelBehavior', 'Model');

// basic code taken and modified/fixed from https://github.com/netguru/namedscopebehavior

/**
 * Edited - Mark Scherer
 * - its now "scope" instead of "scopes" (singular and now analogous to "contain" etc)
 * - corrected syntax, indentation
 * - now probably cake2.x ready (awaiting tests)
 */
class NamedScopeBehavior extends ModelBehavior {

	protected $_defaults = array(
		'scope' => array()
	);

	public function setup(Model $Model, $settings = array()) {
		$this->settings[$Model->alias] = $settings + $this->_defaults;
	}

	public function beforeFind(Model $Model, $queryData) {
		$scopes = array();
		// passed as scopes
		if (!empty($queryData['scope'])) {
			$scope = !is_array($queryData['scope']) ? array($queryData['scope']) : $queryData['scope'];
			$scopes = array_merge($scopes, $scope);
		}
		// passed as conditions['scope']
		if (is_array($queryData['conditions']) && !empty($queryData['conditions']['scope'])) {
			$scope = !is_array($queryData['conditions']['scope']) ? array($queryData['conditions']['scope']) : $queryData['conditions']['scope'];
			unset($queryData['conditions']['scope']);
			$scopes = array_merge($scopes, $scope);
		}

		// if there are scopes defined, we need to get rid of possible condition set earlier by find() method if model->id was set
		if (!empty($scopes) && !empty($Model->id) && !empty($queryData['conditions']["`{$Model->alias}`.`{$Model->primaryKey}`"]) && $queryData['conditions']["`{$Model->alias}`.`{$Model->primaryKey}`"] ==
			$Model->id) {
			unset($queryData['conditions']["`{$Model->alias}`.`{$Model->primaryKey}`"]);
		}

		$queryData['conditions'][] = $this->_conditions($scopes, $Model->alias);
		return $queryData;
	}

	/**
	 * NamedScopeBehavior::scope()
	 *
	 * @param Model $Model
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function scope(Model $Model, $name = null, $value = null) {
		if ($name === null) {
			return $this->settings[$Model->alias]['scope'];
		}
		if (in_array($name, $this->settings[$Model->alias]['scope'])) {
			continue;
		}
		$this->settings[$Model->alias]['scope'][$name] = $value;
	}

	/**
	 * NamedScopeBehavior::_conditions()
	 *
	 * @param array $scopes
	 * @param string $modelName
	 * @return array
	 */
	protected function _conditions(array $scopes, $modelName) {
		$conditions = array();
		foreach ($scopes as $scope) {
			if (strpos($scope, '.')) {
				list($scopeModel, $scope) = explode('.', $scope);
			} else {
				$scopeModel = $modelName;
			}
			if (!empty($this->settings[$scopeModel]['scope'][$scope])) {
				$conditions[] = array($this->settings[$scopeModel]['scope'][$scope]);
			}
		}

		return $conditions;
	}

}
