<?php

App::uses('PhpTagShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class PhpTagShellTest extends MyCakeTestCase {

	public $PhpTagShell;

	public function setUp() {
		parent::setUp();

		$this->PhpTagShell = new PhpTagShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->PhpTagShell));
		$this->assertInstanceOf('PhpTagShell', $this->PhpTagShell);
	}

	//TODO
}
