<?php
App::uses('ZipLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class ZipLibTest extends MyCakeTestCase {

	public $ZipLib;

	public function setUp() {
		parent::setUp();

		$this->ZipLib = new ZipLib();

		foreach ($this->testFiles as $file => $content) {
			$this->_createTestFile($file, $content);
		}
	}

	public function tearDown() {
		$this->ZipLib->close();

		foreach ($this->testFiles as $file => $content) {
			unlink(TMP . $file);
		}
		$this->_rrmdir(TMP . 'xyz');
		$this->_rrmdir(TMP . 'xyz2');

		parent::tearDown();
	}

	public function testOpen() {
		$is = $this->ZipLib->open(TMP . 'test_one_folder.zip');
		$this->assertTrue($is);

		$is = $this->ZipLib->getError();
		$this->assertTrue(empty($is));

		$is = $this->ZipLib->open(TMP . 'test_invalid.zip');
		$this->assertFalse($is);

		$is = $this->ZipLib->getError();
		$this->out($is);
		$this->assertTrue(!empty($is));

		$is = $this->ZipLib->getError(true);
		$this->out($is);
		$this->assertTrue(!empty($is));
	}

	public function testFilename() {
		$is = $this->ZipLib->open(TMP . 'test_one_folder.zip');
		$this->assertEquals($this->ZipLib->filename(), 'test_one_folder.zip');
	}

	public function testSize() {
		$this->ZipLib->open(TMP . 'test_folder_and_file.zip');
		$is = $this->ZipLib->size();
		$this->out($is);
		$this->assertEquals(5, $is);
	}

	public function testNum() {
		$this->ZipLib->open(TMP . 'test_one_folder.zip');
		$res = $this->ZipLib->numFiles();
		$this->assertEquals(1, $res);

		$this->ZipLib->open(TMP . 'test_folder_and_file.zip');
		$res = $this->ZipLib->numFiles();
		$this->assertEquals(2, $res);
	}

	public function testUnzip() {

		$this->ZipLib->open(TMP . 'test_folder_and_file.zip');
		$res = $this->ZipLib->unzip(TMP . 'xyz');
		$this->assertTrue($res);
		$this->assertTrue(file_exists(TMP . 'xyz' . DS . 'folder' . DS . 'file.txt'));
		$this->assertSame('test', file_get_contents(TMP . 'xyz' . DS . 'folder' . DS . 'file.txt'));

		$this->ZipLib->open(TMP . 'test_folder_and_file.zip');
		$res = $this->ZipLib->unzip(TMP . 'xyz2', true);
		$this->assertTrue($res);
		$this->assertTrue(file_exists(TMP . 'xyz2' . DS . 'e.txt'));
		$this->assertTrue(file_exists(TMP . 'xyz2' . DS . 'file.txt'));
		$this->assertSame('test', file_get_contents(TMP . 'xyz2' . DS . 'file.txt'));
	}

	/**
	 * Helper method to recursively remove a directory
	 */
	protected function _rrmdir($dir) {
		if (!is_dir($dir)) {
			return;
		}
		foreach (glob($dir . '/*') as $file) {
			if (is_dir($file)) {
				$this->_rrmdir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dir);
	}

	/**
	 * Helper method to create zip test files
	 */
	public function _createTestFile($file, $content = null) {
		if ($content === null) {
			$content = $this->testFiles[$file];
		}
		file_put_contents(TMP . $file, base64_decode($content));
	}

	public $testFiles = array(
		'test_one_folder.zip' => 'UEsDBBQAAAAAABIdFz2DFtyMAQAAAAEAAAAFAAAAZS50eHR4UEsBAhQAFAAAAAAAEh0XPYMW3IwBAAAAAQAAAAUAAAAAAAAAAQAgAAAAAAAAAGUudHh0UEsFBgAAAAABAAEAMwAAACQAAAAAAA==',
		'test_folder_and_file.zip' => 'UEsDBBQAAAAAABIdFz2DFtyMAQAAAAEAAAAFAAAAZS50eHR4UEsDBBQAAAAAAEsjFz0Mfn/YBAAAAAQAAAAPAAAAZm9sZGVyL2ZpbGUudHh0dGVzdFBLAQIUABQAAAAAABIdFz2DFtyMAQAAAAEAAAAFAAAAAAAAAAEAIAAAAAAAAABlLnR4dFBLAQIUABQAAAAAAEsjFz0Mfn/YBAAAAAQAAAAPAAAAAAAAAAEAIAAAACQAAABmb2xkZXIvZmlsZS50eHRQSwUGAAAAAAIAAgBwAAAAVQAAAAAA',
		'test_invalid.zip' => 'UEsDBBQAAAAAABIdFz2DFtyMAQAAAAEAAAAFAAAsS50eHR4UEsDBBQAAAAAAEsjFz0Mfn/YBAAAAAQAAAAPAAAAZm9sZGVyL2ZpbGUudHh0dGVzdFBLAQIUABQAAAAAABIdFz2DFtyMAQAAAAEAAAAFAAAAAAAAAAEAIAAAAAAAAABlLnR4dFBLAQIUABQAAAAAAEsjFz0Mfn/YBAAAAAQAAAAPAAAAAAAAAAEAIAAAACQAAABmb2xkZXIvZmlsZS50eHRQSwUGAAAAAAIAAgBwAAAAVQAAAAAA',
	);

}
