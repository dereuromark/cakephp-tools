<?php

namespace Tools\Model\Behavior;

use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ConfirmableBehavior allows forms to easily require a checkbox toggled (confirmed).
 * Example: Terms of use on registration forms or some "confirm delete checkbox"
 *
 * Copyright 2011, dereuromark (http://www.dereuromark.de)
 *
 * @link http://github.com/dereuromark/
 * @license http://opensource.org/licenses/mit-license.php MIT
 * @link https://www.dereuromark.de/2011/07/05/introducing-two-cakephp-behaviors/
 */
class ConfirmableBehavior extends Behavior {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'message' => null,
		'field' => 'confirm',
		//'table' => null,
		'validator' => 'default',
	];

	/**
	 * @param \Cake\ORM\Table $table
	 * @param array $config
	 */
	public function __construct(Table $table, array $config = []) {
		parent::__construct($table, $config);

		if (!$this->_config['message']) {
			$this->_config['message'] = __d('tools', 'Please confirm the checkbox');
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Validation\Validator $validator
	 * @param string $name
	 * @return void
	 */
	public function buildValidator(EventInterface $event, Validator $validator, $name) {
		$this->build($validator, $name);
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 * @param string $name
	 * @return void
	 */
	public function build(Validator $validator, $name = 'default') {
		if ($name !== $this->_config['validator']) {
			return;
		}

		$field = $this->_config['field'];
		$message = $this->_config['message'];
		$validator->add($field, 'notBlank', [
				'rule' => function ($value, $context) {
					return !empty($value);
				},
				'message' => $message,
				//'provider' => 'table',
				'requirePresence' => true,
				'allowEmpty' => false,
				'last' => true]
		);
		$validator->requirePresence($field);
	}

}
