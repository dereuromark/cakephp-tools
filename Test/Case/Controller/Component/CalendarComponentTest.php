<?php

App::uses('CalendarComponent', 'Tools.Controller/Component');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CalendarComponentTest extends MyCakeTestCase {

	public function setUp() {
		$this->Calendar = new CalendarComponent(new ComponentCollection());
	}

	public function tearDown() {

	}

	public function testObject() {
		$this->assertTrue(is_a($this->Calendar, 'CalendarComponent'));
	}

	public function testX() {
		//TODO
	}

}