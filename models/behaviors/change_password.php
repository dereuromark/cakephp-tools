<?php

/**
 * Copyright 2011, Mark Scherer 
 * 
 * Licensed under The MIT License 
 * Redistributions of files must retain the above copyright notice. 
 * 
 * @version    0.1 
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License 
 */

if (!defined('PWD_MIN_LENGTH')) {
	define('PWD_MIN_LENGTH', 3);
}
if (!defined('PWD_MAX_LENGTH')) {
	define('PWD_MAX_LENGTH', 20);
}

/**
 * A behavior to change passwords the easy way
 * 
 * usage:
 * $this->User->actsAs('Tools.ChangePassword');
 * as first line in any action where you want to allow the user to change his password
 * also add the two form fields in the form (pwd, pwd_confirm)
 * the rest is cake automagic :) 
 * 
 * TODO: form_field_current
 * 
 * 2011-07-04 ms
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
		'form_field' => 'pwd',
		'form_field_repeat' => 'pwd_repeat',
		'form_field_current' => 'pwd_current',
		'hashType' => null,
		'hashSalt' => true
		//'hash'
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
		
		//TODO
		return true;
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
	 * 2011-07-22 ms
	 */
	public function setup(Model $Model, $config = array()) {
		$this->settings[$Model->alias] = Set::merge($this->_defaultSettings, $config);
		$formField = $this->settings[$Model->alias]['form_field'];
		$formFieldRepeat = $this->settings[$Model->alias]['form_field_repeat'];
		
		# add the validation rules if not already attached
		if (!isset($Model->validate[$formField])) {
			$Model->validate[$formField] = $this->_validationRules[$formField];
		}
		if (!isset($Model->validate[$formFieldRepeat])) {
			$Model->validate[$formFieldRepeat] = $this->_validationRules[$formFieldRepeat];
			$Model->validate[$formFieldRepeat]['validateIdentical']['rule'][1] = $formField;			
		}
	}

	/**
	 * whitelisting
	 * 2011-07-22 ms 
	 */
	function beforeValidate(Model $Model) {
		# add fields to whitelist!
		$whitelist = array($this->settings[$Model->alias]['form_field'], $this->settings[$Model->alias]['form_field_repeat']);
		if ($this->settings[$Model->alias]['current']) {
			$whitelist[] = $this->settings[$Model->alias]['form_field_current'];
		}
		if (!empty($Model->whitelist)) {
			$Model->whitelist = am($Model->whitelist, $whitelist);
		}
		
		return true;
	}


	/**
	 * hashing the password now
	 * 2011-07-22 ms 
	 */
	function beforeSave(Model $Model) {
		$formField = $this->settings[$Model->alias]['form_field'];
		$formFieldRepeat = $this->settings[$Model->alias]['form_field_repeat'];
		
		if (!empty($Model->data[$Model->alias][$formField])) {
			$field = $this->settings[$Model->alias]['field']; 
			$type = $this->settings[$Model->alias]['hashType'];
			$salt = $this->settings[$Model->alias]['hashSalt'];
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