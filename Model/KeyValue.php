<?php
App::uses('ToolsAppModel', 'Tools.Model');
/**
 * KeyValue Model
 *
 */
class KeyValue extends ToolsAppModel {

	public $displayField = 'value';

	public $order = array();

	public $validate = array(
		'foreign_id' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
			),
		),
		'key' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
			),
		),
	);

}
