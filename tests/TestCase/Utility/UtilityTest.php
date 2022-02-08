<?php

namespace Tools\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use RuntimeException;
use Shim\TestSuite\TestCase;
use Tools\Utility\Utility;

/**
 * @coversDefaultClass \Tools\Utility\Utility
 */
class UtilityTest extends TestCase {

	/**
	 * @return void
	 */
	public function testNotBlank() {
		$res = Utility::notBlank('a');
		$this->assertTrue($res);

		$res = Utility::notBlank(2);
		$this->assertTrue($res);

		$res = Utility::notBlank(0);
		$this->assertTrue($res);

		$res = Utility::notBlank('0');
		$this->assertTrue($res);

		$res = Utility::notBlank(null);
		$this->assertFalse($res);

		$res = Utility::notBlank(false);
		$this->assertFalse($res);

		$res = Utility::notBlank('');
		$this->assertFalse($res);

		$res = Utility::notBlank([]);
		$this->assertFalse($res);
	}

	/**
	 * @covers ::inArray
	 * @return void
	 */
	public function testInArray() {
		$res = Utility::inArray(2, [1, 2, 3]);
		$this->assertTrue($res);

		$res = Utility::inArray(4, [1, 2, 7]);
		$this->assertFalse($res);

		$res = Utility::inArray('2', [1, 2, 3]);
		$this->assertTrue($res);

		$res = Utility::inArray(2, ['1x', '2x', '3x']);
		$this->assertFalse($res);

		$res = Utility::inArray('3x', ['1x', '2x', '3x']);
		$this->assertTrue($res);

		$res = Utility::inArray(3, ['1', '2', '3']);
		$this->assertTrue($res);

		$res = Utility::inArray('2x', [1, 2, 3]);
		$this->assertFalse($res);
	}

	/**
	 * @return void
	 */
	public function testTokenize() {
		$res = Utility::tokenize('');
		$this->assertSame([], $res);

		$res = Utility::tokenize('some');
		$this->assertSame(['some'], $res);

		$res = Utility::tokenize('some, thing');
		$this->assertSame(['some', 'thing'], $res);

		$res = Utility::tokenize(',some,,, ,, thing,');
		$this->assertSame(['some', 'thing'], array_values($res));
	}

	/**
	 * @covers ::pregMatch
	 * @return void
	 */
	public function testPregMatch() {
		$string = '<abc>';
		preg_match('/\<(\w+)\>/', $string, $matches);
		$this->assertSame([$string, 'abc'], $matches);

		$matches = Utility::pregMatch('/\<(\w+)\>/', $string);
		$this->assertSame([$string, 'abc'], $matches);

		$string = '<äöü>';
		preg_match('/\<(.+)\>/', $string, $matches);
		$this->assertSame([$string, 'äöü'], $matches);

		$matches = Utility::pregMatch('/\<(.+)\>/', $string);
		$this->assertSame([$string, 'äöü'], $matches);

		$string = 'D-81245 München';
		preg_match('/(*UTF8)([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string, $matches);
		$expected = [
			$string,
			'D',
			'81245',
			'München',
		];
		$this->assertSame($expected, $matches);

		// we dont need the utf8 hack:
		$matches = Utility::pregMatch('/([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string);
		$this->assertSame($expected, $matches);
	}

	/**
	 * @covers ::pregMatch
	 * @return void
	 */
	public function testPregMatchWithPatternEscape() {
		$string = 'http://www.example.com/s?q=php.net+docs';
		$res = preg_quote($string);
		$this->assertSame('http\://www\.example\.com/s\?q\=php\.net\+docs', $res);

		$string = 'http://www.example.com/s?q=php.net+docs';
		$res = preg_quote($string, '/');
		$this->assertSame('http\:\/\/www\.example\.com\/s\?q\=php\.net\+docs', $res);

		$res = '/a\s*' . $res . '\s*z/i';
		$string = 'a ' . $string . ' z';
		$matches = Utility::pregMatch($res, $string);
		$expected = [$string];
		$this->assertSame($expected, $matches);
	}

	/**
	 * @covers ::pregMatchAll
	 * @return void
	 */
	public function testPregMatchAll() {
		$string = 'D-81245 München';
		preg_match_all('/(*UTF8)([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string, $matches, PREG_SET_ORDER);
		$expected = [
			[
				$string,
				'D',
				'81245',
				'München',
			],
		];
		$this->assertSame($expected, $matches);

		// we dont need the utf8 hack:
		$matches = Utility::pregMatchAll('/([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string);
		$this->assertSame($expected, $matches);
	}

	/**
	 * @covers ::strSplit
	 * @return void
	 */
	public function testStrSplit() {
		$res = str_split('some äöü string', 7);
		$expected = ['some äö', 'ü strin', 'g'];
		$this->assertNotSame($expected, $res);

		$res = Utility::strSplit('some äöü string', 7);
		$this->assertSame($expected, $res);
	}

	/**
	 * @covers ::typeCast
	 * @return void
	 */
	public function testTypeCast() {
		$res = Utility::typeCast(2, 'string');
		$this->assertNotSame(2, $res);
		$this->assertSame('2', $res);
	}

	/**
	 * @covers ::getClientIp
	 * @return void
	 */
	public function testGetClientIp() {
		$res = Utility::getClientIp();
		$this->assertSame((string)env('REMOTE_ADDR'), $res);
	}

	/**
	 * @covers ::fileExists
	 * @return void
	 */
	public function testFileExists() {
		$res = Utility::fileExists('https://raw.githubusercontent.com/dereuromark/cakephp-tools/master/docs/README.md');
		$this->assertTrue($res);

		$res = Utility::fileExists(Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.jpg');
		$this->assertTrue($res);

		$res = Utility::fileExists('https://raw.githubusercontent.com/dereuromark/cakephp-tools/master/docs/README_invalid.md');
		$this->assertFalse($res);

		$res = Utility::fileExists(Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'fooooo.jpg');
		$this->assertFalse($res);
	}

	/**
	 * @covers ::urlExists
	 * @return void
	 */
	public function testUrlExists() {
		$res = Utility::urlExists('https://www.spiegel.de');
		$this->assertTrue($res);

		$res = Utility::urlExists('https://www.spiegel.de/some/inexistent.img');
		$this->assertFalse($res);
	}

	/**
	 * @covers ::getReferer
	 * @return void
	 */
	public function testGetReferer() {
		Configure::write('App.fullBaseUrl', 'http://foo.bar');

		$res = Utility::getReferer();
		$this->assertSame(env('HTTP_REFERER'), $res);

		$res = Utility::getReferer(true);
		$this->assertSame(env('HTTP_REFERER'), $res);

		$_SERVER['HTTP_REFERER'] = '/foo/bar';
		$res = Utility::getReferer(true);
		$base = Configure::read('App.fullBaseUrl');

		$this->assertSame($base . env('HTTP_REFERER'), $res);
	}

	/**
	 * @covers ::getHeaderFromUrl
	 * @return void
	 */
	public function testGetHeaderFromUrl() {
		$res = Utility::getHeaderFromUrl('http://www.spiegel.de');
		$this->assertTrue(is_array($res) && count($res) > 1);
		//$this->assertSame('HTTP/1.0 200 OK', $res[0]);
	}

	/**
	 * @covers ::autoPrefixUrl
	 * @return void
	 */
	public function testAutoPrefixUrl() {
		$res = Utility::autoPrefixUrl('www.spiegel.de');
		$this->assertSame('http://www.spiegel.de', $res);
	}

	/**
	 * @covers ::cleanUrl
	 * @return void
	 */
	public function testCleanUrl() {
		$res = Utility::cleanUrl('www.spiegel.de');
		$this->assertSame('http://www.spiegel.de', $res);

		$res = Utility::cleanUrl('http://');
		$this->assertSame('', $res);

		$res = Utility::cleanUrl('http://www');
		$this->assertSame('', $res);

		$res = Utility::cleanUrl('spiegel.de');
		$this->assertSame('http://spiegel.de', $res);

		$res = Utility::cleanUrl('spiegel.de', true);
		$this->assertSame('https://www.spiegel.de', $res);
	}

	/**
	 * @return void
	 */
	public function testStripUrl() {
		$urls = [
			'http://www.cakephp.org/bla/bla' => 'www.cakephp.org/bla/bla',
			'www.cakephp.org' => 'www.cakephp.org',
			'https://spiegel.de' => 'spiegel.de',
			'ftp://xyz' => 'ftp://xyz',
		];

		foreach ($urls as $url => $expected) {
			$is = Utility::stripProtocol($url);
			$this->assertEquals($expected, $is, $url);
		}
	}

	/**
	 * @covers ::trimDeep
	 * @return void
	 */
	public function testDeep() {
		$is = [
			'f some',
			'e 49r ' => 'rf r ',
			'er' => [['ee' => ['rr ' => ' tt ', 'empty' => null]]],
			'bsh' => 1,
			'bkd' => '1',
			'bol' => true,
			'bl' => 'true',
			'flt' => 3.14,
			'fl' => '3.14',
		];
		$expected = [
			'f some',
			'e 49r ' => 'rf r',
			'er' => [['ee' => ['rr ' => 'tt', 'empty' => null]]],
			'bsh' => 1,
			'bkd' => '1',
			'bol' => true,
			'bl' => 'true',
			'flt' => 3.14,
			'fl' => '3.14',
		];

		$res = Utility::trimDeep($is);
		$this->assertSame($expected, $res);
	}

	/**
	 * @covers ::trimDeep
	 * @return void
	 */
	public function testDeepTransformNullToString() {
		$is = [
			'f some',
			'e 49r ' => 'rf r ',
			'er' => [['ee' => ['rr ' => ' tt ', 'empty' => null]]],
		];
		$expected = [
			'f some',
			'e 49r ' => 'rf r',
			'er' => [['ee' => ['rr ' => 'tt', 'empty' => '']]],
		];

		$res = Utility::trimDeep($is, true);
		$this->assertSame($expected, $res);
	}

	/**
	 * //TODO
	 *
	 * @return void
	 */
	public function _testDeepFunction() {
		$is = [
			'f some',
			'e 49r ' => 'rf r ',
			'er' => [['ee' => ['rr ' => ' tt ']]],
		];
		$expected = [
			'f some',
			'e 49r ' => 'rf r',
			'er' => [['ee' => ['rr ' => 'tt']]],
		];

		$result = Utility::deep('trim', $is);
		$this->assertSame($expected, $result);
	}

	/**
	 * TestCountDim method
	 *
	 * @return void
	 */
	public function testCountDim() {
		$data = ['one', '2', 'three'];
		$result = Utility::countDim($data);
		$this->assertSame(1, $result);

		$data = ['1' => '1.1', '2', '3'];
		$result = Utility::countDim($data);
		$this->assertSame(1, $result);

		$data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => '3.1.1']];
		$result = Utility::countDim($data);
		$this->assertSame(2, $result);

		$data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
		$result = Utility::countDim($data);
		$this->assertSame(1, $result);

		$data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
		$result = Utility::countDim($data, true);
		$this->assertSame(2, $result);

		$data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data);
		$this->assertSame(2, $result);

		$data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data, true);
		$this->assertSame(3, $result);

		$data = ['1' => ['1.1' => '1.1.1'], ['2' => ['2.1' => ['2.1.1' => '2.1.1.1']]], '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data, true);
		$this->assertSame(4, $result);

		$data = ['1' => ['1.1' => '1.1.1'], ['2' => ['2.1' => ['2.1.1' => ['2.1.1.1']]]], '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data, true);
		$this->assertSame(5, $result);

		$data = ['1' => ['1.1' => '1.1.1'], ['2' => ['2.1' => ['2.1.1' => ['2.1.1.1' => '2.1.1.1.1']]]], '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data, true);
		$this->assertSame(5, $result);

		$set = ['1' => ['1.1' => '1.1.1'], ['2' => ['2.1' => ['2.1.1' => ['2.1.1.1' => '2.1.1.1.1']]]], '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($set, false, 0);
		$this->assertSame(2, $result);

		$result = Utility::countDim($set, true);
		$this->assertSame(5, $result);

		$data = ['one' => [null], ['null' => null], 'three' => [true, false, null]];
		$result = Utility::countDim($data, true);
		$this->assertSame(2, $result);
	}

	/**
	 * @return void
	 */
	public function testExpandList() {
		$is = [
			'Some.Deep.Value1',
			'Some.Deep.Value2',
			'Some.Even.Deeper.Nested.Value',
			'Empty.',
			'0.1.2',
			'.EmptyString',
		];
		$result = Utility::expandList($is);

		$expected = [
			'Some' => [
				'Deep' => ['Value1', 'Value2'],
				'Even' => ['Deeper' => ['Nested' => ['Value']]],
			],
			'Empty' => [''],
			'0' => ['1' => ['2']],
			'' => ['EmptyString'],
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testExpandListWithKeyLessListInvalid() {
		$is = [
			'Some',
			'ValueOnly',
		];

		$this->expectException(RuntimeException::class);

		Utility::expandList($is);
	}

	/**
	 * @return void
	 */
	public function testExpandListWithKeyLessList() {
		$is = [
			'Some',
			'Thing',
			'.EmptyString',
		];
		$result = Utility::expandList($is, '.', '');

		$expected = [
			'' => ['Some', 'Thing', 'EmptyString'],
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testFlatten() {
		$is = [
			'Some' => [
				'Deep' => ['Value1', 'Value2'],
				'Even' => ['Deeper' => ['Nested' => ['Value']]],
			],
			'Empty' => [''],
			'0' => ['1' => ['2']],
			//'ValueOnly',
			'' => ['EmptyString'],
		];
		$result = Utility::flattenList($is);

		$expected = [
			'Some.Deep.Value1',
			'Some.Deep.Value2',
			'Some.Even.Deeper.Nested.Value',
			'Empty.',
			'0.1.2',
			//'1.ValueOnly'
			'.EmptyString',
		];
		$this->assertSame($expected, $result);

		// Test integers als booleans
		$is = [
			'Some' => [
				'Deep' => [true],
				'Even' => ['Deeper' => ['Nested' => [false, true]]],
			],
			'Integer' => ['Value' => [-3]],
		];
		$result = Utility::flattenList($is);

		$expected = [
			'Some.Deep.1',
			'Some.Even.Deeper.Nested.0',
			'Some.Even.Deeper.Nested.1',
			'Integer.Value.-3',
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @covers ::arrayFlatten
	 * @return void
	 */
	public function testArrayFlattenBasic() {
		$strings = [
			'a' => ['a' => 'A'],
			'b' => ['b' => 'B', 'c' => 'C'],
			'c' => [],
			'd' => [[['z' => 'Z'], 'y' => 'Y']],
		];

		$result = Utility::arrayFlatten($strings);
		$expected = [
			'a' => 'A',
			'b' => 'B',
			'c' => 'C',
			'z' => 'Z',
			'y' => 'Y',
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test that deeper nested values overwrite higher ones.
	 *
	 * @covers ::arrayFlatten
	 * @return void
	 */
	public function testArrayFlatten() {
		$array = [
			'a' => 1,
			'b' => ['h' => false, 'c' => ['d' => ['f' => 'g', 'h' => true]]],
			'k' => 'm',
		];
		$res = Utility::arrayFlatten($array);

		$expected = [
			'a' => 1,
			'h' => true,
			'f' => 'g',
			'k' => 'm',
		];
		$this->assertSame($expected, $res);
	}

	/**
	 * @covers ::arrayFlatten
	 * @return void
	 */
	public function testArrayFlattenAndPreserveKeys() {
		$array = [
			0 => 1,
			1 => ['c' => ['d' => ['g', 'h' => true]]],
			2 => 'm',
		];
		$res = Utility::arrayFlatten($array, true);

		$expected = [
			0 => 'g',
			'h' => true,
			2 => 'm',
		];
		$this->assertSame($expected, $res);
	}

	/**
	 * @covers ::arrayShiftKeys
	 * @return void
	 */
	public function testArrayShiftKeys() {
		$array = [
			'a' => 1,
			'b' => ['c' => ['d' => ['f' => 'g', 'h' => true]]],
			'k' => 'm',
		];
		$res = Utility::arrayShiftKeys($array);

		$expected = 'a';
		$this->assertSame($expected, $res);
		$expected = [
			'b' => ['c' => ['d' => ['f' => 'g', 'h' => true]]],
			'k' => 'm',
		];
		$this->assertSame($expected, $array);
	}

	/**
	 * @covers ::returnElapsedTime
	 * @return void
	 */
	public function testTime() {
		Utility::startClock();
		time_nanosleep(0, 200000000);
		$res = Utility::returnElapsedTime();
		$this->assertTrue(round($res, 1) === 0.2);

		time_nanosleep(0, 100000000);
		$res = Utility::returnElapsedTime(8, true);
		$this->assertTrue(round($res, 1) === 0.3);

		time_nanosleep(0, 100000000);
		$res = Utility::returnElapsedTime();
		$this->assertTrue(round($res, 1) === 0.1);
	}

	/**
	 * @covers ::logicalAnd
	 * @return void
	 */
	public function testLogicalAnd() {
		$array = [
			'a' => 1,
			'b' => 1,
			'c' => 0,
			'd' => 1,
		];
		$is = Utility::logicalAnd($array);
		$this->assertFalse($is);

		$array = [
			'a' => 1,
			'b' => 1,
			'c' => 1,
			'd' => 1,
		];
		$is = Utility::logicalAnd($array);
		$this->assertTrue($is);
	}

	/**
	 * @covers ::logicalOr
	 * @return void
	 */
	public function testLogicalOr() {
		$array = [
			'a' => 0,
			'b' => 1,
			'c' => 0,
			'd' => 1,
		];
		$is = Utility::logicalOr($array);
		$this->assertTrue($is);

		$array = [
			'a' => 1,
			'b' => 1,
			'c' => 1,
			'd' => 1,
		];
		$is = Utility::logicalOr($array);
		$this->assertTrue($is);

		$array = [
			'a' => 0,
			'b' => 0,
			'c' => 0,
			'd' => 0,
		];
		$is = Utility::logicalOr($array);
		$this->assertFalse($is);
	}

}
