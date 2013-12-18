<?php
App::uses('Utility', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * @covers Utility
 */
class UtilityTest extends MyCakeTestCase {

	/**
	 * UtilityTest::testInArray()
	 *
	 * @covers Utility::inArray
	 * @return void
	 */
	public function testInArray() {
		$res = Utility::inArray(2, array(1, 2, 3));
		$this->assertTrue($res);

		$res = Utility::inArray(4, array(1, 2, 7));
		$this->assertFalse($res);

		$res = Utility::inArray('2', array(1, 2, 3));
		$this->assertTrue($res);

		$res = Utility::inArray(2, array('1x', '2x', '3x'));
		$this->assertFalse($res);

		$res = Utility::inArray('3x', array('1x', '2x', '3x'));
		$this->assertTrue($res);

		$res = Utility::inArray(3, array('1', '2', '3'));
		$this->assertTrue($res);

		$res = Utility::inArray('2x', array(1, 2, 3));
		$this->assertFalse($res);
	}

	public function testTokenize() {
		$res = Utility::tokenize('');
		$this->assertSame(array(), $res);

		$res = Utility::tokenize('some');
		$this->assertSame(array('some'), $res);

		$res = Utility::tokenize('some, thing');
		$this->assertSame(array('some', 'thing'), $res);

		$res = Utility::tokenize(',some,,, ,, thing,');
		$this->assertSame(array('some', 'thing'), array_values($res));
	}

	/**
	 * UtilityTest::testPregMatch()
	 *
	 * @covers Utility::pregMatch
	 * @return void
	 */
	public function testPregMatch() {
		$string = '<abc>';
		preg_match('/\<(\w+)\>/', $string, $matches);
		$this->assertSame(array($string, 'abc'), $matches);

		$matches = Utility::pregMatch('/\<(\w+)\>/', $string);
		$this->assertSame(array($string, 'abc'), $matches);

		$string = '<äöü>';
		preg_match('/\<(.+)\>/', $string, $matches);
		$this->assertSame(array($string, 'äöü'), $matches);

		$matches = Utility::pregMatch('/\<(.+)\>/', $string);
		$this->assertSame(array($string, 'äöü'), $matches);

		$string = 'D-81245 München';
		preg_match('/(*UTF8)([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string, $matches);
		$expected = array(
			$string,
			'D',
			'81245',
			'München'
		);
		$this->assertSame($expected, $matches);

		// we dont need the utf8 hack:
		$matches = Utility::pregMatch('/([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string);
		$this->assertSame($expected, $matches);
	}

	/**
	 * UtilityTest::testPregMatchWithPatternEscape()
	 *
	 * @covers Utility::pregMatch
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
		$expected = array($string);
		$this->assertSame($expected, $matches);
	}

	/**
	 * UtilityTest::testPregMatchAll()
	 *
	 * @covers Utility::pregMatchAll
	 * @return void
	 */
	public function testPregMatchAll() {
		$string = 'D-81245 München';
		preg_match_all('/(*UTF8)([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string, $matches, PREG_SET_ORDER);
		$expected = array(
			array(
				$string,
				'D',
				'81245',
				'München'
			)
		);
		$this->assertSame($expected, $matches);

		// we dont need the utf8 hack:
		$matches = Utility::pregMatchAll('/([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string);
		$this->assertSame($expected, $matches);
	}

	/**
	 * UtilityTest::testStrSplit()
	 *
	 * @covers Utility::strSplit
	 * @return void
	 */
	public function testStrSplit() {
		$res = str_split('some äöü string', 7);
		$expected = array('some äö', 'ü strin', 'g');
		$this->assertNotSame($expected, $res);

		$res = Utility::strSplit('some äöü string', 7);
		$this->assertSame($expected, $res);
	}

	/**
	 * UtilityTest::testUrlEncode()
	 *
	 * @covers Utility::urlEncode
	 * @return void
	 */
	public function testUrlEncode() {
		$res = Utility::urlEncode('Some/cool=value+more-infos');
		$this->assertSame('U29tZS9jb29sPXZhbHVlK21vcmUtaW5mb3M_', $res);
	}

	/**
	 * UtilityTest::testUrlDecode()
	 *
	 * @covers Utility::urlDecode
	 * @return void
	 */
	public function testUrlDecode() {
		$res = Utility::urlDecode('U29tZS9jb29sPXZhbHVlK21vcmUtaW5mb3M_');
		$this->assertSame('Some/cool=value+more-infos', $res);
	}

	/**
	 * UtilityTest::testTypeCast()
	 *
	 * @covers Utility::typeCast
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
	 * @covers Utility::getClientIp
	 * @return void
	 */
	public function testGetClientIp() {
		$res = Utility::getClientIp();
		$this->assertEquals(env('REMOTE_ADDR'), $res);
	}

	/**
	 * UtilityTest::testGetReferer()
	 *
	 * @covers Utility::getReferer
	 * @return void
	 */
	public function testGetReferer() {
		$res = Utility::getReferer();
		$this->assertEquals(env('HTTP_REFERER'), $res);

		$res = Utility::getReferer(true);
		$this->assertEquals(env('HTTP_REFERER'), $res);

		$_SERVER['HTTP_REFERER'] = '/foo/bar';
		$res = Utility::getReferer(true);
		$base = HTTP_BASE;
		if (!$base) {
			$base = 'http://localhost';
		}
		$this->assertEquals($base . env('HTTP_REFERER'), $res);
	}

	/**
	 * UtilityTest::testGetHeaderFromUrl()
	 *
	 * @covers Utility::getHeaderFromUrl
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
	 * @covers Utility::autoPrefixUrl
	 * @return void
	 */
	public function testAutoPrefixUrl() {
		$res = Utility::autoPrefixUrl('www.spiegel.de');
		$this->assertEquals('http://www.spiegel.de', $res);
	}

	/**
	 * UtilityTest::testCleanUrl()
	 *
	 * @covers Utility::cleanUrl
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
	 * @covers Utility::trimDeep
	 * @return void
	 */
	public function testDeep() {
		$is = array(
			'f some',
			'e 49r ' => 'rf r ',
			'er' => array(array('ee' => array('rr ' => ' tt ')))
		);
		$expected = array(
			'f some',
			'e 49r ' => 'rf r',
			'er' => array(array('ee' => array('rr ' => 'tt')))
		);
		//$this->assertSame($expected, $is);

		$res = Utility::trimDeep($is);
		$this->assertSame($expected, $res);

		//$res = CommonComponent::trimDeep($is);
		//$this->assertSame($expected, $res);
	}

	//TODO: move to boostrap

	public function _testDeepFunction() {
		$is = array(
			'f some',
			'e 49r ' => 'rf r ',
			'er' => array(array('ee' => array('rr ' => ' tt ')))
		);
		$expected = array(
			'f some',
			'e 49r ' => 'rf r',
			'er' => array(array('ee' => array('rr ' => 'tt')))
		);

		$result = Utility::deep('trim', $is);
		$this->assertSame($expected, $result);
	}

	/**
	 * UtilityTest::testExpand()
	 *
	 * @return void
	 */
	public function testExpandList() {
		$is = array(
			'Some.Deep.Value1',
			'Some.Deep.Value2',
			'Some.Even.Deeper.Nested.Value',
			'Empty.',
			'0.1.2',
			'.EmptyString'
		);
		$result = Utility::expandList($is);

		$expected = array(
			'Some' => array(
				'Deep' => array('Value1', 'Value2'),
				'Even' => array('Deeper' => array('Nested' => array('Value')))
			),
			'Empty' => array(''),
			'0' => array('1' => array('2')),
			'' => array('EmptyString')
		);
		$this->assertSame($expected, $result);
	}

	/**
	 * UtilityTest::testExpandListWithKeyLessListInvalid()
	 *
	 * @expectedException RuntimeException
	 * @return void
	 */
	public function testExpandListWithKeyLessListInvalid() {
		$is = array(
			'Some',
			'ValueOnly',
		);
		Utility::expandList($is);
	}

	/**
	 * UtilityTest::testExpandListWithKeyLessList()
	 *
	 * @return void
	 */
	public function testExpandListWithKeyLessList() {
		$is = array(
			'Some',
			'Thing',
			'.EmptyString'
		);
		$result = Utility::expandList($is, '.', '');

		$expected = array(
			'' => array('Some', 'Thing', 'EmptyString'),
		);
		$this->assertSame($expected, $result);
	}

	/**
	 * UtilityTest::testFlatten()
	 *
	 * @return void
	 */
	public function testFlatten() {
		$is = array(
			'Some' => array(
				'Deep' => array('Value1', 'Value2'),
				'Even' => array('Deeper' => array('Nested' => array('Value')))
			),
			'Empty' => array(''),
			'0' => array('1' => array('2')),
			//'ValueOnly',
			'' => array('EmptyString')
		);
		$result = Utility::flattenList($is);

		$expected = array(
			'Some.Deep.Value1',
			'Some.Deep.Value2',
			'Some.Even.Deeper.Nested.Value',
			'Empty.',
			'0.1.2',
			//'1.ValueOnly'
			'.EmptyString'
		);
		$this->assertSame($expected, $result);

		// Test integers als booleans
		$is = array(
			'Some' => array(
				'Deep' => array(true),
				'Even' => array('Deeper' => array('Nested' => array(false, true)))
			),
			'Integer' => array('Value' => array(-3)),
		);
		$result = Utility::flattenList($is);

		$expected = array(
			'Some.Deep.1',
			'Some.Even.Deeper.Nested.0',
			'Some.Even.Deeper.Nested.1',
			'Integer.Value.-3',
		);
		$this->assertSame($expected, $result);
	}

	/**
	 * UtilityTest::testArrayFlattenBasic()
	 *
	 * @covers Utility::arrayFlatten
	 * @return void
	 */
	public function testArrayFlattenBasic() {
		$strings = array(
			'a' => array('a' => 'A'),
			'b' => array('b' => 'B', 'c' => 'C'),
			'c' => array(),
			'd' => array(array(array('z' => 'Z'), 'y' => 'Y'))
		);

		$result = Utility::arrayFlatten($strings);
		$expected = array(
			'a' => 'A',
			'b' => 'B',
			'c' => 'C',
			'z' => 'Z',
			'y' => 'Y'
		);
		$this->assertSame($expected, $result);
	}

	/**
	 * Test that deeper nested values overwrite higher ones.
	 *
	 * @covers Utility::arrayFlatten
	 * @return void
	 */
	public function testArrayFlatten() {
		$array = array(
			'a' => 1,
			'b' => array('h' => false, 'c' => array('d' => array('f' => 'g', 'h' => true))),
			'k' => 'm',
		);
		$res = Utility::arrayFlatten($array);

		$expected = array(
			'a' => 1,
			'h' => true,
			'f' => 'g',
			'k' => 'm',
		);
		$this->assertSame($expected, $res);
	}

	/**
	 * UtilityTest::testArrayFlattenAndPreserveKeys()
	 *
	 * @covers Utility::arrayFlatten
	 * @return void
	 */
	public function testArrayFlattenAndPreserveKeys() {
		$array = array(
			0 => 1,
			1 => array('c' => array('d' => array('g', 'h' => true))),
			2 => 'm',
		);
		$res = Utility::arrayFlatten($array, true);

		$expected = array(
			0 => 'g',
			'h' => true,
			2 => 'm',
		);
		$this->assertSame($expected, $res);
	}

	/**
	 * UtilityTest::testArrayShiftKeys()
	 *
	 * @covers Utility::arrayShiftKeys
	 * @return void
	 */
	public function testArrayShiftKeys() {
		$array = array(
			'a' => 1,
			'b' => array('c' => array('d' => array('f' => 'g', 'h' => true))),
			'k' => 'm',
		);
		$res = Utility::arrayShiftKeys($array);

		$expected = 'a';
		$this->assertSame($expected, $res);
		$expected = array(
			'b' => array('c' => array('d' => array('f' => 'g', 'h' => true))),
			'k' => 'm',
		);
		$this->assertSame($expected, $array);
	}

	/**
	 * UtilityTest::testTime()
	 *
	 * @covers Utility::returnElapsedTime
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
	 * @covers Utility::logicalAnd
	 * @return void
	 */
	public function testLogicalAnd() {
		$array = array(
			'a' => 1,
			'b' => 1,
			'c' => 0,
			'd' => 1,
		);
		$is = Utility::logicalAnd($array);
		$this->assertFalse($is);

		$array = array(
			'a' => 1,
			'b' => 1,
			'c' => 1,
			'd' => 1,
		);
		$is = Utility::logicalAnd($array);
		$this->assertTrue($is);
	}

	/**
	 * UtilityTest::testLogicalOr()
	 *
	 * @covers Utility::logicalOr
	 * @return void
	 */
	public function testLogicalOr() {
		$array = array(
			'a' => 0,
			'b' => 1,
			'c' => 0,
			'd' => 1,
		);
		$is = Utility::logicalOr($array);
		$this->assertTrue($is);

		$array = array(
			'a' => 1,
			'b' => 1,
			'c' => 1,
			'd' => 1,
		);
		$is = Utility::logicalOr($array);
		$this->assertTrue($is);

		$array = array(
			'a' => 0,
			'b' => 0,
			'c' => 0,
			'd' => 0,
		);
		$is = Utility::logicalOr($array);
		$this->assertFalse($is);
	}

	/**
	 * UtilityTest::testIsValidSaveAll()
	 *
	 * @covers Utility::isValidSaveAll
	 * @return void
	 */
	public function testIsValidSaveAll() {
		$result = Utility::isValidSaveAll(array());
		$this->assertFalse($result);

		$result = Utility::isValidSaveAll(array(true, true));
		$this->assertTrue($result);

		$result = Utility::isValidSaveAll(array(true, false));
		$this->assertFalse($result);
	}

}
