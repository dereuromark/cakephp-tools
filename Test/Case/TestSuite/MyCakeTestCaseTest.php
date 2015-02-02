<?php

App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MyCakeTestCaseTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();
	}

	/**
	 * test testAssertWithinRange()
	 *
	 * @return void
	 */
	public function testAssertWithinRange() {
		$this->assertWithinRange(21, 22, 1, 'Not within range');
		$this->assertWithinRange(21.3, 22.2, 1.0, 'Not within range');
	}

	/**
	 * test testAssertNotWithinRange()
	 *
	 * @return void
	 */
	public function testAssertNotWithinRange() {
		$this->assertNotWithinRange(21, 23, 1, 'Within range');
		$this->assertNotWithinRange(21.3, 22.2, 0.7, 'Within range');
	}

}
