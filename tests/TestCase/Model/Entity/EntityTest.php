<?php

namespace Tools\Model\Entity;

use Tools\TestSuite\TestCase;
use Tools\Model\Entity\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class PasswordableBehaviorTest extends TestCase {

	public $fixtures = [
		'plugin.tools.tools_users', 'plugin.tools.roles',
	];

	public $Users;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Users = TableRegistry::get('ToolsUsers');
	}

	public function tearDown() {
		TableRegistry::clear();

		parent::tearDown();
	}

	/**
	 * MyModelTest::testEnum()
	 *
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
