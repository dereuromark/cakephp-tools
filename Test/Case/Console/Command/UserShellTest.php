<?php

App::uses('UserShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class UserShellTest extends MyCakeTestCase {

	public $UserShell;

	public function setUp() {
		parent::setUp();

		$this->UserShell = new UserShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->UserShell));
		$this->assertInstanceOf('UserShell', $this->UserShell);
	}

	//TODO
}
