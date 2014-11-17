<?php
namespace Tools\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Auth\PasswordHasherFactory;

if (!defined('PWD_MIN_LENGTH')) {
	define('PWD_MIN_LENGTH', 6);
}
if (!defined('PWD_MAX_LENGTH')) {
	define('PWD_MAX_LENGTH', 20);
}

/**
 * A CakePHP behavior to work with passwords the easy way
 * - complete validation
 * - hashing of password
 * - requires fields (no tempering even without security component)
 * - usable for edit forms (require=>false for optional password update)
 *
 * Usage: Do NOT hard-add it in the model itself.
 * attach it dynamically in only those actions where you actually change the password like so:
 * $this->Users->addBehavior('Tools.Passwordable', array(SETTINGSARRAY));
 * as first line in any action where you want to allow the user to change his password
 * also add the two form fields in the form (pwd, PWD_confirm)
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
 *
 * @author Mark Scherer
 * @link http://www.dereuromark.de/2011/08/25/working-with-passwords-in-cakephp
 * @license MIT
 */
class PasswordableBehavior extends Behavior {

	/**
	 * @var array
	 */
	protected $_defaultConfig = array(
		'field' => 'password',
		'confirm' => true, // Set to false if in admin view and no confirmation (pwd_repeat) is required
		'require' => true, // If a password change is required (set to false for edit forms, leave it true for pure password update forms)
		'current' => false, // Enquire the current password for security purposes
		'formField' => 'pwd',
		'formFieldRepeat' => 'pwd_repeat',
		'formFieldCurrent' => 'pwd_current',
		'userModel' => null, // Defaults to Users
		'auth' => null, // Which component (defaults to AuthComponent),
		'authType' => 'Form', // Which type of authenticate (Form, Blowfish, ...)
		'passwordHasher' => 'Default', // If a custom pwd hasher is been used
		'allowSame' => true, // Don't allow the old password on change
		'minLength' => PWD_MIN_LENGTH,
		'maxLength' => PWD_MAX_LENGTH,
		'validator' => 'default'
	);

	/**
	 * @var array
	 */
	protected $_validationRules = array(
		'formField' => array(
			'between' => array(
				'rule' => array('lengthBetween', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'message' => array('valErrBetweenCharacters {0} {1}', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'last' => true,
				//'provider' => 'table'
			)
		),
		'formFieldRepeat' => array(
			'between' => array(
				'rule' => array('lengthBetween', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'message' => array('valErrBetweenCharacters {0} {1}', PWD_MIN_LENGTH, PWD_MAX_LENGTH),
				'last' => true,
				//'provider' => 'table'
			),
			'validateIdentical' => array(
				'rule' => array('validateIdentical', ['compare' => 'formField']),
				'message' => 'valErrPwdNotMatch',
				'last' => true,
				'provider' => 'table'
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
				'provider' => 'table'
			)
		),
	);

	/**
	 * Password hasher instance.
	 *
	 * @var AbstractPasswordHasher
	 */
	protected $_passwordHasher;

	/**
	 * Adding validation rules
	 * also adds and merges config settings (direct + configure)
	 *
	 * @return void
	 */
	public function __construct(Table $table, array $config = []) {
		$defaults = $this->_defaultConfig;
		if ($configureDefaults = Configure::read('Passwordable')) {
			$defaults = $configureDefaults + $defaults;
		}
		$config + $defaults;
		parent::__construct($table, $config);

		$formField = $this->_config['formField'];
		$formFieldRepeat = $this->_config['formFieldRepeat'];
		$formFieldCurrent = $this->_config['formFieldCurrent'];

		if ($formField === $this->_config['field']) {
			throw new \Exception('Invalid setup - the form field must to be different from the model field (' . $this->_config['field'] . ').');
		}

		$rules = $this->_validationRules;
		foreach ($rules as $field => $fieldRules) {
			foreach ($fieldRules as $key => $rule) {
				//$rule['allowEmpty'] = !$this->_config['require'];

				if ($key === 'between') {
					$rule['rule'][1] = $this->_config['minLength'];
					$rule['message'][1] = $this->_config['minLength'];
					$rule['rule'][2] = $this->_config['maxLength'];
					$rule['message'][2] = $this->_config['maxLength'];
				}

				if (is_array($rule['message'])) {
					$message = array_shift($rule['message']);
					$rule['message'] = __d('tools', $message, $rule['message']);
				} else {
					$rule['message'] = __d('tools', $rule['message']);
				}
				$fieldRules[$key] = $rule;
			}
			$rules[$field] = $fieldRules;
		}

		$validator = $table->validator($this->_config['validator']);

		// Add the validation rules if not already attached
		if (!count($validator->field($formField))) {
			$validator->add($formField, $rules['formField']);
			$validator->allowEmpty($formField, !$this->_config['require']);
		}
		if (!count($validator->field($formFieldRepeat))) {
			$ruleSet = $rules['formFieldRepeat'];
			$ruleSet['validateIdentical']['rule'][1] = $formField;
			$validator->add($formFieldRepeat, $ruleSet);
			$validator->allowEmpty($formFieldRepeat, !$this->_config['require']);
		}

		if ($this->_config['current'] && !count($validator->field($formFieldCurrent))) {
			$validator->add($formFieldCurrent, $rules['formFieldCurrent']);
			$validator->allowEmpty($formFieldCurrent, !$this->_config['require']);

			if (!$this->_config['allowSame']) {
				$validator->add($formField, 'validateNotSame', array(
					'rule' => array('validateNotSame', ['compare' => $formFieldCurrent]),
					'message' => __d('tools', 'valErrPwdSameAsBefore'),
					'last' => true,
					'provider' => 'table'
				));
			}
		} elseif (!count($validator->field($formFieldCurrent))) {
			// Try to match the password against the hash in the DB
			if (!$this->_config['allowSame']) {
				$validator->add($formField, 'validateNotSame', array(
					'rule' => array('validateNotSameHash'),
					'message' => __d('tools', 'valErrPwdSameAsBefore'),
					//'allowEmpty' => !$this->_config['require'],
					'last' => true,
					'provider' => 'table'
				));
				$validator->allowEmpty($formField, !$this->_config['require']);
			}
		}

		$this->_table = $table;
	}

	/**
	 * Preparing the data
	 *
	 * @return void
	 */
	public function beforeValidate(Event $event, Entity $entity) {
		$formField = $this->_config['formField'];
		$formFieldRepeat = $this->_config['formFieldRepeat'];
		$formFieldCurrent = $this->_config['formFieldCurrent'];

		// Make sure fields are set and validation rules are triggered - prevents tempering of form data
		if ($entity->get($formField) === null) {
			$entity->set($formField, '');
		}
		if ($this->_config['confirm'] && $entity->get($formFieldRepeat) === null) {
			$entity->set($formFieldRepeat, '');
		}
		if ($this->_config['current'] && $entity->get($formFieldCurrent) === null) {
			$entity->set($formFieldCurrent, '');
		}

		// Check if we need to trigger any validation rules
		if (!$this->_config['require']) {
			$current = $entity->get($formFieldCurrent);
			$new = $entity->get($formField) || $entity->get($formFieldRepeat);
			if (!$new && !$current) {
				//$validator->remove($formField); // tmp only!
				//unset($Model->validate[$formField]);
				$entity->unsetProperty($formField);
				if ($this->_config['confirm']) {
					//$validator->remove($formFieldRepeat); // tmp only!
					//unset($Model->validate[$formFieldRepeat]);
					$entity->unsetProperty($formFieldRepeat);
				}
				if ($this->_config['current']) {
					//$validator->remove($formFieldCurrent); // tmp only!
					//unset($Model->validate[$formFieldCurrent]);
					$entity->unsetProperty($formFieldCurrent);
				}
				return true;
			}
			// Make sure we trigger validation if allowEmpty is set but we have the password field set
			if ($new) {
				if ($this->_config['confirm'] && !$entity->get($formFieldRepeat)) {
					$entity->errors($formFieldRepeat, __d('tools', 'valErrPwdNotMatch'));
				}
			}
		}

		// Update whitelist
		$this->_modifyWhitelist($entity);

		return true;
	}

	/**
	 * Hashing the password and whitelisting
	 *
	 * @param Event $event
	 * @return void
	 */
	public function beforeSave(Event $event, Entity $entity) {
		$formField = $this->_config['formField'];
		$field = $this->_config['field'];

		if ($entity->get($formField) !== null) {
			$cost = !empty($this->_config['hashCost']) ? $this->_config['hashCost'] : 10;
			$options = array('cost' => $cost);
			$PasswordHasher = $this->_getPasswordHasher($this->_config['passwordHasher']);
			$entity->set($field, $PasswordHasher->hash($entity->get($formField), $options));

			if (!$entity->get($field)) {
				throw new \Exception('Empty field');
			}

			$entity->unsetProperty($formField);
			//$entity->set($formField, null);

			if ($this->_config['confirm']) {
				$formFieldRepeat = $this->_config['formFieldRepeat'];
				$entity->unsetProperty($formFieldRepeat);
				//unset($Model->data[$table->alias()][$formFieldRepeat]);
			}
			if ($this->_config['current']) {
				$formFieldCurrent = $this->_config['formFieldCurrent'];
				$entity->unsetProperty($formFieldCurrent);
				//unset($Model->data[$table->alias()][$formFieldCurrent]);
			}
		}

		// Update whitelist
		$this->_modifyWhitelist($entity, true);
		return true;
	}

	/**
	 * Checks if the PasswordHasher class supports this and if so, whether the
	 * password needs to be rehashed or not.
	 * This is mainly supported by Tools.Modern (using Bcrypt) yet.
	 *
	 * @param string $hash Currently hashed password.
	 * @return bool Success
	 */
	public function needsPasswordRehash($hash) {
		$PasswordHasher = $this->_getPasswordHasher($this->_config['passwordHasher']);
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
	public function validateCurrentPwd($pwd, $context) {
		$uid = null;
		if (!empty($context['data'][$this->_table->primaryKey()])) {
			$uid = $context['data'][$this->_table->primaryKey()];
		} else {
			trigger_error('No user id given');
			return false;
		}

		return $this->_validateSameHash($pwd, $context);
	}

	/**
	 * If not implemented in AppModel
	 *
	 * @param Model $Model
	 * @param array $data
	 * @param string $compareWith String to compare field value with
	 * @return bool Success
	 */
	public function validateIdentical($value, $options, $context) {
		if (!is_array($options)) {
			$options = array('compare' => $options);
		}

		$compareValue = $context['providers']['entity']->get($options['compare']);
		return ($compareValue === $value);
	}

	/**
	 * If not implemented in AppModel
	 *
	 * @return bool Success
	 */
	public function validateNotSame($data, $options, $context) {
		if (!is_array($options)) {
			$options = array('compare' => $options);
		}

		$value1 = $context['providers']['entity']->get($context['field']);
		$value2 = $context['providers']['entity']->get($options['compare']);
		return ($value1 !== $value2);
	}

	/**
	 * If not implemented in AppModel
	 *
	 * @return bool Success
	 */
	public function validateNotSameHash($data, $context) {
		$field = $this->_config['field'];
		if (!$context['providers']['entity']->get($this->_table->primaryKey())) {
			return true;
		}

		$primaryKey = $context['providers']['entity']->get($this->_table->primaryKey());
		$value = $context['providers']['entity']->get($context['field']);

		$dbValue = $this->_table->find()->where(array($this->_table->primaryKey() => $primaryKey))->first();
		if (!$dbValue) {
			return true;
		}
		$dbValue = $dbValue[$field];
		if (!$dbValue) {
			return true;
		}

		$PasswordHasher = $this->_getPasswordHasher($this->_config['passwordHasher']);
		return !$PasswordHasher->check($value, $dbValue);
	}

	/**
	 * PasswordableBehavior::_validateSameHash()
	 *
	 * @param Model $Model
	 * @param string $pwd
	 * @return bool Success
	 */
	protected function _validateSameHash($pwd, $context) {
		$field = $this->_config['field'];

		$primaryKey = $context['providers']['entity']->get($this->_table->primaryKey());
		$dbValue = $this->_table->find()->where(array($this->_table->primaryKey() => $primaryKey))->first();
		if (!$dbValue) {
			return false;
		}
		$dbValue = $dbValue[$field];
		if (!$dbValue && $pwd) {
			return false;
		}

		$PasswordHasher = $this->_getPasswordHasher($this->_config['passwordHasher']);
		return $PasswordHasher->check($pwd, $dbValue);
	}

	/**
	 * PasswordableBehavior::_getPasswordHasher()
	 *
	 * @param mixed $hasher Name or options array.
	 * @return PasswordHasher
	 */
	protected function _getPasswordHasher($hasher) {
		if ($this->_passwordHasher) {
			return $this->_passwordHasher;
		}
		return $this->_passwordHasher = PasswordHasherFactory::build($hasher);
	}

	/**
	 * Modify the model's whitelist.
	 *
	 * Since 2.5 behaviors can also modify the whitelist for validate, thus this behavior can now
	 * (>= CakePHP 2.5) add the form fields automatically, as well (not just the password field itself).
	 *
	 * @param Model $Model
	 * @return void
	 * @deprecated 3.0
	 */
	protected function _modifyWhitelist(Entity $entity, $onSave = false) {
		$fields = array();
		if ($onSave) {
			$fields[] = $this->_config['field'];
		} else {
			$fields[] = $this->_config['formField'];
			if ($this->_config['confirm']) {
				$fields[] = $this->_config['formFieldRepeat'];
			}
			if ($this->_config['current']) {
				$fields[] = $this->_config['formFieldCurrent'];
			}
		}

		foreach ($fields as $field) {
			if (!empty($Model->whitelist) && !in_array($field, $Model->whitelist)) {
				$Model->whitelist = array_merge($Model->whitelist, array($field));
			}
		}
	}

}
