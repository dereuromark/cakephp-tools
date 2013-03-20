<?php

App::uses('IndexShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class IndexShellTest extends MyCakeTestCase {

	public $IndexShell;

	public function setUp() {
		parent::setUp();

		$this->IndexShell = new IndexShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->IndexShell));
		$this->assertInstanceOf('IndexShell', $this->IndexShell);
	}

	//TODO
}
