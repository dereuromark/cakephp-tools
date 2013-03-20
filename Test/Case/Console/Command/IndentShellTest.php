<?php

App::uses('IndentShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class IndentShellTest extends MyCakeTestCase {

	public $IndentShell;

	public function setUp() {
		parent::setUp();

		$this->IndentShell = new IndentShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->IndentShell));
		$this->assertInstanceOf('IndentShell', $this->IndentShell);
	}

	//TODO
}
