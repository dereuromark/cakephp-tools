<?php

/**
 * Copyright 2011, Mark Scherer 
 * 
 * Licensed under The MIT License 
 * Redistributions of files must retain the above copyright notice. 
 * 
 * @version    1.1 
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License 
 */

if (!defined('PWD_MIN_LENGTH')) {
	define('PWD_MIN_LENGTH', 3);
}
if (!defined('PWD_MAX_LENGTH')) {
	define('PWD_MAX_LENGTH', 20);
}

/**
 * A cakephp1.3 behavior to change passwords the easy way
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
 * TODO: allowEmpty and nonEmptyToEmpty - maybe with checkbox "set_new_pwd"
 * TODO: test cases
 * feel free to help me out
 * 
 * 2011-08-24 ms
 */
class ChangePasswordBehavior extends ModelBehavior {

	var $settings = array();

	/**
	 * @access protected
	 */
	var $_defaultSettings = array(
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
		'nonEmptyToEmpty' => false, # allow resetting nonempty pwds to empty once set (prevents problems with default edit actions)
	);
	
	var $_validationRules = array(
		'pwd' => array(
			'between' => array(
				'rule' => array('between', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'message' => array('valErrBetweenCharacters %s %s', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
			)
		),
		'pwd_repeat' => array(
			'between' => array(
				'rule' => array('between', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'message' => array('valErrBetweenCharacters %s %s', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
			),
			'validateIdentical' => array(
				'rule' => array('validateIdentical', 'pwd'),
				'message' => 'valErrPwdNotMatch',

			),
		),
		'pwd_current' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrProvideCurrentPwd',
			),
			'validateCurrentPwd' => array(
				'rule' => 'validateCurrentPwd',
				'message' => 'valErrCurrentPwdIncorrect',
			)
		),
	);

	/**
	 * if not implemented in app_model
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
			return false;
		}
		
		if (class_exists('AuthExtComponent')) {
			$this->Auth = new AuthExtComponent();
		} elseif (class_exists($this->settings[$Model->alias]['auth'].'Component')) {
			$auth = $this->settings[$Model->alias]['auth'].'Component';
			$this->Auth = new $auth();
		} else {
			return true;
		}
		return $this->Auth->verifyUser($uid, $pwd);
	}
	
	/**
	 * if not implemented in app_model
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
			$Model->validate[$formField] = $this->_validationRules[$formField];
		}
		if (!isset($Model->validate[$formFieldRepeat])) {
			$Model->validate[$formFieldRepeat] = $this->_validationRules[$formFieldRepeat];
			$Model->validate[$formFieldRepeat]['validateIdentical']['rule'][1] = $formField;			
		}
		if ($this->settings[$Model->alias]['current'] && !isset($Model->validate[$formFieldCurrent])) {
			$Model->validate[$formFieldCurrent] = $this->_validationRules[$formFieldCurrent];			
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
	function beforeValidate(Model $Model) {
		# add fields to whitelist!
		$whitelist = array($this->settings[$Model->alias]['formField'], $this->settings[$Model->alias]['formFieldRepeat']);
		if ($this->settings[$Model->alias]['current']) {
			$whitelist[] = $this->settings[$Model->alias]['formFieldCurrent'];
		}
		if (!empty($Model->whitelist)) {
			$Model->whitelist = am($Model->whitelist, $whitelist);
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
	function beforeSave(Model $Model) {
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
				$Model->whitelist = am($Model->whitelist, array($field));
			}
		}
		
		return true;
	}

	
}