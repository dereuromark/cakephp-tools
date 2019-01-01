<?php

namespace Tools\Model\Entity;

use Cake\ORM\TableRegistry;
use Tools\TestSuite\TestCase;

class EntityTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.Tools.ToolsUsers', 'plugin.Tools.Roles',
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	public $Users;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Users = TableRegistry::get('ToolsUsers');
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		TableRegistry::clear();

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testEnum() {
		$array = [
			1 => 'foo',
			2 => 'bar',
		];

		$res = Entity::enum(null, $array, false);
		$this->assertEquals($array, $res);

		$res = Entity::enum(2, $array, false);
		$this->assertEquals('bar', $res);

		$res = Entity::enum('2', $array, false);
		$this->assertEquals('bar', $res);

		$res = Entity::enum(3, $array, false);
		$this->assertFalse($res);
	}

}
