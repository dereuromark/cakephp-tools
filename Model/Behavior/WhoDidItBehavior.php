<?php
/**
 * PHP 5
 *
 * @copyright http://www.4webby.com
 * @author Daniel Vecchiato
 * @author Mark Scherer
 * @author Marc Würth
 * @version 1.3
 * @license http://opensource.org/licenses/mit-license.php MIT
 */

App::uses('AuthComponent', 'Controller/Component');
App::uses('CakeSession', 'Model/Datasource');
App::uses('ModelBehavior', 'Model');

/**
 * WhoDidIt Behavior
 *
 * Handles created_by, modified_by fields for a given Model, if they exist in the Model DB table.
 * It's similar to the created, modified automagic, but it stores the id of the logged in user
 * in the models that have $actsAs = array('WhoDidIt').
 *
 * This is useful to track who created records, and the last user that has changed them.
 *
 * @link http://book.cakephp.org/2.0/en/models/saving-your-data.html#using-created-and-modified
 */
class WhoDidItBehavior extends ModelBehavior {

	/**
	 * Default config for a model that has this behavior attached.
	 *
	 * Setting force_modified to true will have the same effect as overriding the save method as
	 * described in the code example for "Using created and modified" in the Cookbook.
	 *
	 * @var array
	 * @link http://book.cakephp.org/2.0/en/models/saving-your-data.html#using-created-and-modified
	 */
	protected $_defaultConfig = [
		'auth_session' => 'Auth', // Name of Auth session key
		'user_model' => 'User', // Name of the User model (for plugins use PluginName.ModelName)
		'created_by_field' => 'created_by', // Name of the "created_by" field in the model
		'modified_by_field' => 'modified_by', // Name of the "modified_by" field in the model
		'confirmed_by_field' => 'confirmed_by', // Name of the "confirmed by" field in the model
		'auto_bind' => true, // Automatically bind the model to the User model (default true)
		'force_modified' => false // Force update of the "modified" field even if not empty
	];

	/**
	 * Initiate WhoDidIt Behavior.
	 *
	 * Checks if the configured fields are available in the model.
	 * Also binds the User model as association for each available field.
	 *
	 * @param Model $Model The model.
	 * @param array $config Behavior settings you would like to override.
	 * @return void
	 */
	public function setup(Model $Model, $config = []) {
		$config += $this->_defaultConfig;

		$config['has_created_by'] = $Model->hasField($config['created_by_field']);
		$config['has_modified_by'] = $Model->hasField($config['modified_by_field']);
		$config['has_confirmed_by'] = $Model->hasField($config['confirmed_by_field']);

		// Handles model binding to the User model according to the auto_bind settings (default true).
		if ($config['auto_bind']) {
			if ($config['has_created_by']) {
				$commonBelongsTo = [
					'CreatedBy' => [
						'className' => $config['user_model'],
						'foreignKey' => $config['created_by_field']]];
				$Model->bindModel(['belongsTo' => $commonBelongsTo], false);
			}

			if ($config['has_modified_by']) {
				$commonBelongsTo = [
					'ModifiedBy' => [
						'className' => $config['user_model'],
						'foreignKey' => $config['modified_by_field']]];
				$Model->bindModel(['belongsTo' => $commonBelongsTo], false);
			}

			if ($config['has_confirmed_by']) {
				$commonBelongsTo = [
					'ConfirmedBy' => [
						'className' => $config['user_model'],
						'foreignKey' => $config['confirmed_by_field']]];
				$Model->bindModel(['belongsTo' => $commonBelongsTo], false);
			}
		}

		$this->settings[$Model->alias] = $config;
	}

	/**
	 * Before save callback.
	 *
	 * Checks if at least one field is available.
	 * Reads the current user id from the session.
	 * If a user id is set it will fill...
	 * ... the created_by field only when creating a record
	 * ... the modified by field only if it is not in the data array
	 * or the "force_modified" setting is set to true.
	 *
	 * @param Model $Model The Model using this behavior
	 * @param array $options Options passed from Model::save(), unused.
	 * @return mixed False if the operation should abort. Any other result will continue.
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function beforeSave(Model $Model, $options = []) {
		$config = $this->settings[$Model->alias];
		if (!$config['has_created_by'] && !$config['has_modified_by']) {
			return true;
		}

		$authSession = $config['auth_session'];
		list(, $userSession) = pluginSplit($config['user_model']);

		$userId = AuthComponent::user('id');
		if (empty($userId)) {
			$userId = CakeSession::read($authSession . '.' . $userSession . '.id');
		}

		if (!$userId) {
			return true;
		}

		$data = [];
		$modifiedByField = $config['modified_by_field'];

		if (!isset($Model->data[$Model->alias][$modifiedByField]) || $config['force_modified']) {
			$data[$config['modified_by_field']] = $userId;
		}

		if (!$Model->exists()) {
			$data[$config['created_by_field']] = $userId;
		}
		if ($data) {
			$Model->set($data);
		}
		return true;
	}

}
