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
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false),
		'password' => array('type' => 'string', 'null' => false),
		'role_id' => array('type' => 'integer', 'null' => false),
	);

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = array(
		array('id' => 1, 'role_id' => 1, 'password' => '123456', 'name' => 'User 1'),
		array('id' => 2, 'role_id' => 2, 'password' => '123456', 'name' => 'User 2'),
		array('id' => 3, 'role_id' => 1, 'password' => '123456', 'name' => 'User 3'),
		array('id' => 4, 'role_id' => 3, 'password' => '123456', 'name' => 'User 4')
	);

}
