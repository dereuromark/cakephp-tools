<?php

App::uses('ResetShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class ResetShellTest extends MyCakeTestCase {

	public $ResetShell;

	public function setUp() {
		parent::setUp();

		$this->ResetShell = new ResetShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->ResetShell));
		$this->assertInstanceOf('ResetShell', $this->ResetShell);
	}

	//TODO
}
