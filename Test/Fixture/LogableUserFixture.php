<?php
class LogableUserFixture extends CakeTestFixture {

	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'name' => ['type' => 'string', 'length' => 255, 'null' => false],
		'counter' => ['type' => 'integer', 'length' => 6, 'null' => false, 'default' => 1]
	];

	public $records = [
		['id' => 66, 'name' => 'Alexander', 'counter' => 12],
		['id' => 301, 'name' => 'Steven', 'counter' => 12],
	];
}
