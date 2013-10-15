<?php

App::uses('CalendarComponent', 'Tools.Controller/Component');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CalendarComponentTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->Calendar = new CalendarComponent(new ComponentCollection());
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('CalendarComponent', $this->Calendar);
	}

	public function testX() {
		//TODO
	}

}
