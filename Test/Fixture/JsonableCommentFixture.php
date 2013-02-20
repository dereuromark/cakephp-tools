<?php

class JsonableCommentFixture extends CakeTestFixture {

	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'comment'		=> array('type' => 'string', 'length' => 255, 'null' => false),
		'url'		=> array('type' => 'string', 'length' => 255, 'null' => false),
		'title'		=> array('type' => 'string', 'length' => 255, 'null' => false),
		'details'		=> array('type' => 'string', 'length' => 255, 'null' => false)
	);

	public $records = array(
	);
}
