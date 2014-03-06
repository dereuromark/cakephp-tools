<?php

App::uses('LoremHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');

class LoremHelperTest extends MyCakeTestCase {

	/**
	 * LoremHelperTest::setUp()
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Lorem = new LoremHelper(new View(null));
	}

	/**
	 * LoremHelperTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertInstanceOf('LoremHelper', $this->Lorem);
	}

	/**
	 * LoremHelperTest::testIpsum()
	 *
	 * @return void
	 */
	public function testIpsum() {
		$is = $this->Lorem->ipsum();
		$this->assertTextContains('<p>', $is);
		$this->assertTextContains('</p>', $is);
		$this->assertTrue(strlen($is) > 50);

		$is = $this->Lorem->ipsum(6, 'w');
		$words = explode(' ', $is);
		$this->assertSame(6, count($words));
	}

}
