<?php
namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ToolsUser Fixture
 */
class ToolsUsersFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'null' => true],
		'password' => ['type' => 'string', 'null' => true],
		'role_id' => ['type' => 'integer', 'null' => true],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = array(
		array('role_id' => 1, 'password' => '123456', 'name' => 'User 1'),
		array('role_id' => 2, 'password' => '123456', 'name' => 'User 2'),
		array('role_id' => 1, 'password' => '123456', 'name' => 'User 3'),
		array('role_id' => 3, 'password' => '123456', 'name' => 'User 4')
	);

}
