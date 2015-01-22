<?php
namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class JsonableCommentsFixture extends TestFixture {

	public $fields = [
		'id' => ['type' => 'integer'],
		'comment' => ['type' => 'string', 'length' => 255, 'null' => false],
		'url' => ['type' => 'string', 'length' => 255, 'null' => false],
		'title' => ['type' => 'string', 'length' => 255, 'null' => false],
		'details' => ['type' => 'string', 'length' => 255, 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	];

	public $records = [
	];
}
