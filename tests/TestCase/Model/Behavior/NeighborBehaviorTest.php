<?php

namespace Tools\Model\Behavior;

use Cake\ORM\TableRegistry;
use Shim\TestSuite\TestCase;
use Tools\Model\Table\Table;

class NeighborBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Tools.Stories',
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	protected $Table;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Table = TableRegistry::getTableLocator()->get('Stories');
		$this->Table->addBehavior('Tools.Neighbor');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		TableRegistry::clear();

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testNeighbors() {
		$id = 2;

		$result = $this->Table->neighbors($id);

		$this->assertEquals('Second', $result['prev']['title']);
		$this->assertEquals('Forth', $result['next']['title']);
	}

	/**
	 * @return void
	 */
	public function testNeighborsReverse() {
		$id = 2;

		$result = $this->Table->neighbors($id, ['reverse' => true]);

		$this->assertEquals('Forth', $result['prev']['title']);
		$this->assertEquals('Second', $result['next']['title']);
	}

	/**
	 * @return void
	 */
	public function testNeighborsCustomSortField() {
		$id = 2;

		$result = $this->Table->neighbors($id, ['sortField' => 'sort']);

		$this->assertEquals('Second', $result['prev']['title']);
		$this->assertEquals('First', $result['next']['title']);
	}

	/**
	 * @return void
	 */
	public function testNeighborsCustomFields() {
		$id = 2;

		$result = $this->Table->neighbors($id, ['sortField' => 'sort', 'fields' => ['title']]);
		$this->assertEquals(['title' => 'Second'], $result['prev']->toArray());
		$this->assertEquals(['title' => 'First'], $result['next']->toArray());
	}

	/**
	 * @return void
	 */
	public function testNeighborsStart() {
		$id = 1;

		$result = $this->Table->neighbors($id, ['sortField' => 'id']);

		$this->assertNull($result['prev']);
		$this->assertEquals('Third', $result['next']['title']);
	}

	/**
	 * @return void
	 */
	public function testNeighborsEnd() {
		$id = 4;

		$result = $this->Table->neighbors($id);
		$this->assertEquals('Third', $result['prev']['title']);
		$this->assertNull($result['next']);
	}

}
