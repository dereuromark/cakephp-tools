<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RoleFixture
 */
class StoriesFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'null' => false, 'length' => 64, 'comment' => ''],
		'slug' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 20, 'comment' => ''],
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
			'id' => '1',
			'title' => 'Second',
			'slug' => 'second',
			'created' => '2010-01-07 03:36:32',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '2',
			'active' => 1,
		],
		[
			'id' => '2',
			'title' => 'Third',
			'slug' => 'third',
			'created' => '2010-01-07 03:36:33',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '3',
			'active' => 1,
		],
		[
			'id' => '3',
			'title' => 'First',
			'slug' => 'first',
			'created' => '2010-01-07 03:36:31',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '4',
			'active' => 1,
		],
		[
			'id' => '4',
			'title' => 'Forth',
			'slug' => 'forth',
			'created' => '2010-01-07 03:36:34',
			'modified' => '2010-01-07 03:36:33',
			'sort' => '1',
			'active' => 1,
		],
	];

}
