<?php
App::uses('ToolsAppModel', 'Tools.Model');

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}

/**
 * `logs` table populated by LogableBehavior
 */
class Log extends ToolsAppModel {

	public $order = array('Log.created' => 'DESC');

	public $belongsTo = array(
		'User' => array(
			'className' => CLASS_USER,
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => array('id', 'username'),
			'order' => ''
		),
	);

	/**
	 * Not really necessary probably
	 */
	public function find($type = null, $query = array()) {
		if ($type === 'last') {
			$options = array_merge(array('order' => array($this->alias . '.id' => 'DESC')), $query);
			return parent::find('first', $options);
		}
		return parent::find($type, $query);
	}

}
