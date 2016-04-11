<?php

namespace Tools\Model\Behavior;

use Cake\ORM\TableRegistry;
use Tools\Model\Table\Table;
use Tools\TestSuite\TestCase;

class NeighborBehaviorTest extends TestCase {

	/**
	 * @var \Tools\Model\Table\Table
	 */
	public $Table;

	/**
	 * @var array
	 */
	public $fixtures = ['plugin.tools.stories'];

	public function setUp() {
		parent::setUp();

		//Configure::write('App.namespace', 'TestApp');

		$this->Table = TableRegistry::get('Stories');
		$this->Table->addBehavior('Tools.Neighbor');
	}

	public function tearDown() {
		TableRegistry::clear();

		parent::tearDown();
	}

	/**
	 * NeighborBehaviorTest::testNeighborRecords()
	 *
	 * @return void
	 */
	public function testNeighbors() {
		$id = 2;

		$result = $this->Table->neighbors($id);

		$this->assertEquals('Second', $result['prev']['title']);
		$this->assertEquals('Forth', $result['next']['title']);
	}

	/**
	 * NeighborBehaviorTest::testNeighborRecords()
	 *
	 * @return void
	 */
	public function testNeighborsReverse() {
		$id = 2;

		$result = $this->Table->neighbors($id, ['reverse' => true]);

		$this->assertEquals('Forth', $result['prev']['title']);
		$this->assertEquals('Second', $result['next']['title']);
	}

	/**
	 * NeighborBehaviorTest::testNeighborRecords()
	 *
	 * @return void
	 */
	public function testNeighborsCustomSortField() {
		$id = 2;

		$result = $this->Table->neighbors($id, ['sortField' => 'sort']);

		$this->assertEquals('Second', $result['prev']['title']);
		$this->assertEquals('First', $result['next']['title']);
	}

	/**
	 * NeighborBehaviorTest::testNeighborRecords()
	 *
	 * @return void
	 */
	public function testNeighborsCustomFields() {
		$id = 2;

		$result = $this->Table->neighbors($id, ['sortField' => 'sort', 'fields' => ['title']]);
		//debug($result);
		$this->assertEquals(['title' => 'Second'], $result['prev']->toArray());
		$this->assertEquals(['title' => 'First'], $result['next']->toArray());
	}

	/**
	 * NeighborBehaviorTest::testNeighborRecords()
	 *
	 * @return void
	 */
	public function testNeighborsStart() {
		$id = 1;

		$result = $this->Table->neighbors($id, ['sortField' => 'id']);

		$this->assertNull($result['prev']);
		$this->assertEquals('Third', $result['next']['title']);
	}

	/**
	 * NeighborBehaviorTest::testNeighborRecords()
	 *
	 * @return void
	 */
	public function testNeighborsEnd() {
		$id = 4;

		$result = $this->Table->neighbors($id);
		//debug($result);
		$this->assertEquals('Third', $result['prev']['title']);
		$this->assertNull($result['next']);
	}

}
