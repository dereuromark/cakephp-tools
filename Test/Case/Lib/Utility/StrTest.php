<?php
/**
 * Draft 0.2 for PHP argument order fix
 * 2012-04-14 ms
 */

App::uses('Str', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * @see https://bugs.php.net/bug.php?id=44794
 * 2012-04-14 ms
 */
class StrTest extends MyCakeTestCase {

	/**
	 * fixed
	 * - documented return type (mixed)
	 * - argument order
	 * - missing underscore
	 */
	public function testStrStr() {
		$res = Str::str('some', 'more some text');
		$expected = 'some text';
		$this->assertSame($expected, $res);

		$res = Str::str('some', 'more som text');
		$expected = false;
		$this->assertSame($expected, $res);
	}

	/**
	 * no changes
	 */
	public function testStrReplace() {
		$res = Str::replace('some', 'more', 'in some text');
		$expected = 'in more text';
		$this->assertSame($expected, $res);

		$count = 0;
		$res = Str::replace('some', 'more', 'in some text', $count);
		$this->assertSame($expected, $res);
		$this->assertSame(1, $count);
	}

	/**
	 * fixed
	 * - documented return type (mixed)
	 * - argument order
	 * - missing underscore
	 *
	 * very strange method
	 */
	public function testStrRchr() {
		$res = Str::rChr('some', 'more some text');
		$expected = 'some text';
		$this->assertSame($expected, $res);

		# WTF?
		$res = Str::rChr('some', 'more som text');
		$expected = 'som text';
		$this->assertSame($expected, $res);

		$res = Str::rChr('xome', 'more som text');
		$expected = 'xt';
		$this->assertSame($expected, $res);

		$res = Str::rChr('abc', 'more som text');
		$expected = false;
		$this->assertSame($expected, $res);

		$res = Str::rChr(120, 'more som text');
		$expected = 'xt';
		$this->assertSame($expected, $res);
	}

}
