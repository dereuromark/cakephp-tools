<?php

App::uses('EncodingShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class EncodingShellTest extends MyCakeTestCase {

	public $EncodingShell;

	public function setUp() {
		parent::setUp();

		$this->EncodingShell = new EncodingShell();
		$this->EncodingShell->initialize();
		$this->EncodingShell->startup();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->EncodingShell));
		$this->assertInstanceOf('EncodingShell', $this->EncodingShell);
	}

	public function testFolder() {
		$this->EncodingShell->params['ext'] = '';
		$this->EncodingShell->params['verbose'] = false;
		$this->EncodingShell->args[] = dirname(__FILE__);
		$this->EncodingShell->folder();
	}

}

class TestEncodingShell extends EncodingShell {

	public function found() {
		return $this->_found;
	}

}