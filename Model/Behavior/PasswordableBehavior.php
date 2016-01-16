<?php
App::uses('ModelBehavior', 'Model');
App::uses('Security', 'Utility');
App::uses('PasswordHasherFactory', 'Shim.Controller/Component/Auth');

// @deprecated Use Configure settings instead.
if (!defined('PWD_MIN_LENGTH')) {
	define('PWD_MIN_LENGTH', 6);
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
 * Also note that you can apply global settings via Configure key 'Passwordable', as well,
 * if you don't want to manually pass them along each time you use the behavior. This also
 * keeps the code clean and lean.
 *
 * Now also is capable of:
 * - Require current password prior to altering it (current=>true)
 * - Don't allow the same password it was before (allowSame=>false)
 * - Support different auth types and password hashing algorythms
 * - PasswordHasher support
 * - Tools.Modern PasswordHasher and password_hash()/password_verify() support
 * - Option to use complex validation rule (regex)
 *
 * @version 1.9
 * @author Mark Scherer
 * @link http://www.dereuromark.de/2011/08/25/working-with-passwords-in-cakephp
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class PasswordableBehavior extends ModelBehavior {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'field' => 'password',
		'confirm' => true, // Set to false if in admin view and no confirmation (pwd_repeat) is required
		'require' => true, // If a password change is required (set to false for edit forms, leave it true for pure password update forms)
		'allowEmpty' => false, // Deprecated, do NOT use anymore! Use require instead!
		'current' => false, // Enquire the current password for security purposes
		'formField' => 'pwd',
		'formFieldRepeat' => 'pwd_repeat',
		'formFieldCurrent' => 'pwd_current',
		'userModel' => null, // Defaults to User
		'hashType' => null, // Only for authType Form [Cake2.3]
		'hashSalt' => true, // Only for authType Form [Cake2.3]
		'auth' => null, // Which component (defaults to AuthComponent),
		'authType' => 'Form', // Which type of authenticate (Form, Blowfish, ...) [Cake2.4+]
		'passwordHasher' => null, // If a custom pwd hasher is been used [Cake2.4+]
		'allowSame' => true, // Don't allow the old password on change
		'minLength' => PWD_MIN_LENGTH,
		'maxLength' => PWD_MAX_LENGTH,
		'customValidation' => null // Custom validation rule(s) for the formField
	];

	/**
	 * @var array
	 */
	protected $_validationRules = [];

	/**
	 * Adding validation rules
	 * also adds and merges config settings (direct + configure)
	 *
	 * @return void
	 */
	public function setup(Model $Model, $config = []) {
		$this->_validationRules = [
			'formField' => [
				'between' => [
					'rule' => ['between', PWD_MIN_LENGTH, PWD_MAX_LENGTH],
					'message' => __d('tools', 'valErrBetweenCharacters %s %s', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
					'allowEmpty' => null,
					'last' => true,
				]
			],
			'formFieldRepeat' => [
				'validateNotEmpty' => [
					'rule' => ['notBlank'],
					'message' => __d('tools', 'valErrPwdRepeat'),
					'allowEmpty' => true,
					'last' => true,
				],
				'validateIdentical' => [
					'rule' => ['validateIdentical', 'formField'],
					'message' => __d('tools', 'valErrPwdNotMatch'),
					'allowEmpty' => null,
					'last' => true,
				],
			],
			'formFieldCurrent' => [
				'notBlank' => [
					'rule' => ['notBlank'],
					'message' => __d('tools', 'valErrProvideCurrentPwd'),
					'allowEmpty' => null,
					'last' => true,
				],
				'validateCurrentPwd' => [
					'rule' => 'validateCurrentPwd',
					'message' => __d('tools', 'valErrCurrentPwdIncorrect'),
					'allowEmpty' => null,
					'last' => true,
				]
			]
		];

		$defaults = $this->_defaultConfig;
		if ($configureDefaults = Configure::read('Passwordable')) {
			$defaults = $configureDefaults + $defaults;
		}
		$this->settings[$Model->alias] = $config + $defaults;

		// BC comp
		if ($this->settings[$Model->alias]['allowEmpty']) {
			$this->settings[$Model->alias]['require'] = false;
		}

		$formField = $this->settings[$Model->alias]['formField'];
		$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
		$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];

		if ($formField === $this->settings[$Model->alias]['field']) {
			throw new CakeException('Invalid setup - the form field must to be different from the model field (' . $this->settings[$Model->alias]['field'] . ').');
		}

		$rules = $this->_validationRules;
		foreach ($rules as $field => $fieldRules) {
			foreach ($fieldRules as $key => $rule) {
				$rule['allowEmpty'] = !$this->settings[$Model->alias]['require'];

				if ($key === 'between') {
					$rule['rule'] = ['between', $this->settings[$Model->alias]['minLength'], $this->settings[$Model->alias]['maxLength']];
					$rule['message'] = __d('tools', 'valErrBetweenCharacters %s %s', $this->settings[$Model->alias]['minLength'], $this->settings[$Model->alias]['maxLength']);
				}

				$fieldRules[$key] = $rule;
			}
			$rules[$field] = $fieldRules;
		}

		// Add the validation rules if not already attached
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
				$Model->validator()->add($formField, 'validateNotSame', [
					'rule' => ['validateNotSame', $formField, $formFieldCurrent],
					'message' => __d('tools', 'valErrPwdSameAsBefore'),
					'allowEmpty' => !$this->settings[$Model->alias]['require'],
					'last' => true,
				]);
			}
		} elseif (!isset($Model->validate[$formFieldCurrent])) {
			// Try to match the password against the hash in the DB
			if (!$this->settings[$Model->alias]['allowSame']) {
				$Model->validator()->add($formField, 'validateNotSame', [
					'rule' => ['validateNotSameHash', $formField],
					'message' => __d('tools', 'valErrPwdSameAsBefore'),
					'allowEmpty' => !$this->settings[$Model->alias]['require'],
					'last' => true,
				]);
			}
		}

		// Add custom rule(s) if configured
		if ($this->settings[$Model->alias]['customValidation']) {
			$Model->validator()->add($formField, $this->settings[$Model->alias]['customValidation']);
		}
	}

	/**
	 * Preparing the data
	 *
	 * @return bool Success
	 */
	public function beforeValidate(Model $Model, $options = []) {
		$formField = $this->settings[$Model->alias]['formField'];
		$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
		$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];

		// Make sure fields are set and validation rules are triggered - prevents tempering of form data
		if (!isset($Model->data[$Model->alias][$formField])) {
			$Model->data[$Model->alias][$formField] = '';
		}
		if ($this->settings[$Model->alias]['confirm'] && !isset($Model->data[$Model->alias][$formFieldRepeat])) {
			$Model->data[$Model->alias][$formFieldRepeat] = '';
		}
		if ($this->settings[$Model->alias]['current'] && !isset($Model->data[$Model->alias][$formFieldCurrent])) {
			$Model->data[$Model->alias][$formFieldCurrent] = '';
		}

		// Check if we need to trigger any validation rules
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
			// Make sure we trigger validation if allowEmpty is set but we have the password field set
			if ($new) {
				if ($this->settings[$Model->alias]['confirm'] && empty($Model->data[$Model->alias][$formFieldRepeat])) {
					$Model->invalidate($formFieldRepeat, __d('tools', 'valErrPwdNotMatch'));
				}
			}
		}

		// Update whitelist
		$this->_modifyWhitelist($Model);

		return true;
	}

	/**
	 * Hashing the password and whitelisting
	 *
	 * @param Model $Model
	 * @return bool Success
	 */
	public function beforeSave(Model $Model, $options = []) {
		$formField = $this->settings[$Model->alias]['formField'];
		$field = $this->settings[$Model->alias]['field'];
		$type = $this->settings[$Model->alias]['hashType'];
		$salt = $this->settings[$Model->alias]['hashSalt'];
		if ($this->settings[$Model->alias]['authType'] === 'Blowfish') {
			$type = 'blowfish';
			$salt = false;
		}

		if (isset($Model->data[$Model->alias][$formField])) {
			if ($type === 'blowfish' && function_exists('password_hash') && !empty($this->settings[$Model->alias]['passwordHasher'])) {
				$cost = !empty($this->settings[$Model->alias]['hashCost']) ? $this->settings[$Model->alias]['hashCost'] : 10;
				$options = ['cost' => $cost];
				$PasswordHasher = $this->_getPasswordHasher($this->settings[$Model->alias]['passwordHasher']);
				$Model->data[$Model->alias][$field] = $PasswordHasher->hash($Model->data[$Model->alias][$formField], $options);
			} else {
				$Model->data[$Model->alias][$field] = Security::hash($Model->data[$Model->alias][$formField], $type, $salt);
			}
			if (!$Model->data[$Model->alias][$field]) {
				return false;
			}

			unset($Model->data[$Model->alias][$formField]);
			if ($this->settings[$Model->alias]['confirm']) {
				$formFieldRepeat = $this->settings[$Model->alias]['formFieldRepeat'];
				unset($Model->data[$Model->alias][$formFieldRepeat]);
			}
			if ($this->settings[$Model->alias]['current']) {
				$formFieldCurrent = $this->settings[$Model->alias]['formFieldCurrent'];
				unset($Model->data[$Model->alias][$formFieldCurrent]);
			}
		}

		// Update whitelist
		$this->_modifyWhitelist($Model, true);
		return true;
	}

	/**
	 * Checks if the PasswordHasher class supports this and if so, whether the
	 * password needs to be rehashed or not.
	 * This is mainly supported by Tools.Modern (using Bcrypt) yet.
	 *
	 * @param Model $Model
	 * @param string $hash Currently hashed password.
	 * @return bool Success
	 */
	public function needsPasswordRehash(Model $Model, $hash) {
		if (empty($this->settings[$Model->alias]['passwordHasher'])) {
			return false;
		}

		$PasswordHasher = $this->_getPasswordHasher($this->settings[$Model->alias]['passwordHasher']);
		if (!method_exists($PasswordHasher, 'needsRehash')) {
			return false;
		}
		return $PasswordHasher->needsRehash($hash);
	}

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
	 * @return bool Success
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

		return $this->_validateSameHash($Model, $pwd);
	}

	/**
	 * If not implemented in AppModel
	 *
	 * @param Model $Model
	 * @param array $data
	 * @param string $compareWith String to compare field value with
	 * @return bool Success
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
	 * If not implemented in AppModel
	 *
	 * @return bool Success
	 */
	public function validateNotSame(Model $Model, $data, $field1, $field2) {
		$value1 = $Model->data[$Model->alias][$field1];
		$value2 = $Model->data[$Model->alias][$field2];
		return ($value1 !== $value2);
	}

	/**
	 * If not implemented in AppModel
	 *
	 * @return bool Success
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
		if ($type === 'blowfish' && function_exists('password_hash') && !empty($this->settings[$Model->alias]['passwordHasher'])) {
			$value = $Model->data[$Model->alias][$formField];
		} else {
			$value = Security::hash($Model->data[$Model->alias][$formField], $type, $salt);
		}

		$dbValue = $Model->fieldByConditions($field, [$Model->primaryKey => $primaryKey]);
		if (!$dbValue) {
			return true;
		}

		if ($type === 'blowfish' && function_exists('password_hash') && !empty($this->settings[$Model->alias]['passwordHasher'])) {
			$PasswordHasher = $this->_getPasswordHasher($this->settings[$Model->alias]['passwordHasher']);
			return !$PasswordHasher->check($value, $dbValue);
		}
		return ($value !== $dbValue);
	}

	/**
	 * PasswordableBehavior::_validateSameHash()
	 *
	 * @param Model $Model
	 * @param string $pwd
	 * @return bool Success
	 */
	protected function _validateSameHash(Model $Model, $pwd) {
		$field = $this->settings[$Model->alias]['field'];
		$type = $this->settings[$Model->alias]['hashType'];
		$salt = $this->settings[$Model->alias]['hashSalt'];
		if ($this->settings[$Model->alias]['authType'] === 'Blowfish') {
			$type = 'blowfish';
			$salt = false;
		}

		$primaryKey = $Model->data[$Model->alias][$Model->primaryKey];
		$record = $Model->find('first', ['conditions' => [$Model->primaryKey => $primaryKey]]);
		if (empty($record[$Model->alias][$field]) && $pwd) {
			return false;
		}
		$dbValue = $record[$Model->alias][$field];

		if ($type === 'blowfish' && function_exists('password_hash') && !empty($this->settings[$Model->alias]['passwordHasher'])) {
			$value = $pwd;
		} else {
			if ($type === 'blowfish') {
				$salt = $dbValue;
			}
			$value = Security::hash($pwd, $type, $salt);
		}

		if ($type === 'blowfish' && function_exists('password_hash') && !empty($this->settings[$Model->alias]['passwordHasher'])) {
			$PasswordHasher = $this->_getPasswordHasher($this->settings[$Model->alias]['passwordHasher']);
			return $PasswordHasher->check($value, $dbValue);
		}
		return $value === $dbValue;
	}

	/**
	 * PasswordableBehavior::_getPasswordHasher()
	 *
	 * @param mixed $hasher Name or options array.
	 * @return PasswordHasher
	 */
	protected function _getPasswordHasher($hasher) {
		return PasswordHasherFactory::build($hasher);

		$class = $hasher;
		$config = [];
		if (is_array($hasher)) {
			$class = $hasher['className'];
			unset($hasher['className']);
			$config = $hasher;
		}

		list($plugin, $class) = pluginSplit($class, true);
		$className = $class . 'PasswordHasher';
		App::uses($className, $plugin . 'Controller/Component/Auth');
		if (!class_exists($className)) {
			throw new CakeException(sprintf('Password hasher class "%s" was not found.', $class));
		}
		if (!is_subclass_of($className, 'AbstractPasswordHasher')) {
			throw new CakeException('Password hasher must extend AbstractPasswordHasher class.');
		}
		return new $className($config);
	}

	/**
	 * Modify the model's whitelist.
	 *
	 * Since 2.5 behaviors can also modify the whitelist for validate, thus this behavior can now
	 * (>= CakePHP 2.5) add the form fields automatically, as well (not just the password field itself).
	 *
	 * @param Model $Model
	 * @return void
	 */
	protected function _modifyWhitelist(Model $Model, $onSave = false) {
		$fields = [];
		if ($onSave) {
			$fields[] = $this->settings[$Model->alias]['field'];
		} else {
			$fields[] = $this->settings[$Model->alias]['formField'];
			if ($this->settings[$Model->alias]['confirm']) {
				$fields[] = $this->settings[$Model->alias]['formFieldRepeat'];
			}
			if ($this->settings[$Model->alias]['current']) {
				$fields[] = $this->settings[$Model->alias]['formFieldCurrent'];
			}
		}

		foreach ($fields as $field) {
			if (!empty($Model->whitelist) && !in_array($field, $Model->whitelist)) {
				$Model->whitelist = array_merge($Model->whitelist, [$field]);
			}
		}
	}

}
