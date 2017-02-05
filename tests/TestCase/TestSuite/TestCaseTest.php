<?php

namespace Tools\Test\TestCase\TestSuite;

use Tools\TestSuite\TestCase;

class TestCaseTest extends TestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testAssertNotWithinRange() {
		$this->assertWithinRange(22, 23, 1);

		$this->assertNotWithinRange(22, 23, 0.9);
	}

}
