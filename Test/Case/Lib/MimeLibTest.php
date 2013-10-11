<?php
App::uses('MimeLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('CakeResponse', 'Network');

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
		$res = $this->Mime->getMimeType('odxs');
		$this->assertFalse($res);

		$res = $this->Mime->getMimeType('ods');
		$this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $res);
	}

	public function testOverwrite() {
		$res = $this->Mime->getMimeType('ics');
		$this->assertEquals('application/ics', $res);
	}

	public function testReverseToSingle() {
		$res = $this->Mime->getMimeType('html');
		$this->assertEquals('text/html', $res);

		$res = $this->Mime->getMimeType('csv');
		$this->assertEquals('text/csv', $res);
	}

	public function testReverseToMultiple() {
		$res = $this->Mime->getMimeType('html', false);
		$this->assertTrue(is_array($res) && count($res) === 2);

		$res = $this->Mime->getMimeType('csv', false);
		$this->assertTrue(is_array($res) && count($res) > 2);
	}

	/**
	 * Test fake files
	 */
	public function testCorrectFileExtension() {
		file_put_contents(TMP . 'sometest.txt', 'xyz');
		$is = $this->Mime->extractMimeType(TMP . 'sometest.txt');
		//pr($is);
		$this->assertEquals($is, 'text/plain');
	}

	/**
	 * Test fake files
	 */
	public function testWrongFileExtension() {
		file_put_contents(TMP . 'sometest.zip', 'xyz');
		$is = $this->Mime->extractMimeType(TMP . 'sometest.zip');
		//pr($is);
		$this->assertEquals($is, 'text/plain');
		//Test failes? finfo_open not availaible??
	}

	/**
	 * Test fake files
	 */
	public function testEncoding() {
		file_put_contents(TMP . 'sometest.txt', 'xyz');
		$is = $this->Mime->getEncoding(TMP . 'sometest.txt');
		//pr($is);
		$this->assertEquals($is, 'us-ascii');

		file_put_contents(TMP . 'sometest.zip', utf8_encode('xäääyz'));
		$is = $this->Mime->getEncoding(TMP . 'sometest.zip');
		//pr($is);
		$this->assertEquals($is, 'utf-8');

		file_put_contents(TMP . 'sometest.zip', utf8_encode('xyz'));
		$is = $this->Mime->getEncoding(TMP . 'sometest.zip');
		//pr($is);
		$this->assertEquals($is, 'us-ascii');
		//Tests fail? finfo_open not availaible??
	}

	/**
	 * MimeLibTest::testDifferenceBetweenPluginAndCore()
	 *
	 * @return void
	 */
	public function testDifferenceBetweenPluginAndCore() {
		$this->TestCakeResponse = new TestCakeResponse();
		$this->TestMime = new TestMimeLib();

		$core = $this->TestCakeResponse->getMimeTypes();
		$plugin = $this->TestMime->getMimeTypes();

		$diff = array(
			'coreonly' => array(),
			'pluginonly' => array(),
			'modified' => array()
		);
		foreach ($core as $key => $value) {
			if (!isset($plugin[$key])) {
				$diff['coreonly'][$key] = $value;
			} elseif ($value !== $plugin[$key]) {
				$diff['modified'][$key] = array('was' => $value, 'is' => $plugin[$key]);
			}
			unset($plugin[$key]);
		}
		foreach ($plugin as $key => $value) {
			$diff['pluginonly'][$key] = $value;
		}
		$this->debug($diff);
	}

}

class TestCakeResponse extends CakeResponse {

	public function getMimeTypes() {
		return $this->_mimeTypes;
	}

}

class TestMimeLib extends MimeLib {

	public function getMimeTypes($coreHasPrecedence = false) {
		return $this->_mimeTypesExt;
	}

}

