<?php

class LinkableUserFixture extends CakeTestFixture {

	public $fields = [
		'id'			=> ['type' => 'integer', 'key' => 'primary'],
		'username'	=> ['type' => 'string', 'length' => 255, 'null' => false],
		'role_id' => ['type' => 'integer']
	];

	public $records = [
		['id' => 1, 'username' => 'CakePHP', 'role_id' => 1],
		['id' => 2, 'username' => 'Zend', 'role_id' => 2],
		['id' => 3, 'username' => 'Symfony', 'role_id' => 2],
		['id' => 4, 'username' => 'CodeIgniter', 'role_id' => 3]
	];

}
