<?php

App::uses('CodeShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CodeShellTest extends MyCakeTestCase {

	public $CodeShell;

	public function setUp() {
		parent::setUp();

		$this->CodeShell = new CodeShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->CodeShell));
		$this->assertInstanceOf('CodeShell', $this->CodeShell);
	}

	//TODO
}
