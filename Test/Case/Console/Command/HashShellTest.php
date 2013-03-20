<?php

App::uses('HashShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class HashShellTest extends MyCakeTestCase {

	public $HashShell;

	public function setUp() {
		parent::setUp();

		$this->HashShell = new HashShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->HashShell));
		$this->assertInstanceOf('HashShell', $this->HashShell);
	}

	//TODO
}
