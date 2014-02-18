<?php
App::uses('ModelBehavior', 'Model');

/**
 * MasterPassword Behavior for admin views
 *
 * Uses
 * - Tools.Hash Shell to hash password
 * - master_password element of Tools plugin for Form input
 *
 * Usage:
 * In the controller:
 * $this->ModelName->Behaviors->load('Tools.MasterPassword');
 * In the view:
 * echo $this->element('master_password', array(), array('plugin'=>'tools'));
 * Put this into your private configs:
 * Configure::write('MasterPassword.password', 'your_hashed_pwd_string');
 * You can also use an array to store multiple passwords
 *
 * Note:
 * sha1 is the default hashing algorithm
 *
 * Use Configure::write('MasterPassword.password', false) to deactivate
 *
 * Copyright 2011, dereuromark (http://www.dereuromark.de)
 *
 * @author Mark Scherer
 * @link http://github.com/dereuromark/
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 */
class MasterPasswordBehavior extends ModelBehavior {

	protected $_defaults = array(
		'message' => 'Incorrect Master Password',
		'field' => 'master_pwd',
		'model' => null,
		'before' => 'validate',
		'hash' => 'sha1',
		'salt' => false, //TODO: maybe allow to use core salt for additional security?
		'log' => false //TODO: log the usage of pwds to a log file `master_password`
	);

	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaults;
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], is_array($settings) ? $settings : array());
		// deactivate dynamically
		if (Configure::read('MasterPassword.password') === false) {
			$this->settings[$Model->alias]['before'] = '';
		}
	}

	public function beforeValidate(Model $Model, $options = array()) {
		$return = parent::beforeValidate($Model, $options);

		if ($this->settings[$Model->alias]['before'] === 'validate') {
			// we dont want to return the value, because other fields might then not be validated
			// (save will not continue with errors, anyway)
			$this->confirm($Model, $return);
		}

		return $return;
	}

	public function beforeSave(Model $Model, $options = array()) {
		$return = parent::beforeSave($Model, $options);

		if ($this->settings[$Model->alias]['before'] === 'save') {
			return $this->confirm($Model, $return);
		}

		return $return;
	}

	/**
	 * Run before a model is saved, used...
	 *
	 * @param object $Model Model about to be saved.
	 * @return boolean true if save should proceed, false otherwise
	 */
	public function confirm(Model $Model, $return = true) {
		$field = $this->settings[$Model->alias]['field'];
		$message = $this->settings[$Model->alias]['message'];

		if (!$this->isAuthorized($Model, $field)) {
				$Model->invalidate($field, $message);
				return false;
		}

		return $return;
	}

	/**
	 * Checks a string against the stored hash values of master passwords
	 *
	 * @param string $pwd: plain password string (not hashed etc)
	 * @return boolean Success
	 */
	public function isAuthorized(Model $Model, $field) {
		if (empty($Model->data[$Model->alias][$field])) {
			return false;
		}
		$masterPwds = (array)Configure::read('MasterPassword.password');
		$pwd = $this->_hash($Model->data[$Model->alias][$field], $this->settings[$Model->alias]['hash'], $this->settings[$Model->alias]['salt']);
		foreach ($masterPwds as $masterPwd) {
			if ($masterPwd === $pwd) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return string hash or FALSE on failure
	 */
	protected function _hash($string, $algorithm, $salt) {
		if ($salt) {
			if (is_string($salt)) {
				$string = $salt . $string;
			} else {
				$string = Configure::read('Security.salt') . $string;
			}
		}
		if ($algorithm === 'sha1') {
			return sha1($string);
		}
		if ($algorithm === 'md5') {
			return md5($string);
		}
		// mcrypt installed?
		if (function_exists('hash') && in_array($algorithm, hash_algos())) {
			return hash($algorithm, $string);
		}
		trigger_error('Hash method not available');
		return false;
	}

}
