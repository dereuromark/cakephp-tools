<?php
App::uses('ToolsAppModel', 'Tools.Model');

/**
 * "Fake" model to validate all contact forms
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class ContactForm extends ToolsAppModel {

	protected $_schema = [
		'name' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '30'],
		'email' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '60'],
		'subject' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '60'],
		'message' => ['type' => 'text', 'null' => false, 'default' => ''],
	];

	public $useTable = false;

	public $validate = [
		'name' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
				'last' => true
			]
		],
		'email' => [
			'email' => [
				'rule' => ['email', true],
				'message' => 'valErrInvalidEmail',
				'last' => true
			],
		],
		'subject' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
				'last' => true
			]
		],
		'message' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
				'last' => true
			]
		],
	];

}
