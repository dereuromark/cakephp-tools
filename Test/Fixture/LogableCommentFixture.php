<?php
class LogableCommentFixture extends CakeTestFixture {

	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'content' => ['type' => 'string', 'length' => 255, 'null' => false],
	];

	public $records = [
		['id' => 1, 'content' => 'I like it'],
		['id' => 2, 'content' => 'I don\'t'],
		['id' => 3, 'content' => 'I LOVE it!'],
		['id' => 4, 'content' => 'I hate it'],

	];
}
