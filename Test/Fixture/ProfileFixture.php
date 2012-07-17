<?php

class ProfileFixture extends CakeTestFixture {

	 
	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'user_id'	=> array('type' => 'integer'),
		'biography'	=> array('type' => 'string', 'length' => 255, 'null' => false)
	);
	
	public $records = array(
		array ('id' => 1, 'user_id' => 1, 'biography' => 'CakePHP is a rapid development framework for PHP that provides an extensible architecture for developing, maintaining, and deploying applications.'),
		array ('id' => 2, 'user_id' => 2, 'biography' => ''),
		array ('id' => 3, 'user_id' => 3, 'biography' => ''),
		array ('id' => 4, 'user_id' => 4, 'biography' => '')
	);
}
