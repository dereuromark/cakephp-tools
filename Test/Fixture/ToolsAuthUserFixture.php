<?php

/**
 * ToolsUser Fixture
 */
class ToolsAuthUserFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'username' => ['type' => 'string', 'null' => false],
		'email' => ['type' => 'string', 'null' => false],
		'password' => ['type' => 'string', 'null' => false],
		'role_id' => ['type' => 'integer', 'null' => true],
	];

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => 1,
			'username' => 'User 1',
			'email' => 'myemail@example.com',
			'password' => '',
			'role_id' => 1
		]
	];

}
