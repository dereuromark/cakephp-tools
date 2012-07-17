<?php

class GenericFixture extends CakeTestFixture {

	 
	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'text'		=> array('type' => 'string', 'length' => 255, 'null' => false)
	);
	
	public $records = array(
		array ('id' => 1, 'text' => ''),
		array ('id' => 2, 'text' => ''),
		array ('id' => 3, 'text' => ''),
		array ('id' => 4, 'text' => '')
	);
}
