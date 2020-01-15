<?php

namespace Tools\Model\Behavior;

use ArrayObject;
use Cake\Auth\PasswordHasherFactory;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use RuntimeException;

if (!defined('PWD_MIN_LENGTH')) {
	define('PWD_MIN_LENGTH', 6);
}
if (!defined('PWD_MAX_LENGTH')) {
	define('PWD_MAX_LENGTH', 50);
}

/**
 * A CakePHP behavior to work with passwords the easy way
 * - complete validation
 * - hashing of password
 * - requires fields (no tempering even without security component)
 * - usable for edit forms (require=>false for optional password update)
 *
 * Also capable of:
 * - Require current password prior to altering it (current=>true)
 * - Don't allow the same password it was before (allowSame=>false)
 *
 * Usage: See docs
 *
 * @author Mark Scherer
 * @link https://www.dereuromark.de/2011/08/25/working-with-passwords-in-cakephp
 * @license MIT
 */
class PasswordableBehavior extends Behavior {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'field' => 'password',
		'confirm' => true, // Set to false if in admin view and no confirmation (pwd_repeat) is required
		'require' => true, // If a password change is required (set to false for edit forms, leave it true for pure password update forms)
		'current' => false, // Enquire the current password for security purposes
		'formField' => 'pwd',
		'formFieldRepeat' => 'pwd_repeat',
		'formFieldCurrent' => 'pwd_current',
		'passwordHasher' => 'Default', // If a custom pwd hasher is been used
		'allowSame' => true, // Don't allow the old password on change
		'minLength' => PWD_MIN_LENGTH,
		'maxLength' => PWD_MAX_LENGTH,
		'validator' => 'default',
		'customValidation' => null, // Custom validation rule(s) for the formField
		'forceFieldList' => false,
	];

	/**
	 * @var array
	 */
	protected $_validationRules = [
		'formField' => [
			'between' => [
				'rule' => ['lengthBetween', PWD_MIN_LENGTH, PWD_MAX_LENGTH],
				'message' => ['valErrBetweenCharacters {0} {1}', PWD_MIN_LENGTH, PWD_MAX_LENGTH],
				'last' => true,
				//'provider' => 'table'
			],
		],
		'formFieldRepeat' => [
			'validateIdentical' => [
				'rule' => ['validateIdentical', ['compare' => 'formField']],
				'message' => 'valErrPwdNotMatch',
				'last' => true,
				'provider' => 'table',
			],
		],
		'formFieldCurrent' => [
			'notBlank' => [
				'rule' => ['notBlank'],
				'message' => 'valErrProvideCurrentPwd',
				'last' => true,
			],
			'validateCurrentPwd' => [
				'rule' => 'validateCurrentPwd',
				'message' => 'valErrCurrentPwdIncorrect',
				'last' => true,
				'provider' => 'table',
			],
		],
	];

	/**
	 * Password hasher instance.
	 *
	 * @var \Cake\Auth\AbstractPasswordHasher|null
	 */
	protected $_passwordHasher;

	/**
	 * Adding validation rules
	 * also adds and merges config settings (direct + configure)
	 *
	 * @param \Cake\ORM\Table $table
	 * @param array $config
	 */
	public function __construct(Table $table, array $config = []) {
		$defaults = $this->_defaultConfig;
		$configureDefaults = Configure::read('Passwordable');
		if ($configureDefaults) {
			$defaults = $configureDefaults + $defaults;
		}
		$config += $defaults;
		parent::__construct($table, $config);
	}

	/**
	 * Constructor hook method.
	 *
	 * Implement this method to avoid having to overwrite
	 * the constructor and call parent.
	 *
	 * @param array $config The configuration array this behavior is using.
	 * @return void
	 * @throws \RuntimeException
	 */
	public function initialize(array $config): void {
		$formField = $this->_config['formField'];
		$formFieldRepeat = $this->_config['formFieldRepeat'];
		$formFieldCurrent = $this->_config['formFieldCurrent'];

		if ($formField === $this->_config['field']) {
			throw new RuntimeException('Invalid setup - the form field must to be different from the model field (' . $this->_config['field'] . ').');
		}

		$rules = $this->_validationRules;
		foreach ($rules as $field => $fieldRules) {
			foreach ($fieldRules as $key => $rule) {
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

		$validator = $this->_table->getValidator($this->_config['validator']);

		// Add the validation rules if not already attached
		if (!count($validator->field($formField))) {
			$validator->add($formField, $rules['formField']);
			$validator->allowEmpty($formField, !$this->_config['require']);
		}
		if (!count($validator->field($formFieldRepeat))) {
			$ruleSet = $rules['formFieldRepeat'];
			$ruleSet['validateIdentical']['rule'][1] = $formField;
			$validator->add($formFieldRepeat, $ruleSet);
			$require = $this->_config['require'];
			$validator->allowEmpty($formFieldRepeat, function ($context) use ($require, $formField) {
				if (!$require && !empty($context['data'][$formField])) {
					return false;
				}
				return !$require;
			});
		}

		if ($this->_config['current'] && !count($validator->field($formFieldCurrent))) {
			$validator->add($formFieldCurrent, $rules['formFieldCurrent']);
			$require = $this->_config['require'];
			$validator->allowEmpty($formFieldCurrent, function ($context) use ($require, $formField) {
				if (!$require && !empty($context['data'][$formField])) {
					return false;
				}
				return !$require;
			});

			if (!$this->_config['allowSame']) {
				$validator->add($formField, 'validateNotSame', [
					'rule' => ['validateNotSame', ['compare' => $formFieldCurrent]],
					'message' => __d('tools', 'valErrPwdSameAsBefore'),
					'last' => true,
					'provider' => 'table',
				]);
			}
		} elseif (!count($validator->field($formFieldCurrent))) {
			// Try to match the password against the hash in the DB
			if (!$this->_config['allowSame']) {
				$validator->add($formField, 'validateNotSame', [
					'rule' => ['validateNotSameHash'],
					'message' => __d('tools', 'valErrPwdSameAsBefore'),
					//'allowEmpty' => !$this->_config['require'],
					'last' => true,
					'provider' => 'table',
				]);
				$validator->allowEmpty($formField, !$this->_config['require']);
			}
		}

		// Add custom rule(s) if configured
		if ($this->_config['customValidation']) {
			$validator->add($formField, $this->_config['customValidation']);
		}
	}

	/**
	 * Preparing the data
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options) {
		$formField = $this->_config['formField'];
		$formFieldRepeat = $this->_config['formFieldRepeat'];
		$formFieldCurrent = $this->_config['formFieldCurrent'];

		if (!isset($options['fields']) && $this->_config['forceFieldList']) {
			$options['fields'] = array_keys((array)$data);
		}
		if (isset($options['fields'])) {
			if (!in_array($formField, $options['fields'])) {
				$options['fields'][] = $formField;
			}
			if (!in_array($formFieldRepeat, $options['fields'])) {
				$options['fields'][] = $formFieldRepeat;
			}
			if (!in_array($formFieldCurrent, $options['fields'])) {
				$options['fields'][] = $formFieldCurrent;
			}
		}

		// Make sure fields are set and validation rules are triggered - prevents tempering of form data
		if (!isset($data[$formField])) {
			$data[$formField] = '';
		}
		if ($this->_config['confirm'] && !isset($data[$formFieldRepeat])) {
			$data[$formFieldRepeat] = '';
		}
		if ($this->_config['current'] && !isset($data[$formFieldCurrent])) {
			$data[$formFieldCurrent] = '';
		}

		// Check if we need to trigger any validation rules
		if (!$this->_config['require']) {
			$new = !empty($data[$formField]) || !empty($data[$formFieldRepeat]);

			// Make sure we trigger validation if allowEmpty is set but we have the password field set
			if ($new) {
				if ($this->_config['confirm'] && empty($data[$formFieldRepeat])) {
					//$entity->errors($formFieldRepeat, __d('tools', 'valErrPwdNotMatch'));
				}
			}
		}
	}

	/**
	 * Preparing the data
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @param string $operation
	 * @return void
	 */
	public function beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, $operation) {
		$formField = $this->_config['formField'];
		$formFieldRepeat = $this->_config['formFieldRepeat'];
		$formFieldCurrent = $this->_config['formFieldCurrent'];

		// Check if we need to trigger any validation rules
		if (!$this->_config['require']) {
			$current = $entity->get($formFieldCurrent);
			$new = $entity->get($formField) || $entity->get($formFieldRepeat);
			if (!$new && !$current) {
				$entity->unset($formField);
				if ($this->_config['confirm']) {
					$entity->unset($formFieldRepeat);
				}
				if ($this->_config['current']) {
					$entity->unset($formFieldCurrent);
				}
				return;
			}
		}
	}

	/**
	 * Hashing the password and whitelisting
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @throws \RuntimeException
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
		$formField = $this->_config['formField'];
		$field = $this->_config['field'];

		$PasswordHasher = $this->_getPasswordHasher($this->_config['passwordHasher']);

		if ($entity->get($formField) !== null) {
			$entity->set($field, $PasswordHasher->hash($entity->get($formField)));

			if (!$entity->get($field)) {
				throw new RuntimeException('Empty field');
			}

			$entity->unset($formField);

			if ($this->_config['confirm']) {
				$formFieldRepeat = $this->_config['formFieldRepeat'];
				$entity->unset($formFieldRepeat);
			}
			if ($this->_config['current']) {
				$formFieldCurrent = $this->_config['formFieldCurrent'];
				$entity->unset($formFieldCurrent);
			}
		} else {
			// To help mitigate timing-based user enumeration attacks.
			$PasswordHasher->hash('');
		}
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
	 * If not implemented in Table class
	 *
	 * Note: requires the used Auth component to be App::uses() loaded.
	 * It also reqires the same Auth setup as in your AppController's beforeFilter().
	 * So if you set up any special passwordHasher or auth type, you need to provide those
	 * with the settings passed to the behavior:
	 *
	 * 'passwordHasher' => array(
	 *     'className' => 'Simple',
	 *     'hashType' => 'sha256'
	 * )
	 *
	 * @param string $pwd
	 * @param array $context
	 * @return bool Success
	 */
	public function validateCurrentPwd($pwd, $context) {
		$uid = null;
		if (!empty($context['data'][$this->_table->getPrimaryKey()])) {
			$uid = $context['data'][$this->_table->getPrimaryKey()];
		} else {
			trigger_error('No user id given');
			return false;
		}

		return $this->_validateSameHash($pwd, $context);
	}

	/**
	 * If not implemented in Table class
	 *
	 * @param string $value
	 * @param array $options
	 * @param array $context
	 * @return bool Success
	 */
	public function validateIdentical($value, $options, $context) {
		if (!is_array($options)) {
			$options = ['compare' => $options];
		}

		$compareValue = $context['data'][$options['compare']];
		return $compareValue === $value;
	}

	/**
	 * If not implemented in Table class
	 *
	 * @param string $data
	 * @param array $options
	 * @param array $context
	 * @return bool Success
	 */
	public function validateNotSame($data, $options, $context) {
		if (!is_array($options)) {
			$options = ['compare' => $options];
		}

		$compareValue = $context['data'][$options['compare']];
		return $compareValue !== $data;
	}

	/**
	 * If not implemented in Table class
	 *
	 * @param string $data
	 * @param array $context
	 * @return bool Success
	 */
	public function validateNotSameHash($data, $context) {
		$field = $this->_config['field'];
		if (empty($context['data'][$this->_table->getPrimaryKey()])) {
			return true;
		}

		$primaryKey = $context['data'][$this->_table->getPrimaryKey()];
		$value = $context['data'][$context['field']];

		$dbValue = $this->_table->find()->where([$this->_table->getAlias() . '.' . $this->_table->getPrimaryKey() => $primaryKey])->first();
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
	 * @param string $pwd
	 * @param array $context
	 * @return bool Success
	 */
	protected function _validateSameHash($pwd, $context) {
		$field = $this->_config['field'];

		$primaryKey = $context['data'][$this->_table->getPrimaryKey()];
		$dbValue = $this->_table->find()->where([$this->_table->getAlias() . '.' . $this->_table->getPrimaryKey() => $primaryKey])->first();
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
	 * @param string|array $hasher Name or options array.
	 * @param array $options
	 * @return \Cake\Auth\AbstractPasswordHasher
	 */
	protected function _getPasswordHasher($hasher, array $options = []) {
		if ($this->_passwordHasher) {
			return $this->_passwordHasher;
		}

		$config = [];
		if (is_string($hasher)) {
			$class = $hasher;
		} else {
			$class = $hasher['className'];
			$config = $hasher;
			unset($config['className']);
		}
		$config['className'] = $class;

		$cost = !empty($this->_config['hashCost']) ? $this->_config['hashCost'] : 10;
		$config['cost'] = $cost;

		$config += $options;

		return $this->_passwordHasher = PasswordHasherFactory::build($config);
	}

}
