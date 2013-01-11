<?php

App::uses('MimeLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MimeLibTest extends MyCakeTestCase {

	public $Mime;

	public function setUp() {
		parent::setUp();

		$this->Mime = new MimeLib();
	}


	public function testObject() {
		$this->assertTrue(is_object($this->Mime));
		$this->assertInstanceOf('MimeLib', $this->Mime);
	}

	public function testAll() {
		$res = $this->Mime->getMimeTypes();
		$this->assertTrue(is_array($res) && count($res) > 100);
	}

	public function testSingle() {
		$res = $this->Mime->getMimeType('csv');
		$this->assertTrue(is_array($res) && count($res) > 2);

		$res = $this->Mime->getMimeType('ods', true);
		$this->assertFalse($res);

		$res = $this->Mime->getMimeType('ods');
		$this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $res);
	}

	public function testSingleReverse() {
		$res = $this->Mime->getMimeType('csv');
		$this->assertTrue(is_array($res) && count($res) > 2);
	}


	/**
	 * test fake files
	 * 2010-10-22 ms
	 */
	public function testCorrectFileExtension() {
		file_put_contents(TMP.'sometest.txt', 'xyz');
		$is = $this->Mime->extractMimeType(TMP.'sometest.txt');
		pr($is);
		$this->assertEquals($is, 'text/plain');
	}

	/**
	 * test fake files
	 * 2010-10-22 ms
	 */
	public function testWrongFileExtension() {
		file_put_contents(TMP.'sometest.zip', 'xyz');
		$is = $this->Mime->extractMimeType(TMP.'sometest.zip');
		pr($is);
		$this->assertEquals($is, 'text/plain');
		//Test failes? finfo_open not availaible??
	}

	/**
	 * test fake files
	 * 2010-10-22 ms
	 */
	public function testEncoding() {
		file_put_contents(TMP.'sometest.txt', 'xyz');
		$is = $this->Mime->getEncoding(TMP.'sometest.txt');
		pr($is);
		$this->assertEquals($is, 'us-ascii');

		file_put_contents(TMP.'sometest.zip', utf8_encode('xäääyz'));
		$is = $this->Mime->getEncoding(TMP.'sometest.zip');
		pr($is);
		$this->assertEquals($is, 'utf-8');

		file_put_contents(TMP.'sometest.zip', utf8_encode('xyz'));
		$is = $this->Mime->getEncoding(TMP.'sometest.zip');
		pr($is);
		$this->assertEquals($is, 'us-ascii');

		//Tests fail? finfo_open not availaible??
	}
}
