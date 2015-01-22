<?php

class BlogPostFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'title'		=> ['type' => 'string', 'length' => 255, 'null' => false],
		'user_id'	=> ['type' => 'integer'],
	];

	public $records = [
		['id' => 1, 'title' => 'Post 1', 'user_id' => 1],
		['id' => 2, 'title' => 'Post 2', 'user_id' => 2]
	];

}
