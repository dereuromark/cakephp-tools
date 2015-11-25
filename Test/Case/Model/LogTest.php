<?php

App::uses('Log', 'Tools.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class LogTest extends MyCakeTestCase {

	public $Log;

	public function setUp() {
		parent::setUp();

		$this->Log = new Log();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->Log));
		$this->assertInstanceOf('Log', $this->Log);
	}

	//TODO
}
