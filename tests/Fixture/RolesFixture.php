<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RoleFixture
 */
class RolesFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'null' => false, 'length' => 64, 'comment' => ''],
		'alias' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 20, 'comment' => ''],
		'default_role' => ['type' => 'boolean', 'null' => false, 'default' => false, 'collate' => null, 'comment' => 'set at register'],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'sort' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'collate' => null, 'comment' => ''],
		'active' => ['type' => 'boolean', 'null' => false, 'default' => false, 'collate' => null, 'comment' => ''],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'name' => 'Super-Admin',
			'alias' => 'superadmin',
			'default_role' => 0,
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '7',
			'active' => 1,
		],
		[
			'name' => 'Admin',
			'alias' => 'admin',
			'default_role' => 0,
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '6',
			'active' => 1,
		],
		[
			'name' => 'User',
			'alias' => 'user',
			'default_role' => 1,
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '1',
			'active' => 1,
		],
		[
			'name' => 'Partner',
			'alias' => 'partner',
			'default_role' => 0,
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '0',
			'active' => 1,
		],
	];

}
