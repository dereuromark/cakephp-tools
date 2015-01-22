<?php
App::uses('ToolsAppModel', 'Tools.Model');

/**
 * KeyValue Model
 *
 */
class KeyValue extends ToolsAppModel {

	public $displayField = 'value';

	public $order = [];

	public $validate = [
		'foreign_id' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
			],
		],
		'key' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
			],
		],
	];

}
