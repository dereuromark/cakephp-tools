<?php

class LinkableCommentFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'user_id'	=> ['type' => 'integer'],
		'body'		=> ['type' => 'string', 'length' => 255, 'null' => false]
	];

	public $records = [
		['id' => 1, 'user_id' => 1, 'body' => 'Text'],
		['id' => 2, 'user_id' => 1, 'body' => 'Text'],
		['id' => 3, 'user_id' => 2, 'body' => 'Text'],
		['id' => 4, 'user_id' => 3, 'body' => 'Text'],
		['id' => 5, 'user_id' => 4, 'body' => 'Text']
	];
}
