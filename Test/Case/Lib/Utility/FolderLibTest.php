<?php

App::uses('FolderLib', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class FolderLibTest extends MyCakeTestCase {

	public $FolderLib;

	public function setUp() {
		parent::setUp();

		$this->FolderLib = new FolderLib();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->FolderLib));
		$this->assertInstanceOf('FolderLib', $this->FolderLib);
	}

	/**
	 * FolderLibTest::testClear()
	 *
	 * @return void
	 */
	public function testClear() {
		$folder = TMP;
		mkdir($folder . 'x' . DS . 'y', 0770, true);
		touch($folder . 'x' . DS . 'y' . DS . 'one.txt');
		touch($folder . 'x' . DS . 'two.txt');

		$Folder = new FolderLib($folder . 'x');
		$result = $Folder->clear();
		$this->assertTrue($result);

		$this->assertTrue(is_dir($folder . 'x'));
		$this->assertFalse(is_dir($folder . 'x' . DS . 'y'));
		$this->assertFalse(is_file($folder . 'x' . DS . 'two.txt'));

		$Folder->delete($folder . 'x');
		$this->assertFalse(is_file($folder . 'x'));
	}

}
