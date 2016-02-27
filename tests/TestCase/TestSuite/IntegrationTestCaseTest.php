<?php

namespace Tools\TestCase\TestSuite;

use Tools\TestSuite\IntegrationTestCase;

class IntegrationTestCaseTest extends IntegrationTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testFoo() {
		$this->debug('Foo');

		$x = $this->osFix("\r\n");
		$this->assertSame("\n", $x);

		$result = $this->isDebug();
		$this->assertFalse($result);
	}

}
