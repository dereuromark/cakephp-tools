<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\View\View;
use DateTime;
use Tools\TestSuite\TestCase;
use TestApp\View\Helper\TimelineHelper;

/**
 * Timeline Helper Test Case
 */
class TimelineHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\TimelineHelper|\TestApp\View\Helper\TimelineHelper
	 */
	public $Timeline;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Timeline = new TimelineHelper(new View(null));
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

		$this->Timeline->finalize();
		$result = $this->Timeline->getView()->fetch('script');
		$this->assertContains('\'start\': new Date(', $result);
	}

	/**
	 * @return void
	 */
	public function testFinalizeReturnScript() {
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

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Timeline);
	}

}
