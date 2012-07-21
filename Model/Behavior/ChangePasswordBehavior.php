<?php
App::uses('ModelBehavior', 'Model');
App::uses('CakeResponse', 'Network');
App::uses('Security', 'Utility');

/**
 * Copyright 2011, Mark Scherer
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.4
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 */

if (!defined('PWD_MIN_LENGTH')) {
	define('PWD_MIN_LENGTH', 3);
}
if (!defined('PWD_MAX_LENGTH')) {
	define('PWD_MAX_LENGTH', 20);
}

/**
 * A cakephp2 behavior to change passwords the easy way
 * - complete validation
 * - hashing of password
 * - requires fields (no tempering even without security component)
 *
 * usage: do NOT add it via $actAs = array()
 * attach it dynamically in only those actions where you actually change the password like so:
 * $this->User->Behaviors->attach('Tools.ChangePassword', array(SETTINGSARRAY));
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
 * 2011-08-24 ms
 */
class ChangePasswordBehavior extends ModelBehavior {

	public $settings = array();

	/**
	 * @access protected
	 */
	public $_defaultSettings = array(
		'field' => 'password',
		'confirm' => true, # set to false if in admin view and no confirmation (pwd_repeat) is required
		'allowEmpty' => false,
		'current' => false, # expect the current password for security purposes
		'formField' => 'pwd',
		'formFieldRepeat' => 'pwd_repeat',
		'formFieldCurrent' => 'pwd_current',
		'hashType' => null,
		'hashSalt' => true,
		'auth' => 'Auth', # which component,
		'allowSame' => true, # dont allow the old password on change
		'nonEmptyToEmpty' => false, # allow resetting nonempty pwds to empty once set (prevents problems with default edit actions)
	);

	public $_validationRules = array(
		'formField' => array(
			'between' => array(
				'rule' => array('between', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'message' => array('valErrBetweenCharacters %s %s', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'last' => true,
			)
		),
		'formFieldRepeat' => array(
			'between' => array(
				'rule' => array('between', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'message' => array('valErrBetweenCharacters %s %s', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'last' => true,
			),
			'validateIdentical' => array(
				'rule' => array('validateIdentical', 'formField'),
				'message' => 'valErrPwdNotMatch',
				'last' => true,
			),
		),
		'formFieldCurrent' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrProvideCurrentPwd',
				'last' => true,
			),
			'validateCurrentPwd' => array(
				'rule' => 'validateCurrentPwd',
				'message' => 'valErrCurrentPwdIncorrect',
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
		if (class_exists('AuthExtComponent')) {
			$this->Auth = new AuthExtComponent(new ComponentCollection());
		} elseif (class_exists($this->settings[$Model->alias]['auth'].'Component')) {
			$auth = $this->settings[$Model->alias]['auth'].'Component';
			$this->Auth = new $auth(new ComponentCollection());
		} else {
			throw new CakeException('No validation class found');
		}
		# easiest authenticate method via form and (id + pwd)
		$this->Auth->authenticate = array('Form'=>array('fields'=>array('username' => 'id', 'password'=>$this->settings[$Model->alias]['field'])));

		$request = new CakeRequest(null, false);
		$request->data['User'] = array('id'=>$uid, 'password'=>$pwd);
		$response = new CakeResponse();
		return $this->Auth->identify($request, $response);
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
		return ($compareValue == $value);
	}

	/**
	 * if not implemented in AppModel
	 * @return bool $success
	 * 2011-11-10 ms
	 */
	public function validateNotSame(Model $Model, $data, $field1, $field2) {
		$value1 = $Model->data[$Model->alias][$field1];
		$value2 = $Model->data[$Model->alias][$field2];
		return ($value1 != $value2);
	}

	/**
	 * adding validation rules
	 * also adds and merges config settings (direct + configure)
	 * 2011-08-24 ms
	 */
	public function setup(Model $Model, $config = array()) {
		$defaults = $this->_defaultSettings;
		if ($configureDefaults = Configure::read('ChangePassword')) {
			$defaults = Set::merge($defaults, $configureDefaults);
		}
		$this->settings[$Model->alias] = Set::merge($defaults, $config);

		$formField = $this->settings[$Model->alias]['formField'];
		$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
		$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];

		# add the validation rules if not already attached
		if (!isset($Model->validate[$formField])) {
			$Model->validate[$formField] = $this->_validationRules['formField'];
		}
		if (!isset($Model->validate[$formFieldRepeat])) {
			$Model->validate[$formFieldRepeat] = $this->_validationRules['formFieldRepeat'];
			$Model->validate[$formFieldRepeat]['validateIdentical']['rule'][1] = $formField;
		}

		if ($this->settings[$Model->alias]['current'] && !isset($Model->validate[$formFieldCurrent])) {
			$Model->validate[$formFieldCurrent] = $this->_validationRules['formFieldCurrent'];

			if (!$this->settings[$Model->alias]['allowSame']) {
				$Model->validate[$formField]['validateNotSame'] = array(
					'rule' => array('validateNotSame', $formField, $formFieldCurrent),
					'message' => 'valErrPwdSameAsBefore',
					'last' => true,
				);
			}
		}

		# allowEmpty?
		if (!empty($this->settings[$Model->alias]['allowEmpty'])) {
			$Model->validate[$formField]['between']['rule'][1] = 0;
		}
	}

	/**
	 * whitelisting
	 * 2011-07-22 ms
	 */
	public function beforeValidate(Model $Model) {
		# add fields to whitelist!
		$whitelist = array($this->settings[$Model->alias]['formField'], $this->settings[$Model->alias]['formFieldRepeat']);
		if ($this->settings[$Model->alias]['current']) {
			$whitelist[] = $this->settings[$Model->alias]['formFieldCurrent'];
		}
		if (!empty($Model->whitelist)) {
			$Model->whitelist = array_merge($Model->whitelist, $whitelist);
		}

		# make sure fields are set and validation rules are triggered - prevents tempering of form data
		$formField = $this->settings[$Model->alias]['formField'];
		$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
		$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];
		if (!isset($Model->data[$Model->alias][$formField])) {
			$Model->data[$Model->alias][$formField] = '';
		}
		if ($this->settings[$Model->alias]['confirm'] && !isset($Model->data[$Model->alias][$formFieldRepeat])) {
			$Model->data[$Model->alias][$formFieldRepeat] = '';
		}
		if ($this->settings[$Model->alias]['current'] && !isset($Model->data[$Model->alias][$formFieldCurrent])) {
			$Model->data[$Model->alias][$formFieldCurrent] = '';
		}

		return true;
	}


	/**
	 * hashing the password now
	 * 2011-07-22 ms
	 */
	public function beforeSave(Model $Model) {
		$formField = $this->settings[$Model->alias]['formField'];
		$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
		$field = $this->settings[$Model->alias]['field'];
		$type = $this->settings[$Model->alias]['hashType'];
		$salt = $this->settings[$Model->alias]['hashSalt'];

		if (empty($Model->data[$Model->alias][$formField]) && !$this->settings[$Model->alias]['nonEmptyToEmpty']) {
			# is edit? previous password was "notEmpty"?
			if (!empty($Model->data[$Model->alias][$Model->primaryKey]) && ($oldPwd = $Model->field($field, array($Model->alias.'.id'=>$Model->data[$Model->alias][$Model->primaryKey]))) && $oldPwd != Security::hash('', $type, $salt)) {
				unset($Model->data[$Model->alias][$formField]);
			}
		}

		if (isset($Model->data[$Model->alias][$formField])) {
			$Model->data[$Model->alias][$field] = Security::hash($Model->data[$Model->alias][$formField], $type, $salt);
			unset($Model->data[$Model->alias][$formField]);
			if ($this->settings[$Model->alias]['confirm']) {
				unset($Model->data[$Model->alias][$formFieldRepeat]);
			}
			# update whitelist
			if (!empty($Model->whitelist)) {
				$Model->whitelist = array_merge($Model->whitelist, array($field));
			}
		}

		return true;
	}


}