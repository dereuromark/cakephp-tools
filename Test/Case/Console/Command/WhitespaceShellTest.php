<?php

App::uses('WhitespaceShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class WhitespaceShellTest extends MyCakeTestCase {

	public $WhitespaceShell;

	public function setUp() {
		$this->WhitespaceShell = new WhitespaceShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->WhitespaceShell));
		$this->assertInstanceOf('WhitespaceShell', $this->WhitespaceShell);
	}

	//TODO
}
