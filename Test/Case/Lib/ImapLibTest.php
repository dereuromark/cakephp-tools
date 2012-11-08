<?php

App::uses('ImapLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class ImapLibTest extends MyCakeTestCase {

	public function setUp() {
		$this->ImapLib = new ImapLib();
	}

	public function tearDown() {

	}

	public function testObject() {
		$this->assertTrue(is_a($this->ImapLib, 'ImapLib'));
	}

	public function testX() {
		//TODO
	}

}