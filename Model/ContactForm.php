<?php
App::uses('ToolsAppModel', 'Tools.Model');

/**
 * "Fake" model to validate all contact forms
 * @author Mark Scherer
 * @cakephp 2.x
 * @license MIT
 */
class ContactForm extends ToolsAppModel {

	protected $_schema = array(
		'name' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '30'),
		'email' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '60'),
		'subject' => array('type' => 'string', 'null' => false, 'default' => '', 'length' => '60'),
		'message' => array('type' => 'text', 'null' => false, 'default' => ''),
	);

	public $useTable = false;

	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
				'last' => true
			)
		),
		'email' => array(
			'email' => array(
				'rule' => array('email', true),
				'message' => 'valErrInvalidEmail',
				'last' => true
			),
		),
		'subject' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
				'last' => true
			)
		),
		'message' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
				'last' => true
			)
		),
	);

}
