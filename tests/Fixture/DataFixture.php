<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class DataFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
		'name' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
		'data_json' => ['type' => 'text', 'length' => 16777215, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
		'data_array' => ['type' => 'text', 'length' => 16777215, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
		],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
			[
				'name' => 'Lorem ipsum dolor sit amet',
				'data_json' => null,
				'data_array' => null,
			],
		];

}
