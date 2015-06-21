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
			'notBlank' => [
				'rule' => ['notBlank'],
				'message' => 'valErrMandatoryField',
			],
		],
		'key' => [
			'notBlank' => [
				'rule' => ['notBlank'],
				'message' => 'valErrMandatoryField',
			],
		],
	];

}
