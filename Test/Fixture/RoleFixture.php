<?php

/**
 * RoleFixture
 *
 */
class RoleFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary', 'collate' => null, 'comment' => ''],
		'name' => ['type' => 'string', 'null' => false, 'length' => 64, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'description' => ['type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'alias' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 20, 'collate' => 'utf8_unicode_ci', 'comment' => '', 'charset' => 'utf8'],
		'default_role' => ['type' => 'boolean', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => 'set at register'],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null, 'collate' => null, 'comment' => ''],
		'sort' => ['type' => 'integer', 'null' => false, 'default' => '0', 'length' => 10, 'collate' => null, 'comment' => ''],
		'active' => ['type' => 'boolean', 'null' => false, 'default' => '0', 'collate' => null, 'comment' => ''],
		'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => 1]],
		'tableParameters' => []
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => '2',
			'name' => 'Admin',
			'description' => 'Zuständig für die Verwaltung der Seite und Mitglieder, Ahndung von Missbrauch und CO',
			'alias' => 'admin',
			'default_role' => 0,
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '6',
			'active' => 1
		],
		[
			'id' => '4',
			'name' => 'User',
			'description' => 'Standardrolle jedes Mitglieds (ausreichend für die meisten Aktionen)',
			'alias' => 'user',
			'default_role' => 1,
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '1',
			'active' => 1
		],
		[
			'id' => '6',
			'name' => 'Partner',
			'description' => 'Partner',
			'alias' => 'partner',
			'default_role' => 0,
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '0',
			'active' => 1
		],
		[
			'id' => '1',
			'name' => 'Super-Admin',
			'description' => 'Zuständig für Programmierung, Sicherheit, Bugfixes, Hosting und CO',
			'alias' => 'superadmin',
			'default_role' => 0,
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '7',
			'active' => 1
		],
	];

}
