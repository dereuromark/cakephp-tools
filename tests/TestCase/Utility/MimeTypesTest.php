<?php

namespace Tools\Test\TestCase\Utility;

use Shim\TestSuite\TestCase;
use Tools\Utility\MimeTypes;

class MimeTypesTest extends TestCase {

	/**
	 * @var \Tools\Utility\MimeTypes
	 */
	protected $MimeTypes;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->MimeTypes = new MimeTypes();
	}

	/**
	 * @return void
	 */
	public function testAll() {
		$res = $this->MimeTypes->all();
		$this->assertTrue(is_array($res) && count($res) > 100);
	}

	/**
	 * @return void
	 */
	public function testSingle() {
		$res = $this->MimeTypes->getMimeType('odxs');
		$this->assertNull($res);

		$res = $this->MimeTypes->getMimeType('ods');
		$this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $res);
	}

	/**
	 * @return void
	 */
	public function testOverwrite() {
		$res = $this->MimeTypes->getMimeType('ics');
		$this->assertEquals('application/ics', $res);
	}

	/**
	 * @return void
	 */
	public function testReverseToSingle() {
		$res = $this->MimeTypes->getMimeType('html');
		$this->assertEquals('text/html', $res);

		$res = $this->MimeTypes->getMimeType('csv');
		$this->assertEquals('text/csv', $res);
	}

	/**
	 * @return void
	 */
	public function testReverseToMultiple() {
		$res = $this->MimeTypes->getMimeType('html', false);
		$this->assertTrue(is_array($res));
		$this->assertSame(2, count($res));

		$res = $this->MimeTypes->getMimeType('csv', false);
		$this->assertTrue(is_array($res)); //  && count($res) > 2
		$this->assertSame(2, count($res));
	}

	/**
	 * @return void
	 */
	public function test(): void {
		$result = $this->MimeTypes->mapType('application/pdf');
		$this->assertSame('pdf', $result);

		$result = $this->MimeTypes->mapType('application/pdf123');
		$this->assertNull($result);

		$result = $this->MimeTypes->mapType('image/x-tiff');
		$this->assertSame('tif', $result);
	}

}
