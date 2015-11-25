<?php

class NewsCategoryFixture extends CakeTestFixture {

	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'name' => ['type' => 'string', 'length' => 255, 'null' => false]
	];

	public $records = [
		['id' => 1, 'name' => 'Development'],
		['id' => 2, 'name' => 'Programming'],
		['id' => 3, 'name' => 'Scripting'],
	];
}
