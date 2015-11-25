<?php

class GenericFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'text'		=> ['type' => 'string', 'length' => 255, 'null' => false]
	];

	public $records = [
		['id' => 1, 'text' => ''],
		['id' => 2, 'text' => ''],
		['id' => 3, 'text' => ''],
		['id' => 4, 'text' => '']
	];
}
