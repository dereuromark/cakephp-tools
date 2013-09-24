<?php
App::uses('ModelBehavior', 'Model');
App::uses('Router', 'Routing');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Security', 'Utility');

if (!defined('PWD_MIN_LENGTH')) {
	define('PWD_MIN_LENGTH', 3);
}
if (!defined('PWD_MAX_LENGTH')) {
	define('PWD_MAX_LENGTH', 20);
}

/**
 * A CakePHP2 behavior to work with passwords the easy way
 * - complete validation
 * - hashing of password
 * - requires fields (no tempering even without security component)
 * - usable for edit forms (require=>false for optional password update)
 *
 * Usage: Do NOT add it via $actAs = array()
 * attach it dynamically in only those actions where you actually change the password like so:
 * $this->User->Behaviors->load('Tools.Passwordable', array(SETTINGSARRAY));
 * as first line in any action where you want to allow the user to change his password
 * also add the two form fields in the form (pwd, pwd_confirm)
 * the rest is cake automagic :)
 *
 * Now also is capable of:
 * - require current password prior to altering it (current=>true)
 * - don't allow the same password it was before (allowSame=>false)
 * - supporting different auth types and password hashing algorythms
 *
 * @version 1.7 (Now CakePHP2.4/2.5 ready - with passwordHasher support)
 * @author Mark Scherer
 * @link http://www.dereuromark.de/2011/08/25/working-with-passwords-in-cakephp
 * @license MIT
 * 2012-08-18 ms
 */
class PasswordableBehavior extends ModelBehavior {

	/**
	 * @var array
	 */
	protected $_defaults = array(
		'field' => 'password',
		'confirm' => true, // Set to false if in admin view and no confirmation (pwd_repeat) is required
		'require' => true, // If a password change is required (set to false for edit forms, leave it true for pure password update forms)
		'allowEmpty' => false, // Deprecated, do NOT use anymore! Use require instead!
		'current' => false, // Enquire the current password for security purposes
		'formField' => 'pwd',
		'formFieldRepeat' => 'pwd_repeat',
		'formFieldCurrent' => 'pwd_current',
		'userModel' => null, // Defaults to User
		'hashType' => null, // Only for authType Form [cake2.3]
		'hashSalt' => true, // Only for authType Form [cake2.3]
		'auth' => null, // Which component (defaults to AuthComponent),
		'authType' => 'Form', // Which type of authenticate (Form, Blowfish, ...) [cake2.4]
		'passwordHasher' => null, // If a custom pwd hasher is been used [cake2.4]
		'allowSame' => true, // Don't allow the old password on change
		'minLength' => PWD_MIN_LENGTH,
		'maxLength' => PWD_MAX_LENGTH
	);

	/**
	 * @var array
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
	 * If not implemented in AppModel
	 *
	 * Note: requires the used Auth component to be App::uses() loaded.
	 * It also reqires the same Auth setup as in your AppController's beforeFilter().
	 * So if you set up any special passwordHasher or auth type, you need to provide those
	 * with the settings passed to the behavior:
	 *
	 * 'authType' => 'Blowfish', 'passwordHasher' => array(
	 *     'className' => 'Simple',
	 *     'hashType' => 'sha256'
	 * )
	 *
	 * @throws CakeException
	 * @param Model $Model
	 * @param array $data
	 * @return boolean Success
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

		$auth = 'Auth';
		if (empty($this->settings[$Model->alias]['auth']) && class_exists('AuthExtComponent')) {
			$auth = 'AuthExt';
		} elseif ($this->settings[$Model->alias]['auth']) {
			$auth = $this->settings[$Model->alias]['auth'];
		}
		$authClass = $auth . 'Component';
		if (!class_exists($authClass)) {
			throw new CakeException('No Authentication class found (' . $authClass. ')');
		}

		$this->Auth = new $authClass(new ComponentCollection());

		# easiest authenticate method via form and (id + pwd)
		$authConfig = array(
			'fields' => array('username' => 'id', 'password' => $this->settings[$Model->alias]['field']),
			'userModel' => $this->settings[$Model->alias]['userModel'] ? $this->settings[$Model->alias]['userModel'] : $Model->alias
		);
		if (!empty($this->settings[$Model->alias]['passwordHasher'])) {
			$authConfig['passwordHasher'] = $this->settings[$Model->alias]['passwordHasher'];
		}
		$this->Auth->authenticate = array(
			$this->settings[$Model->alias]['authType'] => $authConfig
		);
		$request = Router::getRequest();
		$request->data[$Model->alias] = array('id' => $uid, 'password' => $pwd);
		$response = new CakeResponse();
		return (bool)$this->Auth->identify($request, $response);
	}

	/**
	 * if not implemented in AppModel
	 *
	 * @param Model $Model
	 * @param array $data
	 * @param string $compareWith String to compare field value with
	 * @return boolean Success
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
	 *
	 * @return boolean Success
	 * 2011-11-10 ms
	 */
	public function validateNotSame(Model $Model, $data, $field1, $field2) {
		$value1 = $Model->data[$Model->alias][$field1];
		$value2 = $Model->data[$Model->alias][$field2];
		return ($value1 !== $value2);
	}

	/**
	 * if not implemented in AppModel
	 *
	 * @return boolean Success
	 * 2011-11-10 ms
	 */
	public function validateNotSameHash(Model $Model, $data, $formField) {
		$field = $this->settings[$Model->alias]['field'];
		$type = $this->settings[$Model->alias]['hashType'];
		$salt = $this->settings[$Model->alias]['hashSalt'];
		if ($this->settings[$Model->alias]['authType'] === 'Blowfish') {
			$type = 'blowfish';
			$salt = false;
		}
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
	 * Adding validation rules
	 * also adds and merges config settings (direct + configure)
	 *
	 * @return void
	 * 2011-08-24 ms
	 */
	public function setup(Model $Model, $config = array()) {
		$defaults = $this->_defaults;
		if ($configureDefaults = Configure::read('Passwordable')) {
			$defaults = Set::merge($defaults, $configureDefaults);
		}
		$this->settings[$Model->alias] = Set::merge($defaults, $config);

		// BC comp
		if ($this->settings[$Model->alias]['allowEmpty']) {
			$this->settings[$Model->alias]['require'] = false;
		}

		$formField = $this->settings[$Model->alias]['formField'];
		$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
		$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];

		$rules = $this->_validationRules;
		foreach ($rules as $key => $rule) {
			foreach ($rule as $rK => $rR) {
				$rR['allowEmpty'] = !$this->settings[$Model->alias]['require'];

				$rules[$key][$rK] = $rR;
			}
		}

		# add the validation rules if not already attached
		if (!isset($Model->validate[$formField])) {
			$Model->validator()->add($formField, $rules['formField']);
		}
		if (!isset($Model->validate[$formFieldRepeat])) {
			$ruleSet = $rules['formFieldRepeat'];
			$ruleSet['validateIdentical']['rule'][1] = $formField;
			$Model->validator()->add($formFieldRepeat, $ruleSet);
		}

		if ($this->settings[$Model->alias]['current'] && !isset($Model->validate[$formFieldCurrent])) {
			$Model->validator()->add($formFieldCurrent, $rules['formFieldCurrent']);

			if (!$this->settings[$Model->alias]['allowSame']) {
				$Model->validator()->add($formField, 'validateNotSame', array(
					'rule' => array('validateNotSame', $formField, $formFieldCurrent),
					'message' => 'valErrPwdSameAsBefore',
					'allowEmpty' => !$this->settings[$Model->alias]['require'],
					'last' => true,
				));
			}
		} elseif (!isset($Model->validate[$formFieldCurrent])) {
			# try to match the password against the hash in the DB
			if (!$this->settings[$Model->alias]['allowSame']) {
				$Model->validator()->add($formField, 'validateNotSame', array(
					'rule' => array('validateNotSameHash', $formField),
					'message' => 'valErrPwdSameAsBefore',
					'allowEmpty' => !$this->settings[$Model->alias]['require'],
					'last' => true,
				));
			}
		}
	}

	/**
	 * Preparing the data
	 *
	 * @return boolean Success
	 * 2011-07-22 ms
	 */
	public function beforeValidate(Model $Model, $options = array()) {
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
		if (!$this->settings[$Model->alias]['require']) {
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
	 * Hashing the password and whitelisting
	 *
	 * @return boolean Success
	 * 2011-07-22 ms
	 */
	public function beforeSave(Model $Model, $options = array()) {
		$formField = $this->settings[$Model->alias]['formField'];
		$field = $this->settings[$Model->alias]['field'];
		$type = $this->settings[$Model->alias]['hashType'];
		$salt = $this->settings[$Model->alias]['hashSalt'];
		if ($this->settings[$Model->alias]['authType'] === 'Blowfish') {
			$type = 'blowfish';
			$salt = false;
		}

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
