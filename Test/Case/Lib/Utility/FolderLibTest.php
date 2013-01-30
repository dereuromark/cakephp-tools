<?php

App::uses('FolderLib', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class FolderLibTest extends MyCakeTestCase {

	public $FolderLib;

	public function setUp() {
		$this->FolderLib = new FolderLib();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->FolderLib));
		$this->assertInstanceOf('FolderLib', $this->FolderLib);
	}

	//TODO
}
