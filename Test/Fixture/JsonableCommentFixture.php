<?php

class JsonableCommentFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'comment'		=> ['type' => 'string', 'length' => 255, 'null' => false],
		'url'		=> ['type' => 'string', 'length' => 255, 'null' => false],
		'title'		=> ['type' => 'string', 'length' => 255, 'null' => false],
		'details'		=> ['type' => 'string', 'length' => 255, 'null' => false]
	];

	public $records = [
	];
}
