<?php

namespace Tools\Test\TestCase;

use DateTime;
use Tools\TestSuite\TestCase;

/**
 * RssViewTest
 */
class BootstrapTest extends TestCase {

	/**
	 * @return void
	 */
	public function testIsEmpty() {
		$result = isEmpty(new DateTime(date(FORMAT_DB_DATE)));
		$this->assertFalse($result);
	}

	/**
	 * BootstrapTest::testStartsWith()
	 *
	 * @return void
	 */
	public function testStartsWith() {
		$strings = [
			[
				'auto',
				'au',
				true
			],
			[
				'auto',
				'ut',
				false
			],
			[
				'Auto',
				'au',
				true
			],
			[
				'auto',
				'Ut',
				false
			],
		];

		foreach ($strings as $string) {
			$is = startsWith($string[0], $string[1]);
			//pr(returns($is). ' - expected '.returns($string[2]));
			$this->assertEquals($string[2], $is);
		}

		$is = startsWith('Auto', 'aut', true);
		$this->assertEquals(false, $is);
	}

	/**
	 * BootstrapTest::testEndsWith()
	 *
	 * @return void
	 */
	public function testEndsWith() {
		$strings = [
			[
				'auto',
				'to',
				true
			],
			[
				'auto',
				'ut',
				false
			],
			[
				'auto',
				'To',
				true
			],
			[
				'auto',
				'Ut',
				false
			],
		];

		foreach ($strings as $string) {
			$is = endsWith($string[0], $string[1]);
			//pr(returns($is). ' - expected '.returns($string[2]));
			$this->assertEquals($string[2], $is);
		}

		$is = endsWith('Auto', 'To', true);
		$this->assertEquals(false, $is);
	}

	/**
	 * BootstrapTest::testContains()
	 *
	 * @return void
	 */
	public function testContains() {
		$strings = [
			[
				'auto',
				'to',
				true
			],
			[
				'auto',
				'ut',
				true
			],
			[
				'auto',
				'To',
				true
			],
			[
				'auto',
				'ot',
				false
			],
		];

		foreach ($strings as $string) {
			$is = contains($string[0], $string[1]);
			//pr(returns($is). ' - expected '.returns($string[2]));
			$this->assertEquals($string[2], $is);
		}

		$is = contains('Auto', 'To', true);
		$this->assertEquals(false, $is);
	}

	public function testEnt() {
		//$this->assertEquals($expected, $is);
	}

	public function testEntDec() {
		//$this->assertEquals($expected, $is);
	}

	public function testReturns() {
		//$this->assertEquals($expected, $is);
	}

	/**
	 * BootstrapTest::testExtractPathInfo()
	 *
	 * @return void
	 */
	public function testExtractPathInfo() {
		$result = extractPathInfo('somefile.jpg', 'ext');
		$this->assertEquals('jpg', $result);

		$result = extractPathInfo('somefile.jpg', 'base');
		$this->assertEquals('somefile.jpg', $result);

		$result = extractPathInfo('somefile.jpg', 'file');
		$this->assertEquals('somefile', $result);

		$result = extractPathInfo('somefile.jpg?foo=bar#something', 'ext', true);
		$this->assertEquals('jpg', $result);
	}

	/**
	 * BootstrapTest::testExtractFileInfo()
	 *
	 * @return void
	 */
	public function testExtractFileInfo() {
		$result = extractFileInfo('/some/path/to/filename.ext', 'file');
		$this->assertEquals('filename', $result);

		$result = extractFileInfo('/some/path/to/filename.x.y.z.ext', 'file');
		$this->assertEquals('filename.x.y.z', $result);
	}

}
