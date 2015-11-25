<?php

class LinkableProfileFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'user_id'	=> ['type' => 'integer'],
		'biography'	=> ['type' => 'string', 'length' => 255, 'null' => false]
	];

	public $records = [
		['id' => 1, 'user_id' => 1, 'biography' => 'CakePHP is a rapid development framework for PHP that provides an extensible architecture for developing, maintaining, and deploying applications.'],
		['id' => 2, 'user_id' => 2, 'biography' => ''],
		['id' => 3, 'user_id' => 3, 'biography' => ''],
		['id' => 4, 'user_id' => 4, 'biography' => '']
	];
}
