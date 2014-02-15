<?php
App::uses('ModelBehavior', 'Model');

/**
 * A behavior to keep scopes and conditions DRY across multiple methods and models.
 * Those scopes can be added to any find() options array.
 * Additionally, custom scopedFinds can help to reduce the amount of methods needed
 * through a global config and one scopedFind() method.
 *
 * Basic idea taken and modified/fixed from https://github.com/netguru/namedscopebehavior
 * and https://github.com/josegonzalez/cakephp-simple-scope
 *
 * - it's now "scope" instead of "scopes" (singular and now analogous to "contain" etc)
 * - corrected syntax, indentation
 * - reads the model's 'scopes' attribute if applicable
 * - allows 'scopes' in scopedFind()
 *
 * If used across models, it is advisable to load this globally via $actAs in the AppModel
 * (just as with Containable).
 *
 * In case you need to dynamically set the Model->scopes attribute, use the constructor:
 *
 *   public function __construct($id = false, $table = null, $ds = null) {
 *     $this->scopes = ...
 *     parent::__construct($id, $table, $ds);
 *   }
 *
 * The order is important since behaviors are loaded in the parent constructor.
 *
 * Note that it can be vital to use the model prefixes in the conditions and in the scopes
 * to avoid SQL errors or naming conflicts.
 *
 * See the test cases for more complex examples.
 *
 * @license MIT
 * @author Mark Scherer
 * @link https://github.com/dereuromark/tools/wiki/Model-Behavior-NamedScope
 */
class NamedScopeBehavior extends ModelBehavior {

	protected $_defaults = array(
		'scope' => array(), // Container to hold all scopes
		'attribute' => 'scopes', // Model attribute to hold the custom scopes
		'findAttribute' => 'scopedFinds' // Model attribute to hold the custom finds
	);

	/**
	 * Sets up the behavior including settings (i.e. scope).
	 *
	 * @param Model $Model
	 * @param array $settings
	 * @return void
	 */
	public function setup(Model $Model, $settings = array()) {
		$attribute = !empty($settings['attribute']) ? $settings['attribute'] : $this->_defaults['attribute'];
		if (!empty($Model->$attribute)) {
			$settings['scope'] = !empty($settings['scope']) ? array_merge($Model->$attribute, $settings['scope']) : $Model->$attribute;
		}
		$this->settings[$Model->alias] = $settings + $this->_defaults;
	}

	/**
	 * Triggered before the actual find.
	 *
	 * @param Model $Model
	 * @param array $queryData
	 * @return mixed
	 */
	public function beforeFind(Model $Model, $queryData) {
		$scopes = array();
		// Passed as scopes (preferred)
		if (!empty($queryData['scope'])) {
			$scope = !is_array($queryData['scope']) ? array($queryData['scope']) : $queryData['scope'];
			$scopes = array_merge($scopes, $scope);
		}
		// Passed as conditions['scope']
		if (is_array($queryData['conditions']) && !empty($queryData['conditions']['scope'])) {
			$scope = !is_array($queryData['conditions']['scope']) ? array($queryData['conditions']['scope']) : $queryData['conditions']['scope'];
			unset($queryData['conditions']['scope']);
			$scopes = array_merge($scopes, $scope);
		}

		// If there are scopes defined, we need to get rid of possible condition set earlier by find() method if model->id was set
		if (!empty($scopes) && !empty($Model->id) && !empty($queryData['conditions']["`{$Model->alias}`.`{$Model->primaryKey}`"]) &&
			$queryData['conditions']["`{$Model->alias}`.`{$Model->primaryKey}`"] == $Model->id) {
			unset($queryData['conditions']["`{$Model->alias}`.`{$Model->primaryKey}`"]);
		}

		$queryData['conditions'][] = $this->_conditions($scopes, $Model->alias);
		return $queryData;
	}

	/**
	 * Set/get scopes.
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
		if ($value === null) {
			return isset($this->settings[$Model->alias]['scope'][$name]) ? $this->settings[$Model->alias]['scope'][$name] : null;
		}
		$this->settings[$Model->alias]['scope'][$name] = $value;
	}

	/**
	 * Scoped find() with a specific key.
	 *
	 * If you need to switch the type, use the customConfig:
	 *   array('type' => 'count')
	 * All active find methods are supported.
	 *
	 * @param mixed $Model
	 * @param mixed $key
	 * @param array $customConfig
	 * @return mixed
	 * @throws RuntimeException On invalid configs.
	 */
	public function scopedFind(Model $Model, $key, array $customConfig = array()) {
		$attribute = $this->settings[$Model->alias]['findAttribute'];
		if (empty($Model->$attribute)) {
			throw new RuntimeException('No scopedFinds configs in ' . $Model->alias);
		}
		$finds = $Model->$attribute;
		if (empty($finds[$key])) {
			throw new RuntimeException('No scopedFinds configs in ' . $Model->alias . ' for the key ' . $key);
		}

		$config = $finds[$key];
		$config['find'] = array_merge_recursive($config['find'], $customConfig);
		if (!isset($config['find']['type'])) {
			$config['find']['type'] = 'all';
		}

		if (!empty($config['find']['virtualFields'])) {
			$Model->virtualFields = $config['find']['virtualFields'] + $Model->virtualFields;
		}

		if (!empty($config['find']['options']['contain']) && !$Model->Behaviors->loaded('Containable')) {
			$Model->Behaviors->load('Containable');
		}

		return $Model->find($config['find']['type'], $config['find']['options']);
	}

	/**
	 * List all scoped find groups available.
	 *
	 * @param Model $Model
	 * @return array
	 */
	public function scopedFinds(Model $Model) {
		$attribute = $this->settings[$Model->alias]['findAttribute'];
		if (empty($Model->$attribute)) {
			return array();
		}

		$data = array();
		foreach ($Model->$attribute as $group => $config) {
			$data[$group] = $config['name'];
		}

		return $data;
	}

	/**
	 * Resolves the scope names into their conditions.
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
