<?php

class BlogPostFixture extends CakeTestFixture {

	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'title'		=> array('type' => 'string', 'length' => 255, 'null' => false),
		'user_id'	=> array('type' => 'integer'),
	);

	public $records = array(
		array ('id' => 1, 'title' => 'Post 1', 'user_id' => 1),
		array ('id' => 2, 'title' => 'Post 2', 'user_id' => 2)
	);

}
