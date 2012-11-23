<?php
App::uses('ModelBehavior', 'Model');
App::uses('CakeResponse', 'Network');
App::uses('Security', 'Utility');

if (!defined('PWD_MIN_LENGTH')) {
	define('PWD_MIN_LENGTH', 3);
}
if (!defined('PWD_MAX_LENGTH')) {
	define('PWD_MAX_LENGTH', 20);
}

/**
 * A cakephp2 behavior to work with passwords the easy way
 * - complete validation
 * - hashing of password
 * - requires fields (no tempering even without security component)
 * - usable for edit forms (allowEmpty=>true for optional password update)
 *
 * usage: do NOT add it via $actAs = array()
 * attach it dynamically in only those actions where you actually change the password like so:
 * $this->User->Behaviors->load('Tools.Passwordable', array(SETTINGSARRAY));
 * as first line in any action where you want to allow the user to change his password
 * also add the two form fields in the form (pwd, pwd_confirm)
 * the rest is cake automagic :)
 *
 * now also is capable of:
 * - require current password prior to altering it (current=>true)
 * - don't allow the same password it was before (allowSame=>false)
 *
 * TODO: allowEmpty and nonEmptyToEmpty - maybe with checkbox "set_new_pwd"
 * feel free to help me out
 *
 * @version 1.6 (renamed from ChangePassword to Passwordable)
 * @author Mark Scherer
 * @link http://www.dereuromark.de/2011/08/25/working-with-passwords-in-cakephp
 * @license MIT
 * 2012-08-18 ms
 */
class PasswordableBehavior extends ModelBehavior {

	/**
	 * @access public
	 */
	public $settings = array();

	/**
	 * @access protected
	 */
	protected $_defaultSettings = array(
		'field' => 'password',
		'confirm' => true, # set to false if in admin view and no confirmation (pwd_repeat) is required
		'allowEmpty' => false, # if password must be provided or be changed (set to true for update sites)
		'current' => false, # expect the current password for security purposes
		'formField' => 'pwd',
		'formFieldRepeat' => 'pwd_repeat',
		'formFieldCurrent' => 'pwd_current',
		'hashType' => null,
		'hashSalt' => true,
		'auth' => null, # which component (defaults to AuthComponent),
		'allowSame' => true, # dont allow the old password on change
	);

	/**
	 * @access protected
	 */
	protected $_validationRules = array(
		'formField' => array(
			'between' => array(
				'rule' => array('between', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'message' => array('valErrBetweenCharacters %s %s', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'allowEmpty' => null,
				'last' => true,
			)
		),
		'formFieldRepeat' => array(
			'between' => array(
				'rule' => array('between', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'message' => array('valErrBetweenCharacters %s %s', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'allowEmpty' => null,
				'last' => true,
			),
			'validateIdentical' => array(
				'rule' => array('validateIdentical', 'formField'),
				'message' => 'valErrPwdNotMatch',
				'allowEmpty' => null,
				'last' => true,
			),
		),
		'formFieldCurrent' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrProvideCurrentPwd',
				'allowEmpty' => null,
				'last' => true,
			),
			'validateCurrentPwd' => array(
				'rule' => 'validateCurrentPwd',
				'message' => 'valErrCurrentPwdIncorrect',
				'allowEmpty' => null,
				'last' => true,
			)
		),
	);

	/**
	 * if not implemented in AppModel
	 * @throws CakeException
	 * @return bool $success
	 * 2011-07-22 ms
	 */
	public function validateCurrentPwd(Model $Model, $data) {
		if (is_array($data)) {
			$pwd = array_shift($data);
		} else {
			$pwd = $data;
		}

		$uid = null;
		if ($Model->id) {
			$uid = $Model->id;
		} elseif (!empty($Model->data[$Model->alias]['id'])) {
			$uid = $Model->data[$Model->alias]['id'];
		} else {
			trigger_error('No user id given');
			return false;
		}
		if (empty($this->settings[$Model->alias]['auth']) && class_exists('AuthExtComponent')) {
			$this->Auth = new AuthExtComponent(new ComponentCollection());
		} elseif (class_exists(($this->settings[$Model->alias]['auth'] ? $this->settings[$Model->alias]['auth'] : 'Auth') . 'Component')) {
			$auth = $this->settings[$Model->alias]['auth'].'Component';
			$this->Auth = new $auth(new ComponentCollection());
		} else {
			throw new CakeException('No validation class found');
		}
		# easiest authenticate method via form and (id + pwd)
		$this->Auth->authenticate = array(
			'Form' => array(
				'fields'=>array('username' => 'id', 'password'=>$this->settings[$Model->alias]['field'])
			)
		);
		$request = new CakeRequest(null, false);
		$request->data['User'] = array('id'=>$uid, 'password'=>$pwd);
		$response = new CakeResponse();
		return (bool)$this->Auth->identify($request, $response);
	}

	/**
	 * if not implemented in AppModel
	 * @return bool $success
	 * 2011-07-22 ms
	 */
	public function validateIdentical(Model $Model, $data, $compareWith = null) {
		if (is_array($data)) {
			$value = array_shift($data);
		} else {
			$value = $data;
		}
		$compareValue = $Model->data[$Model->alias][$compareWith];
		return ($compareValue === $value);
	}

	/**
	 * if not implemented in AppModel
	 * @return bool $success
	 * 2011-11-10 ms
	 */
	public function validateNotSame(Model $Model, $data, $field1, $field2) {
		$value1 = $Model->data[$Model->alias][$field1];
		$value2 = $Model->data[$Model->alias][$field2];
		return ($value1 !== $value2);
	}

	/**
	 * if not implemented in AppModel
	 * @return bool $success
	 * 2011-11-10 ms
	 */
	public function validateNotSameHash(Model $Model, $data, $formField) {
		$field = $this->settings[$Model->alias]['field'];
		$type = $this->settings[$Model->alias]['hashType'];
		$salt = $this->settings[$Model->alias]['hashSalt'];

		if (!isset($Model->data[$Model->alias][$Model->primaryKey])) {
			return true;
		}
		$primaryKey = $Model->data[$Model->alias][$Model->primaryKey];
		$value = Security::hash($Model->data[$Model->alias][$formField], $type, $salt);
		$dbValue = $Model->field($field, array($Model->primaryKey => $primaryKey));
		if (!$dbValue) {
			return true;
		}
		return ($value !== $dbValue);
	}

	/**
	 * adding validation rules
	 * also adds and merges config settings (direct + configure)
	 * @return void
	 * 2011-08-24 ms
	 */
	public function setup(Model $Model, $config = array()) {
		$defaults = $this->_defaultSettings;
		if ($configureDefaults = Configure::read('Passwordable')) {
			$defaults = Set::merge($defaults, $configureDefaults);
		}
		$this->settings[$Model->alias] = Set::merge($defaults, $config);

		$formField = $this->settings[$Model->alias]['formField'];
		$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
		$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];

		$rules = $this->_validationRules;

		# add the validation rules if not already attached
		if (!isset($Model->validate[$formField])) {
			$Model->validate[$formField] = $rules['formField'];
		}
		if (!isset($Model->validate[$formFieldRepeat])) {
			$Model->validate[$formFieldRepeat] = $rules['formFieldRepeat'];
			$Model->validate[$formFieldRepeat]['validateIdentical']['rule'][1] = $formField;
		}

		if ($this->settings[$Model->alias]['current'] && !isset($Model->validate[$formFieldCurrent])) {
			$Model->validate[$formFieldCurrent] = $rules['formFieldCurrent'];

			if (!$this->settings[$Model->alias]['allowSame']) {
				$Model->validate[$formField]['validateNotSame'] = array(
					'rule' => array('validateNotSame', $formField, $formFieldCurrent),
					'message' => 'valErrPwdSameAsBefore',
					'allowEmpty' => $this->settings[$Model->alias]['allowEmpty'],
					'last' => true,
				);
			}
		} elseif (!isset($Model->validate[$formFieldCurrent])) {
			# try to match the password against the hash in the DB
			if (!$this->settings[$Model->alias]['allowSame']) {
				$Model->validate[$formField]['validateNotSame'] = array(
					'rule' => array('validateNotSameHash', $formField),
					'message' => 'valErrPwdSameAsBefore',
					'allowEmpty' => $this->settings[$Model->alias]['allowEmpty'],
					'last' => true,
				);
			}
		}
	}

	/**
	 * whitelisting
	 *
	 * @todo currently there is a cake core bug that can break functionality here
	 * (see http://cakephp.lighthouseapp.com/projects/42648/tickets/3071-behavior-validation-methods-broken for details)
	 * @return bool $success
	 * 2011-07-22 ms
	 */
	public function beforeValidate(Model $Model) {
		$formField = $this->settings[$Model->alias]['formField'];
		$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
		$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];

		# make sure fields are set and validation rules are triggered - prevents tempering of form data
		if (!isset($Model->data[$Model->alias][$formField])) {
			$Model->data[$Model->alias][$formField] = '';
		}
		if ($this->settings[$Model->alias]['confirm'] && !isset($Model->data[$Model->alias][$formFieldRepeat])) {
			$Model->data[$Model->alias][$formFieldRepeat] = '';
		}
		if ($this->settings[$Model->alias]['current'] && !isset($Model->data[$Model->alias][$formFieldCurrent])) {
			$Model->data[$Model->alias][$formFieldCurrent] = '';
		}

		# check if we need to trigger any validation rules
		if ($this->settings[$Model->alias]['allowEmpty']) {
			$current = !empty($Model->data[$Model->alias][$formFieldCurrent]);
			$new = !empty($Model->data[$Model->alias][$formField]) || !empty($Model->data[$Model->alias][$formFieldRepeat]);
			if (!$new && !$current) {
				//$Model->validator()->remove($formField); // tmp only!
				//unset($Model->validate[$formField]);
				unset($Model->data[$Model->alias][$formField]);
				if ($this->settings[$Model->alias]['confirm']) {
					//$Model->validator()->remove($formFieldRepeat); // tmp only!
					//unset($Model->validate[$formFieldRepeat]);
					unset($Model->data[$Model->alias][$formFieldRepeat]);
				}
				if ($this->settings[$Model->alias]['current']) {
					//$Model->validator()->remove($formFieldCurrent); // tmp only!
					//unset($Model->validate[$formFieldCurrent]);
					unset($Model->data[$Model->alias][$formFieldCurrent]);
				}
				return true;
			}
		}

		# add fields to whitelist!
		$whitelist = array($this->settings[$Model->alias]['formField'], $this->settings[$Model->alias]['formFieldRepeat']);
		if ($this->settings[$Model->alias]['current']) {
			$whitelist[] = $this->settings[$Model->alias]['formFieldCurrent'];
		}
		if (!empty($Model->whitelist)) {
			$Model->whitelist = array_merge($Model->whitelist, $whitelist);
		}

		return true;
	}


	/**
	 * hashing the password now
	 * @return bool $success
	 * 2011-07-22 ms
	 */
	public function beforeSave(Model $Model) {
		$formField = $this->settings[$Model->alias]['formField'];
		$field = $this->settings[$Model->alias]['field'];
		$type = $this->settings[$Model->alias]['hashType'];
		$salt = $this->settings[$Model->alias]['hashSalt'];

		if (isset($Model->data[$Model->alias][$formField])) {
			$Model->data[$Model->alias][$field] = Security::hash($Model->data[$Model->alias][$formField], $type, $salt);
			unset($Model->data[$Model->alias][$formField]);
			if ($this->settings[$Model->alias]['confirm']) {
				$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
				unset($Model->data[$Model->alias][$formFieldRepeat]);
			}
			if ($this->settings[$Model->alias]['current']) {
				$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];
				unset($Model->data[$Model->alias][$formFieldCurrent]);
			}
			# update whitelist
			if (!empty($Model->whitelist)) {
				$Model->whitelist = array_merge($Model->whitelist, array($field));
			}
		}

		return true;
	}

}