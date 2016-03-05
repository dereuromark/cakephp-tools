<?php

namespace Tools\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Tools\TestSuite\TestCase;
use Tools\Utility\Utility;

/**
 * @coversDefaultClass \Tools\Utility\Utility
 */
class UtilityTest extends TestCase {

	/**
	 * @return void
	 */
	public function testNotEmpty() {
		$res = Utility::notEmpty('a');
		$this->assertTrue($res);

		$res = Utility::notEmpty(2);
		$this->assertTrue($res);

		$res = Utility::notEmpty(0);
		$this->assertFalse($res);

		$res = Utility::notEmpty('0');
		$this->assertTrue($res);

		$res = Utility::notEmpty(null);
		$this->assertFalse($res);

		$res = Utility::notEmpty(false);
		$this->assertFalse($res);

		$res = Utility::notEmpty('');
		$this->assertFalse($res);

		$res = Utility::notEmpty([]);
		$this->assertFalse($res);
	}

	/**
	 * UtilityTest::testInArray()
	 *
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
	 * UtilityTest::testPregMatch()
	 *
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
			'München'
		];
		$this->assertSame($expected, $matches);

		// we dont need the utf8 hack:
		$matches = Utility::pregMatch('/([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string);
		$this->assertSame($expected, $matches);
	}

	/**
	 * UtilityTest::testPregMatchWithPatternEscape()
	 *
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
	 * UtilityTest::testPregMatchAll()
	 *
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
				'München'
			]
		];
		$this->assertSame($expected, $matches);

		// we dont need the utf8 hack:
		$matches = Utility::pregMatchAll('/([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string);
		$this->assertSame($expected, $matches);
	}

	/**
	 * UtilityTest::testStrSplit()
	 *
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
	 * UtilityTest::testUrlEncode()
	 *
	 * @covers ::urlEncode
	 * @return void
	 */
	public function testUrlEncode() {
		$res = Utility::urlEncode('Some/cool=value+more-infos');
		$this->assertSame('U29tZS9jb29sPXZhbHVlK21vcmUtaW5mb3M_', $res);
	}

	/**
	 * UtilityTest::testUrlDecode()
	 *
	 * @covers ::urlDecode
	 * @return void
	 */
	public function testUrlDecode() {
		$res = Utility::urlDecode('U29tZS9jb29sPXZhbHVlK21vcmUtaW5mb3M_');
		$this->assertSame('Some/cool=value+more-infos', $res);
	}

	/**
	 * UtilityTest::testTypeCast()
	 *
	 * @covers ::typeCast
	 * @return void
	 */
	public function testTypeCast() {
		$res = Utility::typeCast(2, 'string');
		$this->assertNotSame(2, $res);
		$this->assertSame('2', $res);
	}

	/**
	 * UtilityTest::testGetClientIp()
	 *
	 * @covers ::getClientIp
	 * @return void
	 */
	public function testGetClientIp() {
		$res = Utility::getClientIp();
		$this->assertEquals(env('REMOTE_ADDR'), $res);
	}

	/**
	 * UtilityTest::testFileExists()
	 *
	 * @covers ::fileExists
	 * @return void
	 */
	public function testFileExists() {
		$res = Utility::fileExists('http://www.spiegel.de/static/sys/v10/icons/home_v2.png');
		$this->assertTrue($res);

		$res = Utility::fileExists(Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.jpg');
		$this->assertTrue($res);

		$res = Utility::fileExists('http://www.spiegel.de/static/sys/v10/icons/home_v2_inexistent.png');
		$this->assertFalse($res);

		$res = Utility::fileExists(Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'fooooo.jpg');
		$this->assertFalse($res);
	}

	/**
	 * UtilityTest::testUrlExists()
	 *
	 * @covers ::urlExists
	 * @return void
	 */
	public function testUrlExists() {
		$res = Utility::urlExists('http://www.spiegel.de');
		$this->assertTrue($res);

		$res = Utility::urlExists('http://www.spiegel.de/some/inexistent.img');
		$this->assertFalse($res);
	}

	/**
	 * UtilityTest::testGetReferer()
	 *
	 * @covers ::getReferer
	 * @return void
	 */
	public function testGetReferer() {
		$res = Utility::getReferer();
		$this->assertEquals(env('HTTP_REFERER'), $res);

		$res = Utility::getReferer(true);
		$this->assertEquals(env('HTTP_REFERER'), $res);

		$_SERVER['HTTP_REFERER'] = '/foo/bar';
		$res = Utility::getReferer(true);
		$base = Configure::read('App.fullBaseUrl');
		if (!$base) {
			$base = ''; //'http://localhost';
		}
		$this->assertEquals($base . env('HTTP_REFERER'), $res);
	}

	/**
	 * UtilityTest::testGetHeaderFromUrl()
	 *
	 * @covers ::getHeaderFromUrl
	 * @return void
	 */
	public function testGetHeaderFromUrl() {
		$res = Utility::getHeaderFromUrl('http://www.spiegel.de');
		$this->assertTrue(is_array($res) && count($res) > 1);
		//$this->assertEquals('HTTP/1.0 200 OK', $res[0]);
	}

	/**
	 * UtilityTest::testAutoPrefixUrl()
	 *
	 * @covers ::autoPrefixUrl
	 * @return void
	 */
	public function testAutoPrefixUrl() {
		$res = Utility::autoPrefixUrl('www.spiegel.de');
		$this->assertEquals('http://www.spiegel.de', $res);
	}

	/**
	 * UtilityTest::testCleanUrl()
	 *
	 * @covers ::cleanUrl
	 * @return void
	 */
	public function testCleanUrl() {
		$res = Utility::cleanUrl('www.spiegel.de');
		$this->assertEquals('http://www.spiegel.de', $res);

		$res = Utility::cleanUrl('http://');
		$this->assertEquals('', $res);

		$res = Utility::cleanUrl('http://www');
		$this->assertEquals('', $res);

		$res = Utility::cleanUrl('spiegel.de');
		$this->assertEquals('http://spiegel.de', $res);

		$res = Utility::cleanUrl('spiegel.de', true);
		//echo returns($res);
		$this->assertEquals('http://www.spiegel.de', $res);
	}

	/**
	 * UtilityTest::testDeep()
	 *
	 * @covers ::trimDeep
	 * @return void
	 */
	public function testDeep() {
		$is = [
			'f some',
			'e 49r ' => 'rf r ',
			'er' => [['ee' => ['rr ' => ' tt ']]]
		];
		$expected = [
			'f some',
			'e 49r ' => 'rf r',
			'er' => [['ee' => ['rr ' => 'tt']]]
		];
		//$this->assertSame($expected, $is);

		$res = Utility::trimDeep($is);
		$this->assertSame($expected, $res);

		//$res = CommonComponent::trimDeep($is);
		//$this->assertSame($expected, $res);
	}

	//TODO: move to boostrap

	public function _testDeepFunction() {
		$is = [
			'f some',
			'e 49r ' => 'rf r ',
			'er' => [['ee' => ['rr ' => ' tt ']]]
		];
		$expected = [
			'f some',
			'e 49r ' => 'rf r',
			'er' => [['ee' => ['rr ' => 'tt']]]
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
		$this->assertEquals(1, $result);

		$data = ['1' => '1.1', '2', '3'];
		$result = Utility::countDim($data);
		$this->assertEquals(1, $result);

		$data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => '3.1.1']];
		$result = Utility::countDim($data);
		$this->assertEquals(2, $result);

		$data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
		$result = Utility::countDim($data);
		$this->assertEquals(1, $result);

		$data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
		$result = Utility::countDim($data, true);
		$this->assertEquals(2, $result);

		$data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data);
		$this->assertEquals(2, $result);

		$data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data, true);
		$this->assertEquals(3, $result);

		$data = ['1' => ['1.1' => '1.1.1'], ['2' => ['2.1' => ['2.1.1' => '2.1.1.1']]], '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data, true);
		$this->assertEquals(4, $result);

		$data = ['1' => ['1.1' => '1.1.1'], ['2' => ['2.1' => ['2.1.1' => ['2.1.1.1']]]], '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data, true);
		$this->assertEquals(5, $result);

		$data = ['1' => ['1.1' => '1.1.1'], ['2' => ['2.1' => ['2.1.1' => ['2.1.1.1' => '2.1.1.1.1']]]], '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($data, true);
		$this->assertEquals(5, $result);

		$set = ['1' => ['1.1' => '1.1.1'], ['2' => ['2.1' => ['2.1.1' => ['2.1.1.1' => '2.1.1.1.1']]]], '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]];
		$result = Utility::countDim($set, false, 0);
		$this->assertEquals(2, $result);

		$result = Utility::countDim($set, true);
		$this->assertEquals(5, $result);

		$data = ['one' => [null], ['null' => null], 'three' => [true, false, null]];
		$result = Utility::countDim($data, true);
		$this->assertEquals(2, $result);
	}

	/**
	 * UtilityTest::testExpand()
	 *
	 * @return void
	 */
	public function testExpandList() {
		$is = [
			'Some.Deep.Value1',
			'Some.Deep.Value2',
			'Some.Even.Deeper.Nested.Value',
			'Empty.',
			'0.1.2',
			'.EmptyString'
		];
		$result = Utility::expandList($is);

		$expected = [
			'Some' => [
				'Deep' => ['Value1', 'Value2'],
				'Even' => ['Deeper' => ['Nested' => ['Value']]]
			],
			'Empty' => [''],
			'0' => ['1' => ['2']],
			'' => ['EmptyString']
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * UtilityTest::testExpandListWithKeyLessListInvalid()
	 *
	 * @expectedException RuntimeException
	 * @return void
	 */
	public function testExpandListWithKeyLessListInvalid() {
		$is = [
			'Some',
			'ValueOnly',
		];
		Utility::expandList($is);
	}

	/**
	 * UtilityTest::testExpandListWithKeyLessList()
	 *
	 * @return void
	 */
	public function testExpandListWithKeyLessList() {
		$is = [
			'Some',
			'Thing',
			'.EmptyString'
		];
		$result = Utility::expandList($is, '.', '');

		$expected = [
			'' => ['Some', 'Thing', 'EmptyString'],
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * UtilityTest::testFlatten()
	 *
	 * @return void
	 */
	public function testFlatten() {
		$is = [
			'Some' => [
				'Deep' => ['Value1', 'Value2'],
				'Even' => ['Deeper' => ['Nested' => ['Value']]]
			],
			'Empty' => [''],
			'0' => ['1' => ['2']],
			//'ValueOnly',
			'' => ['EmptyString']
		];
		$result = Utility::flattenList($is);

		$expected = [
			'Some.Deep.Value1',
			'Some.Deep.Value2',
			'Some.Even.Deeper.Nested.Value',
			'Empty.',
			'0.1.2',
			//'1.ValueOnly'
			'.EmptyString'
		];
		$this->assertSame($expected, $result);

		// Test integers als booleans
		$is = [
			'Some' => [
				'Deep' => [true],
				'Even' => ['Deeper' => ['Nested' => [false, true]]]
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
	 * UtilityTest::testArrayFlattenBasic()
	 *
	 * @covers ::arrayFlatten
	 * @return void
	 */
	public function testArrayFlattenBasic() {
		$strings = [
			'a' => ['a' => 'A'],
			'b' => ['b' => 'B', 'c' => 'C'],
			'c' => [],
			'd' => [[['z' => 'Z'], 'y' => 'Y']]
		];

		$result = Utility::arrayFlatten($strings);
		$expected = [
			'a' => 'A',
			'b' => 'B',
			'c' => 'C',
			'z' => 'Z',
			'y' => 'Y'
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
	 * UtilityTest::testArrayFlattenAndPreserveKeys()
	 *
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
	 * UtilityTest::testArrayShiftKeys()
	 *
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
	 * UtilityTest::testTime()
	 *
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
	 * UtilityTest::testLogicalAnd()
	 *
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
	 * UtilityTest::testLogicalOr()
	 *
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

	/**
	 * UtilityTest::testIsValidSaveAll()
	 *
	 * @covers ::isValidSaveAll
	 * @return void
	 */
	public function testIsValidSaveAll() {
		$result = Utility::isValidSaveAll([]);
		$this->assertFalse($result);

		$result = Utility::isValidSaveAll([true, true]);
		$this->assertTrue($result);

		$result = Utility::isValidSaveAll([true, false]);
		$this->assertFalse($result);
	}

}
