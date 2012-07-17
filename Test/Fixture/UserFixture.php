<?php

class UserFixture extends CakeTestFixture {

	 
	public $fields = array(
		'id'			=> array('type' => 'integer', 'key' => 'primary'),
		'username'	=> array('type' => 'string', 'length' => 255, 'null' => false)
	);
	
	public $records = array(
		array('id' => 1, 'username' => 'CakePHP'),
		array('id' => 2, 'username' => 'Zend'),
		array('id' => 3, 'username' => 'Symfony'),
		array('id' => 4, 'username' => 'CodeIgniter')
	);
}
