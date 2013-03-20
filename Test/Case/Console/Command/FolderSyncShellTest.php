<?php

App::uses('FolderSyncShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class FolderSyncShellTest extends MyCakeTestCase {

	public $FolderSyncShell;

	public function setUp() {
		parent::setUp();

		$this->FolderSyncShell = new FolderSyncShell();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->FolderSyncShell));
		$this->assertInstanceOf('FolderSyncShell', $this->FolderSyncShell);
	}

	//TODO
}
