<?php
/**
 * PHP 5
 *
 * @copyright http://www.4webby.com
 * @author Daniel Vecchiato
 * @author Mark Scherer
 * @licence MIT
 */

App::uses('CakeSession', 'Model/Datasource');
App::uses('ModelBehavior', 'Model');

/**
 * WhoDidIt Model Behavior
 *
 * Handles created_by, modified_by fields for a given model, if they exist in the model's
 * table scheme.
 * It's similar to the created, modified automagic, but it stores the id of the logged in
 * user in the models that have $actsAs = array('Tools.WhoDidIt').
 *
 * This is useful to track who created records, and the last user that has changed them.
 *
 * @link http://book.cakephp.org/2.0/en/models/saving-your-data.html#using-created-and-modified
 */
class WhoDidItBehavior extends ModelBehavior {

	/**
	 * Default settings for a model that has this behavior attached.
	 *
	 * @var array
	 */
	protected $_defaults = array(
		'auth_session' => 'Auth', // Name of Auth session key.
		'user_model' => 'User', // Name of User model.
		'created_by_field' => 'created_by', // The name of the "created_by" field in DB.
		'modified_by_field' => 'modified_by', // The name of the "modified_by" field in DB.
		'confirmed_by_field' => 'confirmed_by', // The name of the "confirmed_by" field in DB.
		'auto_bind' => true // Automatically bind the model to the User model.
	);

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
	public function setup(Model $Model, $config = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaults, (array)$config);

		$hasFieldCreatedBy = $Model->hasField($this->settings[$Model->alias]['created_by_field']);
		$hasFieldModifiedBy = $Model->hasField($this->settings[$Model->alias]['modified_by_field']);
		$hasFieldConfirmedBy = $Model->hasField($this->settings[$Model->alias]['confirmed_by_field']);

		$this->settings[$Model->alias]['has_created_by'] = $hasFieldCreatedBy;
		$this->settings[$Model->alias]['has_modified_by'] = $hasFieldModifiedBy;
		$this->settings[$Model->alias]['has_confirmed_by'] = $hasFieldConfirmedBy;

		// Handles model binding to the User model according to the auto_bind settings (default true).
		if ($this->settings[$Model->alias]['auto_bind']) {
			if ($hasFieldCreatedBy) {
				$commonBelongsTo = array(
					'CreatedBy' => array(
						'className' => $this->settings[$Model->alias]['user_model'],
						'foreignKey' => $this->settings[$Model->alias]['created_by_field']));
				$Model->bindModel(array('belongsTo' => $commonBelongsTo), false);
			}

			if ($hasFieldModifiedBy) {
				$commonBelongsTo = array(
					'ModifiedBy' => array(
						'className' => $this->settings[$Model->alias]['user_model'],
						'foreignKey' => $this->settings[$Model->alias]['modified_by_field']));
				$Model->bindModel(array('belongsTo' => $commonBelongsTo), false);
			}

			if ($hasFieldConfirmedBy) {
				$commonBelongsTo = array(
					'ConfirmedBy' => array(
						'className' => $this->settings[$Model->alias]['user_model'],
						'foreignKey' => $this->settings[$Model->alias]['confirmed_by_field']));
				$Model->bindModel(array('belongsTo' => $commonBelongsTo), false);
			}
		}
	}

	/**
	 * Before save callback.
	 *
	 * Checks if at least one field is available.
	 * Reads the current user id from the session.
	 *
	 * @param Model $Model The model using this behavior.
	 * @return boolean True if the operation should continue, false if it should abort.
	 */
	public function beforeSave(Model $Model, $options = array()) {
		if ($this->settings[$Model->alias]['has_created_by'] || $this->settings[$Model->alias]['has_modified_by']) {
			$AuthSession = $this->settings[$Model->alias]['auth_session'];
			$UserSession = $this->settings[$Model->alias]['user_model'];

			$userId = CakeSession::read($AuthSession . '.' . $UserSession . '.id');

			if ($userId) {
				$data = array($this->settings[$Model->alias]['modified_by_field'] => $userId);
				if (!$Model->exists()) {
					$data[$this->settings[$Model->alias]['created_by_field']] = $userId;
				}
				$Model->set($data);
			}
		}
		return true;
	}

}
