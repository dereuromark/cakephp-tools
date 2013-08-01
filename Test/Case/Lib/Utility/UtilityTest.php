<?php
App::uses('Utility', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * 2012-02-21 ms
 */
class UtilityTest extends MyCakeTestCase {

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

	public function testPregMatch() {
		$string = '<abc>';
		preg_match('/\<(\w+)\>/', $string, $matches);
		$this->assertSame(array($string, 'abc'), $matches);

		Utility::pregMatch('/\<(\w+)\>/', $string, $matches);
		$this->assertSame(array($string, 'abc'), $matches);

		$string = '<äöü>';
		preg_match('/\<(.+)\>/', $string, $matches);
		$this->assertSame(array($string, 'äöü'), $matches);

		Utility::pregMatch('/\<(.+)\>/', $string, $matches);
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
		Utility::pregMatch('/([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string, $matches);
		$this->assertSame($expected, $matches);
	}

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
		Utility::pregMatchAll('/([\w+])-([a-z0-9]+)\s+\b([\w\s]+)\b/iu', $string, $matches);
		$this->assertSame($expected, $matches);
	}

	public function testStrSplit() {
		$res = str_split('some äöü string', 7);
		$expected = array('some äö', 'ü strin', 'g');
		$this->assertNotSame($expected, $res);

		$res = Utility::strSplit('some äöü string', 7);
		$this->assertSame($expected, $res);
	}

	public function testTypeCast() {
		$res = Utility::typeCast(2, 'string');
		$this->assertNotSame(2, $res);
		$this->assertSame('2', $res);
	}

	public function testGetClientIp() {
		$res = Utility::getClientIp();
		$this->assertEquals(env('REMOTE_ADDR'), $res);
	}

	public function testGetReferer() {
		$res = Utility::getReferer();
		//$this->assertTrue(env(''), $res);
		$this->assertEquals(env('HTTP_REFERER'), $res);
	}

	public function testGetHeaderFromUrl() {
		$res = Utility::getHeaderFromUrl('http://www.spiegel.de');
		$this->assertTrue(is_array($res) && count($res) > 10);
		$this->assertEquals('HTTP/1.0 200 OK', $res[0]);
	}

	public function testAutoPrefixUrl() {
		$res = Utility::autoPrefixUrl('www.spiegel.de');
		$this->assertEquals('http://www.spiegel.de', $res);
	}

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

	public function testDeep() {
		$is = array(
			'f some',
			'e 49r ' => 'rf r ',
			'er' => array(array('ee'=>array('rr '=>' tt ')))
		);
		$expected = array(
			'f some',
			'e 49r ' => 'rf r',
			'er' => array(array('ee'=>array('rr '=>'tt')))
		);
		//$this->assertSame($is, $expected);

		$res = Utility::trimDeep($is);
		$this->assertSame($res, $expected);

		//$res = CommonComponent::trimDeep($is);
		//$this->assertSame($res, $expected);
	}

	//TODO: move to boostrap
	public function _testDeepFunction() {
		$is = array(
			'f some',
			'e 49r ' => 'rf r ',
			'er' => array(array('ee'=>array('rr '=>' tt ')))
		);
		$expected = array(
			'f some',
			'e 49r ' => 'rf r',
			'er' => array(array('ee'=>array('rr '=>'tt')))
		);

		$res = Utility::deep('trim', $is);
		$this->assertSame($res, $expected);

	}

	public function testArrayFlatten() {
		$array=array(
			'a' => 1,
			'b' => array('c'=>array('d'=>array('f'=>'g', 'h'=>true))),
			'k' => 'm',
		);
		$res = Utility::arrayFlatten($array);

		$expected = array(
			'a' => 1,
			'f' => 'g',
			'h'=> true,
			'k' => 'm',
		);
		$this->assertSame($expected, $res);
	}

	public function testArrayShiftKeys() {
		$array=array(
			'a' => 1,
			'b' => array('c'=>array('d'=>array('f'=>'g', 'h'=>true))),
			'k' => 'm',
		);
		$res = Utility::arrayShiftKeys($array);

		$expected = 'a';
		$this->assertSame($expected, $res);
		$expected = array(
			'b' => array('c'=>array('d'=>array('f'=>'g', 'h'=>true))),
			'k' => 'm',
		);
		$this->assertSame($expected, $array);
	}

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

	public function testLogicalAnd() {
		$array=array(
			'a' => 1,
			'b' => 1,
			'c' => 0,
			'd' => 1,
		);
		$is = Utility::logicalAnd($array);
		$this->assertSame($is, false);

		$array=array(
			'a' => 1,
			'b' => 1,
			'c' => 1,
			'd' => 1,
		);
		$is = Utility::logicalAnd($array);
		$this->assertSame($is, true);
	}



	public function testLogicalOr() {
		$array=array(
			'a' => 0,
			'b' => 1,
			'c' => 0,
			'd' => 1,
		);
		$is = Utility::logicalOr($array);
		$this->assertSame($is, true);

		$array=array(
			'a' => 1,
			'b' => 1,
			'c' => 1,
			'd' => 1,
		);
		$is = Utility::logicalOr($array);
		$this->assertSame($is, true);


		$array=array(
			'a' => 0,
			'b' => 0,
			'c' => 0,
			'd' => 0,
		);
		$is = Utility::logicalOr($array);
		$this->assertSame($is, false);



	}




}
