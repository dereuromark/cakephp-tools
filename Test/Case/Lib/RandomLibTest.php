<?php

App::uses('RandomLib', 'Tools.Lib');

class RandomLibTest extends CakeTestCase {

	public function testInt() {
		$is = RandomLib::int(2, 200);
		//pr($is);
		$this->assertTrue($is >= 2 && $is <= 200);
	}

	public function testArrayValue() {
		$array = array(
			'x',
			'y',
			'z',
		);
		$is = RandomLib::arrayValue($array, null, null, true);
		//pr($is);
		$this->assertTrue(in_array($is, $array));

		// non-numerical indexes
		$array = array(
			'e' => 'x',
			'f' => 'y',
			'g' => 'z',
		);
		$is = RandomLib::arrayValue($array);
		//pr($is);
		$this->assertTrue(in_array($is, $array));
	}

	public function testPronounceablePwd() {
		$is = RandomLib::pronounceablePwd(6);
		//pr($is);
		$this->assertTrue(strlen($is) === 6);

		$is = RandomLib::pronounceablePwd(11);
		//pr($is);
		$this->assertTrue(strlen($is) === 11);
	}

	//TOOD: other tests

}
