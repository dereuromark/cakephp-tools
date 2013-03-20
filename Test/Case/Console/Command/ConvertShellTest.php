<?php

App::uses('ConvertShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class ConvertShellTest extends MyCakeTestCase {

	public $ConvertShell;

	public function setUp() {
		parent::setUp();

		$this->ConvertShell = new ConvertShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->ConvertShell));
		$this->assertInstanceOf('ConvertShell', $this->ConvertShell);
	}

	//TODO
}
