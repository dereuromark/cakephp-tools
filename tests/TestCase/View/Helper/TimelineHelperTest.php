<?php

namespace Tools\TestCase\View\Helper;

use Cake\View\View;
use DateTime;
use Tools\TestSuite\TestCase;
use Tools\View\Helper\TimelineHelper;

/**
 * Timeline Helper Test Case
 */
class TimelineHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\TimelineHelper
	 */
	public $Timeline;

	/**
	 * TimelineHelperTest::setUp()
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Timeline = new TimelineTestHelper(new View(null));
	}

	/**
	 * @return void
	 */
	public function testAddItem() {
		$data = [
			'start' => null,
			'content' => '',
		];
		$this->Timeline->addItem($data);
		$items = $this->Timeline->items();
		$this->assertSame(1, count($items));

		$data = [
			[
				'start' => null,
				'content' => '',
			],
			[
				'start' => null,
				'content' => '',
			]
		];
		$this->Timeline->addItems($data);
		$items = $this->Timeline->items();
		$this->assertSame(3, count($items));
	}

	/**
	 * @return void
	 */
	public function testFinalize() {
		$this->testAddItem();
		$data = [
			'start' => new DateTime(),
			'content' => '',
		];
		$this->Timeline->addItem($data);
		$data = [
			'start' => new DateTime(date(FORMAT_DB_DATE)),
			'content' => '',
		];
		$this->Timeline->addItem($data);

		$result = $this->Timeline->finalize(true);
		$this->assertContains('\'start\': new Date(', $result);
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
