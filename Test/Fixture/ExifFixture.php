<?php

class ExifFixture extends CakeTestFixture {

	public $fields = [
		'id' => ['type' => 'integer', 'null' => false, 'default' => null, 'length' => 10, 'key' => 'primary'],
		'user_id' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 36, 'key' => 'index'],
		'title' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 30],
		'body' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 255],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => 1], 'user_id' => ['column' => 'user_id', 'unique' => 0]],
		'tableParameters' => []
	];

	public $records = [
		[
			'id' => 1,
			'user_id' => 'Lorem ipsum dolor sit amet',
			'title' => 'Lorem ipsum dolor sit amet',
			'body' => 'fdfddf',
			'value' => 1,
			'created' => '2010-05-06 16:14:00',
			'modified' => '2010-05-06 16:14:00'
		],
	];
}
