<?php

App::import('Component', 'Tools.Calendar');
App::uses('MyCakeTestCase', 'Tools.Lib');

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