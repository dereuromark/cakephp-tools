<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class JsonableCommentsFixture extends TestFixture {

	/**
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'comment' => ['type' => 'string', 'length' => 255, 'null' => false],
		'url' => ['type' => 'string', 'length' => 255, 'null' => false],
		'title' => ['type' => 'string', 'length' => 255, 'null' => false],
		'details' => ['type' => 'string', 'length' => 255, 'null' => false],
		'details_nullable' => ['type' => 'string', 'length' => 255, 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * @var array
	 */
	public $records = [
	];

}
