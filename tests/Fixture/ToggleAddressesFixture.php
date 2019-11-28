<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ToggleAddressesFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'null' => false, 'default' => null, 'autoIncrement' => true, 'precision' => null],
		'category_id' => ['type' => 'integer', 'length' => 10, 'unsigned' => true, 'null' => true, 'default' => null, 'precision' => null, 'autoIncrement' => null],
		'name' => ['type' => 'string', 'length' => 60, 'null' => false, 'default' => null, 'precision' => null, 'fixed' => null],
		'primary' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'precision' => null],
		'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'precision' => null],
		'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'precision' => null],
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
			'category_id' => 1,
			'name' => 'Foo',
			'primary' => 1,
			'created' => '2017-04-02 15:45:33',
			'modified' => '2017-04-02 15:45:33',
		],
	];

}
