<?php

class LinkableUserFixture extends CakeTestFixture {

	public $fields = array(
		'id'			=> array('type' => 'integer', 'key' => 'primary'),
		'username'	=> array('type' => 'string', 'length' => 255, 'null' => false),
		'role_id' => array('type' => 'integer')
	);

	public $records = array(
		array('id' => 1, 'username' => 'CakePHP', 'role_id' => 1),
		array('id' => 2, 'username' => 'Zend', 'role_id' => 2),
		array('id' => 3, 'username' => 'Symfony', 'role_id' => 2),
		array('id' => 4, 'username' => 'CodeIgniter', 'role_id' => 3)
	);

}
