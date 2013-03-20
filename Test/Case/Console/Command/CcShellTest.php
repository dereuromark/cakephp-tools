<?php

App::uses('CcShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CcShellTest extends MyCakeTestCase {

	public $CcShell;

	public function setUp() {
		parent::setUp();

		$this->CcShell = new CcShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->CcShell));
		$this->assertInstanceOf('CcShell', $this->CcShell);
	}

	//TODO
}
