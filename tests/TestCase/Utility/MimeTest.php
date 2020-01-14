<?php

namespace Tools\Test\TestCase\Utility;

use Cake\Core\Plugin;
use Shim\TestSuite\TestCase;
use TestApp\Http\TestResponse;
use TestApp\Utility\TestMime;

class MimeTest extends TestCase {

	/**
	 * @var \Tools\Utility\Mime
	 */
	protected $Mime;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Mime = new TestMime();
	}

	/**
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->Mime));
		$this->assertInstanceOf('Tools\Utility\Mime', $this->Mime);
	}

	/**
	 * @return void
	 */
	public function testAll() {
		$res = $this->Mime->mimeTypes();
		$this->assertTrue(is_array($res) && count($res) > 100);
	}

	/**
	 * @return void
	 */
	public function testSingle() {
		$res = $this->Mime->getMimeTypeByAlias('odxs');
		$this->assertFalse($res);

		$res = $this->Mime->getMimeTypeByAlias('ods');
		$this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $res);
	}

	/**
	 * @return void
	 */
	public function testOverwrite() {
		$res = $this->Mime->getMimeTypeByAlias('ics');
		$this->assertEquals('application/ics', $res);
	}

	/**
	 * @return void
	 */
	public function testReverseToSingle() {
		$res = $this->Mime->getMimeTypeByAlias('html');
		$this->assertEquals('text/html', $res);

		$res = $this->Mime->getMimeTypeByAlias('csv');
		$this->assertEquals('text/csv', $res);
	}

	/**
	 * @return void
	 */
	public function testReverseToMultiple() {
		$res = $this->Mime->getMimeTypeByAlias('html', false);
		$this->assertTrue(is_array($res));
		$this->assertSame(2, count($res));

		$res = $this->Mime->getMimeTypeByAlias('csv', false);
		$this->assertTrue(is_array($res)); //  && count($res) > 2
		$this->assertSame(2, count($res));
	}

	/**
	 * @return void
	 */
	public function testCorrectFileExtension() {
		file_put_contents(TMP . 'sometest.txt', 'xyz');
		$is = $this->Mime->detectMimeType(TMP . 'sometest.txt');
		//pr($is);
		$this->assertEquals($is, 'text/plain');
	}

	/**
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
	 * @return void
	 */
	public function testgetMimeTypeByAlias() {
		$res = $this->Mime->detectMimeType('https://raw.githubusercontent.com/dereuromark/cakephp-ide-helper/master/docs/img/code_completion.png');
		$this->assertEquals('image/png', $res);

		$res = $this->Mime->detectMimeType('https://raw.githubusercontent.com/dereuromark/cakephp-ide-helper/master/docs/img/code_completion_invalid.png');
		$this->assertEquals('', $res);

		$res = $this->Mime->detectMimeType(Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.jpg');
		$this->assertEquals('image/jpeg', $res);
	}

	/**
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
	 * @return void
	 */
	public function testDifferenceBetweenPluginAndCore() {
		$this->TestCakeResponse = new TestResponse();
		$this->TestMime = new TestMime();

		$core = $this->TestCakeResponse->getMimeTypes();
		$plugin = $this->TestMime->getMimeTypes();

		$diff = [
			'coreonly' => [],
			'pluginonly' => [],
			'modified' => [],
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

		$this->assertNotEmpty($diff['coreonly']);
		$this->assertNotEmpty($diff['pluginonly']);
		$this->assertNotEmpty($diff['modified']);
	}

}
