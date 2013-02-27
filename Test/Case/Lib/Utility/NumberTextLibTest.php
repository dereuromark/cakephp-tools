<?php

App::uses('NumberTextLib', 'Tools.Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class NumberTextLibTest extends MyCakeTestCase {

	public $NumberText = null;

	public function setUp() {
		parent::setUp();

		//$this->NumberText = new NumberTextLib();
	}

	public function testText() {
		$is = NumberTextLib::numberText(22);
		$expected = 'twenty-two';
		$this->assertSame($expected, $is);

		$is = NumberTextLib::numberText(-2);
		$expected = 'negative two';
		$this->assertSame($expected, $is);

		$is = NumberTextLib::numberText(322, 'de_DE');
		$expected = 'dreihundertzweiundzwanzig';
		$this->assertSame($expected, $is);

		$is = NumberTextLib::numberText(-1, 'de_DE');
		$expected = 'minus eins';
		$this->assertSame($expected, $is);
	}

	public function testMoney() {
		$is = NumberTextLib::moneyText(22, 'EUR');
		$expected = 'twenty-two euro';
		$this->assertSame($expected, $is);

		$is = NumberTextLib::moneyText(-1, 'EUR');
		$expected = 'negative one euro';
		$this->assertSame($expected, $is);

		$is = NumberTextLib::moneyText(22, 'EUR', 'de_DE');
		$expected = 'zweiundzwanzig Euro';
		$this->assertSame($expected, $is);

		$is = NumberTextLib::moneyText(-1, 'EUR', 'de_DE');
		$expected = 'minus ein Euro';
		$this->assertSame($expected, $is);
	}

}
