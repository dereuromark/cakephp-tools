<?php

namespace Tools\TestCase\Utility;

use Cake\Core\Plugin;
use Cake\Network\Response;
use Tools\TestSuite\TestCase;
use Tools\Utility\Mime;

class MimeTest extends TestCase {

	/**
	 * @var \Tools\Utility\Mime
	 */
	public $Mime;

	public function setUp() {
		parent::setUp();

		$this->Mime = new Mime();
	}

	public function testObject() {
		$this->assertTrue(is_object($this->Mime));
		$this->assertInstanceOf('Tools\Utility\Mime', $this->Mime);
	}

	public function testAll() {
		$res = $this->Mime->mimeTypes();
		$this->assertTrue(is_array($res) && count($res) > 100);
	}

	public function testSingle() {
		$res = $this->Mime->getMimeTypeByAlias('odxs');
		$this->assertFalse($res);

		$res = $this->Mime->getMimeTypeByAlias('ods');
		$this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $res);
	}

	public function testOverwrite() {
		$res = $this->Mime->getMimeTypeByAlias('ics');
		$this->assertEquals('application/ics', $res);
	}

	public function testReverseToSingle() {
		$res = $this->Mime->getMimeTypeByAlias('html');
		$this->assertEquals('text/html', $res);

		$res = $this->Mime->getMimeTypeByAlias('csv');
		$this->assertEquals('text/csv', $res);
	}

	public function testReverseToMultiple() {
		$res = $this->Mime->getMimeTypeByAlias('html', false);
		$this->assertTrue(is_array($res));
		$this->assertSame(2, count($res));

		$res = $this->Mime->getMimeTypeByAlias('csv', false);
		$this->assertTrue(is_array($res)); //  && count($res) > 2
		$this->assertSame(2, count($res));
	}

	/**
	 * Test fake files
	 *
	 * @return void
	 */
	public function testCorrectFileExtension() {
		file_put_contents(TMP . 'sometest.txt', 'xyz');
		$is = $this->Mime->detectMimeType(TMP . 'sometest.txt');
		//pr($is);
		$this->assertEquals($is, 'text/plain');
	}

	/**
	 * Test fake files
	 *
	 * @return void
	 */
	public function testWrongFileExtension() {
		file_put_contents(TMP . 'sometest.zip', 'xyz');
		$is = $this->Mime->detectMimeType(TMP . 'sometest.zip');
		//pr($is);
		$this->assertEquals($is, 'text/plain');
		//Test failes? finfo_open not availaible??
	}


	/**
	 * testgetMimeTypeByAlias()
	 *
	 * @return void
	 */
	public function testgetMimeTypeByAlias() {
		$res = $this->Mime->detectMimeType('http://www.spiegel.de/static/sys/v10/icons/home_v2.png');
		$this->assertEquals('image/png', $res);

		$res = $this->Mime->detectMimeType('http://www.spiegel.de/static/sys/v10/icons/home_v2_inexistent.png');
		$this->assertEquals('', $res);

		$res = $this->Mime->detectMimeType(Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.jpg');
		$this->assertEquals('image/jpeg', $res);
	}

	/**
	 * Test fake files
	 *
	 * @return void
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
		$this->TestMime = new TestMime();

		$core = $this->TestCakeResponse->getMimeTypes();
		$plugin = $this->TestMime->getMimeTypes();

		$diff = [
			'coreonly' => [],
			'pluginonly' => [],
			'modified' => []
		];
		foreach ($core as $key => $value) {
			if (!isset($plugin[$key])) {
				$diff['coreonly'][$key] = $value;
			} elseif ($value !== $plugin[$key]) {
				$diff['modified'][$key] = ['was' => $value, 'is' => $plugin[$key]];
			}
			unset($plugin[$key]);
		}
		foreach ($plugin as $key => $value) {
			$diff['pluginonly'][$key] = $value;
		}
		//$this->debug($diff);
	}

}

class TestCakeResponse extends Response {

	public function getMimeTypes() {
		return $this->_mimeTypes;
	}

}

class TestMime extends Mime {

	public function getMimeTypes($coreHasPrecedence = false) {
		return $this->_mimeTypesExt;
	}

}
