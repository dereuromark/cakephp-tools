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
	 * No changes
	 *
	 * @return void
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
	 * No changes
	 *
	 * @return void
	 */
	public function testSubstrReplace() {
		$res = Str::substrReplace('some', 'more', 0, 0);
		$expected = 'moresome';
		$this->assertSame($expected, $res);

		$res = Str::substrReplace('some', 'more', 1, 0);
		$expected = 'smoreome';
		$this->assertSame($expected, $res);
	}

	/**
	 * No changes
	 *
	 * @return void
	 */
	public function testCount() {
		$res = Str::count('more', 'some more and more text');
		$this->assertSame(2, $res);

		$res = Str::count('more', 'some text');
		$this->assertSame(0, $res);

		$res = Str::count('more', 'some more and more text and even more text', 10, 20);
		$this->assertSame(1, $res);
	}

	/**
	 * Very strange method
	 *
	 * fixed
	 * - documented return type (mixed)
	 * - argument order
	 * - missing underscore
	 * - naming scheme
	 *
	 * @return void
	 */
	public function testStrLastChr() {
		$res = Str::lastChr('some', 'more some text');
		$expected = 'some text';
		$this->assertSame($expected, $res);

		# WTF?
		$res = Str::lastChr('some', 'more som text');
		$expected = 'som text';
		$this->assertSame($expected, $res);

		$res = Str::lastChr('xome', 'more som text');
		$expected = 'xt';
		$this->assertSame($expected, $res);

		$res = Str::lastChr('abc', 'more som text');
		$expected = false;
		$this->assertSame($expected, $res);

		$res = Str::lastChr(120, 'more som text');
		$expected = 'xt';
		$this->assertSame($expected, $res);
	}

}
