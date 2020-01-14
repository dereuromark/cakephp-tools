<?php

namespace Tools\Test\TestCase;

use DateTime;
use Shim\TestSuite\TestCase;

class BootstrapTest extends TestCase {

	/**
	 * @return void
	 */
	public function testIsEmpty() {
		$result = isEmpty(new DateTime(date(FORMAT_DB_DATE)));
		$this->assertFalse($result);
	}

	/**
	 * @return void
	 */
	public function testStartsWith() {
		$strings = [
			[
				'auto',
				'au',
				true,
			],
			[
				'auto',
				'ut',
				false,
			],
			[
				'Auto',
				'au',
				true,
			],
			[
				'auto',
				'Ut',
				false,
			],
		];

		foreach ($strings as $string) {
			$is = startsWith($string[0], $string[1]);
			$this->assertEquals($string[2], $is);
		}

		$is = startsWith('Auto', 'aut', true);
		$this->assertEquals(false, $is);
	}

	/**
	 * @return void
	 */
	public function testEndsWith() {
		$strings = [
			[
				'auto',
				'to',
				true,
			],
			[
				'auto',
				'ut',
				false,
			],
			[
				'auto',
				'To',
				true,
			],
			[
				'auto',
				'Ut',
				false,
			],
		];

		foreach ($strings as $string) {
			$is = endsWith($string[0], $string[1]);
			$this->assertEquals($string[2], $is);
		}

		$is = endsWith('Auto', 'To', true);
		$this->assertEquals(false, $is);
	}

	/**
	 * @return void
	 */
	public function testContains() {
		$strings = [
			[
				'auto',
				'to',
				true,
			],
			[
				'auto',
				'ut',
				true,
			],
			[
				'auto',
				'To',
				true,
			],
			[
				'auto',
				'ot',
				false,
			],
		];

		foreach ($strings as $string) {
			$is = contains($string[0], $string[1]);
			$this->assertEquals($string[2], $is);
		}

		$is = contains('Auto', 'To', true);
		$this->assertEquals(false, $is);
	}

	/**
	 * @return void
	 */
	public function testEnt() {
		$result = ent('<>');
		$expected = '&lt;&gt;';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testEntDec() {
		$result = entDec('&lt;&gt;');
		$expected = '<>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testReturns() {
		$result = returns([]);
		$expected = '(array)';
		$this->assertTextContains($expected, $result);

		$foo = 1;
		$is = returns($foo);
		$this->assertSame('(int)1', $is);
	}

	/**
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
	 * @return void
	 */
	public function testExtractFileInfo() {
		$result = extractFileInfo('/some/path/to/filename.ext', 'file');
		$this->assertEquals('filename', $result);

		$result = extractFileInfo('/some/path/to/filename.x.y.z.ext', 'file');
		$this->assertEquals('filename.x.y.z', $result);
	}

}
