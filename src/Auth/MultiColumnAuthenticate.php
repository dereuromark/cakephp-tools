<?php

namespace Tools\Auth;

use Cake\Auth\FormAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

/**
 * An authentication adapter for AuthComponent
 *
 * Provides the ability to authenticate using POST data. The username form input
 * can be checked against multiple table columns, for instance username and email
 *
 * ```
 *    $this->Auth->setConfig('authenticate', [
 *        'Tools.MultiColumn' => [
 *            'fields' => [
 *                'username' => 'login',
 *                'password' => 'password'
 *             ],
 *            'columns' => ['username', 'email'],
 *        ]
 *    ]);
 * ```
 *
 * Licensed under The MIT License
 * Copied from discontinued FriendsOfCake/Authenticate
 */
class MultiColumnAuthenticate extends FormAuthenticate {

	/**
	 * Besides the keys specified in BaseAuthenticate::$_defaultConfig,
	 * MultiColumnAuthenticate uses the following extra keys:
	 *
	 * - 'columns' Array of columns to check username form input against
	 *
	 * @param \Cake\Controller\ComponentRegistry $registry The Component registry
	 *   used on this request.
	 * @param array $config Array of config to use.
	 */
	public function __construct(ComponentRegistry $registry, $config) {
		$this->setConfig([
			'columns' => [],
		]);

		parent::__construct($registry, $config);
	}

	/**
	 * Get query object for fetching user from database.
	 *
	 * @param string $username The username/identifier.
	 * @return \Cake\ORM\Query
	 */
	protected function _query(string $username): Query {
		$table = TableRegistry::get($this->_config['userModel']);

		$columns = [];
		foreach ($this->_config['columns'] as $column) {
			$columns[] = [$table->aliasField($column) => $username];
		}
		$conditions = ['OR' => $columns];

		$options = [
			'conditions' => $conditions,
		];

		if (!empty($this->_config['scope'])) {
			$options['conditions'] = array_merge($options['conditions'], $this->_config['scope']);
		}
		if (!empty($this->_config['contain'])) {
			$options['contain'] = $this->_config['contain'];
		}

		$finder = $this->_config['finder'];
		if (is_array($finder)) {
			$options += current($finder);
			$finder = key($finder);
		}

		if (!isset($options['username'])) {
			$options['username'] = $username;
		}

		return $table->find($finder, $options);
	}

}
