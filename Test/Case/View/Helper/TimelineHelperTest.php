<?php

App::uses('TimelineHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');

/**
 * Timeline Helper Test Case
 */
class TimelineHelperTest extends MyCakeTestCase {

	public $Timeline;

	/**
	 * TimelineHelperTest::setUp()
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Timeline = new TimelineTestHelper(new View(null));
		$this->Timeline->Html = new HtmlHelper(new View(null));
	}

	/**
	 * @return void
	 */
	public function testAddItem() {
		$data = array(
			'start' => '',
			'content' => '',
		);
		$this->Timeline->addItem($data);
		$items = $this->Timeline->items();
		$this->assertSame(1, count($items));

		$data = array(
			array(
				'start' => '',
				'content' => '',
			),
			array(
				'start' => '',
				'content' => '',
			)
		);
		$this->Timeline->addItems($data);
		$items = $this->Timeline->items();
		$this->assertSame(3, count($items));
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Timeline);
	}

}

class TimelineTestHelper extends TimelineHelper {

	/**
	 * @return array
	 */
	public function items() {
		return $this->_items;
	}

}