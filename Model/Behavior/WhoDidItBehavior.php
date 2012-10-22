<?php
App::uses('CakeSession', 'Model/Datasource');
App::uses('ModelBehavior', 'Model');

/**
 * WhoDidIt Model Behavior for CakePHP
 *
 * Handles created_by, modified_by fields for a given Model, if they exist in the Model DB table.
 * It's similar to the created, modified automagic, but it stores the logged User id
 * in the models that actsAs = array('WhoDidIt')
 *
 * This is useful to track who created records, and the last user that has changed them
 *
 * @package behaviors
 * @author Daniel Vecchiato
 * @version 1.2
 * @date 01/03/2009
 * @copyright http://www.4webby.com
 * @licence MIT
 * @repository https://github.com/danfreak/4cakephp/tree
 *
 * enhanced/updated - 2011-07-18 ms
 **/
class WhoDidItBehavior extends ModelBehavior {

	/**
	 * Default settings for a model that has this behavior attached.
	 *
	 * @var array
	 * @access protected
	 */
	protected $_defaults = array(
		'auth_session' => 'Auth', //name of Auth session key
		'user_model' => 'User', //name of User model
		'created_by_field' => 'created_by', //the name of the "created_by" field in DB (default 'created_by')
		'modified_by_field' => 'modified_by', //the name of the "modified_by" field in DB (default 'modified_by')
		'confirmed_by_field' => 'confirmed_by',
		'auto_bind' => true //automatically bind the model to the User model (default true)
	);

	/**
	 * Initiate WhoDidIt Behavior
	 *
	 * @param object $Model
	 * @param array $config behavior settings you would like to override
	 * @return void
	 * @access public
	 */
	public function setup(Model $Model, $config = array()) {
		//assign default settings
		$this->settings[$Model->alias] = $this->_defaults;

		//merge custom config with default settings
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$config);

		$hasFieldCreatedBy = $Model->hasField($this->settings[$Model->alias]['created_by_field']);
		$hasFieldModifiedBy = $Model->hasField($this->settings[$Model->alias]['modified_by_field']);
		$hasFieldConfirmedBy = $Model->hasField($this->settings[$Model->alias]['confirmed_by_field']);

		$this->settings[$Model->alias]['has_created_by'] = $hasFieldCreatedBy;
		$this->settings[$Model->alias]['has_modified_by'] = $hasFieldModifiedBy;
		$this->settings[$Model->alias]['has_confirmed_by'] = $hasFieldConfirmedBy;

		//handles model binding to the User model
		//according to the auto_bind settings (default true)
		if ($this->settings[$Model->alias]['auto_bind']) {
			if ($hasFieldCreatedBy) {
				$commonBelongsTo = array('CreatedBy' => array('className' => $this->settings[$Model->alias]['user_model'], 'foreignKey' => $this->settings[$Model->
					alias]['created_by_field']));
				$Model->bindModel(array('belongsTo' => $commonBelongsTo), false);
			}

			if ($hasFieldModifiedBy) {
				$commonBelongsTo = array('ModifiedBy' => array('className' => $this->settings[$Model->alias]['user_model'], 'foreignKey' => $this->settings[$Model->
					alias]['modified_by_field']));
				$Model->bindModel(array('belongsTo' => $commonBelongsTo), false);
			}

			if ($hasFieldConfirmedBy) {
				$commonBelongsTo = array('ConfirmedBy' => array('className' => $this->settings[$Model->alias]['user_model'], 'foreignKey' => $this->settings[$Model->
					alias]['confirmed_by_field']));
				$Model->bindModel(array('belongsTo' => $commonBelongsTo), false);
			}
		}
	}

	/**
	 * Before save callback
	 *
	 * @param object $Model Model using this behavior
	 * @return boolean True if the operation should continue, false if it should abort
	 * @access public
	 */
	public function beforeSave(Model $Model) {
		if ($this->settings[$Model->alias]['has_created_by'] || $this->settings[$Model->alias]['has_modified_by']) {
			$AuthSession = $this->settings[$Model->alias]['auth_session'];
			$UserSession = $this->settings[$Model->alias]['user_model'];

			$userId = CakeSession::read($AuthSession. '.' . $UserSession. '.id');

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
