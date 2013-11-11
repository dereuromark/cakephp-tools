<?php

App::uses('SortableBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class SortableBehaviorTest extends MyCakeTestCase {

	public $fixtures = array('plugin.tools.role');

	public $SortableBehavior;

	public $Model;

	public function setUp() {
		parent::setUp();

		$this->SortableBehavior = new SortableBehavior();
		$this->Model = ClassRegistry::init('Role');
		$this->Model->Behaviors->load('Tools.Sortable');

		// Reset order
		$list = $this->_getList();
		$count = count($list);
		foreach ($list as $id => $order) {
			$this->Model->id = $id;
			$this->Model->saveField('sort', $count);
			$count--;
		}
	}

	public function testObject() {
		$this->assertTrue(is_object($this->SortableBehavior));
		$this->assertInstanceOf('SortableBehavior', $this->SortableBehavior);
	}

	/**
	 * SortableBehaviorTest::testBasicUp()
	 *
	 * @return void
	 */
	public function testBasicUp() {
		$list = $this->_getList();
		//debug($list);

		$positionBefore = $this->_getPosition(4);
		$this->assertSame(3, $positionBefore);

		$this->Model->moveUp(4);

		$positionAfter = $this->_getPosition(4);
		$this->assertSame(2, $positionAfter);

		$this->Model->moveUp(4);

		$positionAfter = $this->_getPosition(4);
		$this->assertSame(1, $positionAfter);

		$this->Model->moveUp(4);

		$positionAfter = $this->_getPosition(4);
		$this->assertSame(1, $positionAfter);
	}

	/**
	 * SortableBehaviorTest::testUp()
	 *
	 * @return void
	 */
	public function testUp() {
		$list = $this->_getList();
		//debug($list);

		$positionBefore = $this->_getPosition(4);
		$this->assertSame(3, $positionBefore);

		$this->Model->moveUp(4, 2);

		$positionAfter = $this->_getPosition(4);
		$this->assertSame(1, $positionAfter);
	}

	/**
	 * SortableBehaviorTest::testBasicDown()
	 *
	 * @return void
	 */
	public function testBasicDown() {
		$positionBefore = $this->_getPosition(2);
		$this->assertSame(2, $positionBefore);

		$this->Model->moveDown(2);

		$positionAfter = $this->_getPosition(2);
		$this->assertSame(3, $positionAfter);

		$this->Model->moveDown(2);

		$positionAfter = $this->_getPosition(2);
		$this->assertSame(4, $positionAfter);

		$this->Model->moveDown(2);

		$positionAfter = $this->_getPosition(2);
		$this->assertSame(4, $positionAfter);
	}

	/**
	 * SortableBehaviorTest::testDown()
	 *
	 * @return void
	 */
	public function testDown() {
		$positionBefore = $this->_getPosition(2);
		$this->assertSame(2, $positionBefore);

		$this->Model->moveDown(2, 3);

		$positionAfter = $this->_getPosition(2);
		$this->assertSame(4, $positionAfter);
	}

	/**
	 * SortableBehaviorTest::testAddNew()
	 *
	 * @return void
	 */
	public function testAddNew() {
		$this->Model->create();
		$data = array(
			'name' => 'new'
		);
		$this->Model->save($data);
		$id = $this->Model->id;
		$list = $this->_getList();
		$position = $this->_getPosition($id);
		$this->assertSame(count($list), $position);
	}

	/**
	 * Get 1-based position in the list.
	 *
	 * @param mixed $id
	 * @param mixed $list
	 * @return int Position or null if not found
	 */
	protected function _getPosition($id, $list = array()) {
		if (!$list) {
			$list = $this->_getList();
		}
		$count = 0;
		$position = null;
		foreach ($list as $k => $v) {
			$count++;
			if ($id == $k) {
				$position = $count;
				break;
			}
		}
		return $position;
	}

	/**
	 * SortableBehaviorTest::_getList()
	 *
	 * @return array
	 */
	protected function _getList() {
		$options = array(
			'order' => array('sort' => 'DESC'),
			'fields' => array('id', 'sort')
		);
		return $this->Model->find('list', $options);
	}

}
