<?php
App::uses('ZodiacLib', 'Tools.Misc');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class ZodiacLibTest extends MyCakeTestCase {

	public $Zodiac;

	public function setUp() {
		parent::setUp();

		$this->Zodiac = new ZodiacLib();
	}

	public function testImage() {
		$is = $this->Zodiac->image(ZodiacLib::SIGN_ARIES);
		$this->debug($is);
		$this->assertEquals($is, 'aries');
	}

	public function testSigns() {
		$is = $this->Zodiac->signs();
		$this->debug($is);
		$this->assertTrue(count($is) === 12);
	}

	public function testSign() {
		$is = $this->Zodiac->getSign(4, 9);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_ARIES);

		$is = $this->Zodiac->signs($is);
		$this->debug($is);
		$this->assertEquals($is, __('zodiacAries'));

		// january
		$is = $this->Zodiac->getSign(1, 20);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_CAPRICORN);

		$is = $this->Zodiac->getSign(1, 21);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_AQUARIUS);

		#february
		$is = $this->Zodiac->getSign(2, 19);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_AQUARIUS);

		$is = $this->Zodiac->getSign(2, 20);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_PISCES);

		#march
		$is = $this->Zodiac->getSign(3, 20);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_PISCES);

		$is = $this->Zodiac->getSign(3, 21);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_ARIES);

		#april
		$is = $this->Zodiac->getSign(4, 20);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_ARIES);

		$is = $this->Zodiac->getSign(4, 21);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_TAURUS);

		#may
		$is = $this->Zodiac->getSign(5, 21);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_TAURUS);

		$is = $this->Zodiac->getSign(5, 22);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_GEMINI);

		#june
		$is = $this->Zodiac->getSign(6, 21);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_GEMINI);

		$is = $this->Zodiac->getSign(6, 22);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_CANCER);

		#july
		$is = $this->Zodiac->getSign(7, 23);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_CANCER);

		$is = $this->Zodiac->getSign(7, 24);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_LEO);

		#august
		$is = $this->Zodiac->getSign(8, 23);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_LEO);

		$is = $this->Zodiac->getSign(8, 24);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_VIRGO);

		#september
		$is = $this->Zodiac->getSign(9, 23);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_VIRGO);

		$is = $this->Zodiac->getSign(9, 24);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_LIBRA);

		#october
		$is = $this->Zodiac->getSign(10, 23);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_LIBRA);

		$is = $this->Zodiac->getSign(10, 24);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_SCORPIO);

		$is = $this->Zodiac->getSign(10, 26);
		$this->debug($is);
		$this->assertSame(ZodiacLib::SIGN_SCORPIO, $is);

		#november
		$is = $this->Zodiac->getSign(11, 22);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_SCORPIO);

		$is = $this->Zodiac->getSign(11, 23);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_SAGITTARIUS);

		#december
		$is = $this->Zodiac->getSign(12, 21);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_SAGITTARIUS);

		$is = $this->Zodiac->getSign(12, 22);
		$this->debug($is);
		$this->assertSame($is, ZodiacLib::SIGN_CAPRICORN);
	}

	public function testRange() {
		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_AQUARIUS);
		$this->assertEquals($is, array(array(1, 21), array(2, 19)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_PISCES);
		$this->assertEquals($is, array(array(2, 20), array(3, 20)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_ARIES);
		$this->assertEquals($is, array(array(3, 21), array(4, 20)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_TAURUS);
		$this->assertEquals($is, array(array(4, 21), array(5, 21)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_GEMINI);
		$this->assertEquals($is, array(array(5, 22), array(6, 21)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_CANCER);
		$this->assertEquals($is, array(array(6, 22), array(7, 23)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_LEO);
		$this->assertEquals($is, array(array(7, 24), array(8, 23)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_VIRGO);
		$this->assertEquals($is, array(array(8, 24), array(9, 23)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_LIBRA);
		$this->assertEquals($is, array(array(9, 24), array(10, 23)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_SCORPIO);
		$this->assertEquals($is, array(array(10, 24), array(11, 22)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_SAGITTARIUS);
		$this->assertEquals($is, array(array(11, 23), array(12, 21)));

		$is = $this->Zodiac->getRange(ZodiacLib::SIGN_CAPRICORN);
		$this->assertEquals($is, array(array(12, 22), array(1, 20)));
	}

	public function testSignViaRange() {
		for ($i = 1; $i <= 12; $i++) {
			$this->out(ZodiacLib::signs($i) . BR);
			$range = $this->Zodiac->getRange($i);

			$is = $this->Zodiac->getSign($range[0][0], $range[0][1]);
			$this->assertSame($is, $i);

			$is = $this->Zodiac->getSign($range[1][0], $range[1][1]);
			$this->assertSame($is, $i);

			// min-1
			$month = $range[0][0];
			$day = $range[0][1] - 1;
			$is = $this->Zodiac->getSign($month, $day);
			$ii = $i;
			if ($ii == 1) {
				$ii = 13;
			}
			$this->assertSame($is, $ii - 1);

			// max+1
			$month = $range[1][0];
			$day = $range[1][1] + 1;
			$ii = $i;
			if ($ii == 12) {
				$ii = 0;
			}
			$is = $this->Zodiac->getSign($month, $day);
			$this->assertSame($is, $ii + 1);
		}
	}

}
