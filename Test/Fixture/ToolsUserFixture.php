<?php

/**
 * ToolsUser Fixture
 */
class ToolsUserFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'name' => ['type' => 'string', 'null' => false],
		'password' => ['type' => 'string', 'null' => false],
		'role_id' => ['type' => 'integer', 'null' => true],
	];

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = [
		['id' => 1, 'role_id' => 1, 'password' => '123456', 'name' => 'User 1'],
		['id' => 2, 'role_id' => 2, 'password' => '123456', 'name' => 'User 2'],
		['id' => 3, 'role_id' => 1, 'password' => '123456', 'name' => 'User 3'],
		['id' => 4, 'role_id' => 3, 'password' => '123456', 'name' => 'User 4']
	];

}
