<?php

App::uses('CopyShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CopyShellTest extends MyCakeTestCase {

	public $CopyShell;

	public function setUp() {
		parent::setUp();

		$this->CopyShell = new CopyShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->CopyShell));
		$this->assertInstanceOf('CopyShell', $this->CopyShell);
	}

	//TODO
}
