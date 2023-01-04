<?php

namespace Tools\Test\TestCase\Model\Entity;

use Shim\TestSuite\TestCase;
use Tools\Model\Entity\Entity;

class EntityTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.ToolsUsers',
		'plugin.Tools.Roles',
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	protected $Users;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Users = $this->getTableLocator()->get('ToolsUsers');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		$this->getTableLocator()->clear();

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

		$res = Entity::enum(null, $array);
		$this->assertSame($array, $res);

		$res = Entity::enum(2, $array);
		$this->assertSame('bar', $res);

		$res = Entity::enum('2', $array);
		$this->assertSame('bar', $res);

		$res = Entity::enum(3, $array);
		$this->assertNull($res);
	}

	/**
	 * @return void
	 */
	public function testEnumPartialOptions() {
		$array = [
			1 => 'foo',
			2 => 'bar',
			3 => 'yeah',
		];

		$res = Entity::enum([2, 3], $array);
		$expected = $array;
		unset($expected[1]);
		$this->assertSame($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testEnumDefaultValue() {
		$array = [
			1 => 'foo',
			2 => 'bar',
		];

		$res = Entity::enum(null, $array, false);
		$this->assertSame($array, $res);

		$res = Entity::enum(2, $array, false);
		$this->assertSame('bar', $res);

		$res = Entity::enum('2', $array, false);
		$this->assertSame('bar', $res);

		$res = Entity::enum(3, $array, false);
		$this->assertFalse($res);
	}

}
