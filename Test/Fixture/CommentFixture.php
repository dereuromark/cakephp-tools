<?php

class CommentFixture extends CakeTestFixture {

	 
	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'user_id'	=> array('type' => 'integer'),
		'body'		=> array('type' => 'string', 'length' => 255, 'null' => false)
	);
	
	public $records = array(
		array('id' => 1, 'user_id' => 1, 'body' =>  'Text'),
		array('id' => 2, 'user_id' => 1, 'body' =>  'Text'),
		array('id' => 3, 'user_id' => 2, 'body' =>  'Text'),
		array('id' => 4, 'user_id' => 3, 'body' =>  'Text'),
		array('id' => 5, 'user_id' => 4, 'body' =>  'Text')
	);
}
