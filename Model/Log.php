<?php
App::uses('ToolsAppModel', 'Tools.Model');

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}

/**
 * `logs` table populated by LogableBehavior
 */
class Log extends ToolsAppModel {

	public $order = ['Log.created' => 'DESC'];

	public $belongsTo = [
		'User' => [
			'className' => CLASS_USER,
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => ['id', 'username'],
			'order' => ''
		],
	];

	/**
	 * Not really necessary probably
	 */
	public function find($type = null, $query = []) {
		if ($type === 'last') {
			$options = array_merge(['order' => [$this->alias . '.id' => 'DESC']], $query);
			return parent::find('first', $options);
		}
		return parent::find($type, $query);
	}

}
