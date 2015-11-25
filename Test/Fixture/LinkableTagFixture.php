<?php

class LinkableTagFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'name'		=> ['type' => 'string', 'length' => 255, 'null' => false],
		'parent_id'		=> ['type' => 'integer']
	];

	public $records = [
		['id' => 1, 'name' => 'General', 'parent_id' => null],
		['id' => 2, 'name' => 'Test I', 'parent_id' => 1],
		['id' => 3, 'name' => 'Test II', 'parent_id' => null],
		['id' => 4, 'name' => 'Test III', 'parent_id' => null]
	];
}
