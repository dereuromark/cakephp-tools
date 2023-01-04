<?php

namespace Tools\Test\Fixture;

use Cake\Database\Schema\TableSchema;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * SessionFixture
 */
class SessionsFixture extends TestFixture {

	/**
	 * fields property
	 *
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'string', 'length' => 128],
		'data' => ['type' => 'binary', 'length' => TableSchema::LENGTH_MEDIUM, 'null' => true],
		'expires' => ['type' => 'integer', 'length' => 11, 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * records property
	 *
	 * @var array
	 */
	public array $records = [];

}
