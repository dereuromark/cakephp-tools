<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 */
class AuthorsFixture extends TestFixture {

	/**
	 * fields property
	 *
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'default' => null],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * records property
	 *
	 * @var array
	 */
	public array $records = [
		['name' => 'mariano'],
		['name' => 'nate'],
		['name' => 'larry'],
		['name' => 'garrett'],
	];

}
