<?php

/**
 * Zodiac Signs
 */
class ZodiacLib {

	public $error = null;

	public static $res = array(
		self::SIGN_AQUARIUS	=> 'aquarius',
		self::SIGN_ARIES	=> 'aries',
		self::SIGN_CANCER	=> 'cancer',
		self::SIGN_CAPRICORN	=> 'capricorn',
		self::SIGN_GEMINI	=> 'gemini',
		self::SIGN_LEO	=> 'leo',
		self::SIGN_LIBRA	=> 'libra',
		self::SIGN_PISCES	=> 'pisces',
		self::SIGN_SAGITTARIUS	=> 'sagittarius',
		self::SIGN_SCORPIO	=> 'scorpio',
		self::SIGN_TAURUS	=> 'taurus',
		self::SIGN_VIRGO	=> 'virgo',
	);

	public function error() {
		return $this->error;
	}

	/**
	 * @param integer $sign
	 * @return array(array(m, d), array(m, d)) (first is min, second is max)
	 */
	public function getRange($sign) {
		$range = null;
		switch ($sign) {
			case self::SIGN_AQUARIUS:
				$range = array(array(1, 21), array(2, 19));
				break;
			case self::SIGN_PISCES:
				$range = array(array(2, 20), array(3, 20));
				break;
			case self::SIGN_ARIES:
				$range = array(array(3, 21), array(4, 20));
				break;
			case self::SIGN_TAURUS:
				$range = array(array(4, 21), array(5, 21));
				break;
			case self::SIGN_GEMINI:
				$range = array(array(5, 22), array(6, 21));
				break;
			case self::SIGN_CANCER:
				$range = array(array(6, 22), array(7, 23));
				break;
			case self::SIGN_LEO:
				$range = array(array(7, 24), array(8, 23));
				break;
			case self::SIGN_VIRGO:
				$range = array(array(8, 24), array(9, 23));
				break;
			case self::SIGN_LIBRA:
				$range = array(array(9, 24), array(10, 23));
				break;
			case self::SIGN_SCORPIO:
				$range = array(array(10, 24), array(11, 22));
				break;
			case self::SIGN_SAGITTARIUS:
				$range = array(array(11, 23), array(12, 21));
				break;
			case self::SIGN_CAPRICORN:
				$range = array(array(12, 22), array(1, 20));
				break;
		}

		return $range;
	}

	/**
	 * Zodiac Sign for given day and month
	 *
	 * @param month
	 * @param day
	 * expects valid values
	 * @return integer sign or false on failure
	 */
	public function getSign($month, $day) {
		switch ($month) {
			case 1:
				$zodiac = ($day <= 20) ? self::SIGN_CAPRICORN : self::SIGN_AQUARIUS;
				break;
			case 2:
				$zodiac = ($day <= 19) ? self::SIGN_AQUARIUS : self::SIGN_PISCES;
				break;
			case 3:
				$zodiac = ($day <= 20) ? self::SIGN_PISCES : self::SIGN_ARIES;
				break;
			case 4:
				$zodiac = ($day <= 20) ? self::SIGN_ARIES : self::SIGN_TAURUS;
				break;
			case 5 :
				$zodiac = ($day <= 21) ? self::SIGN_TAURUS : self::SIGN_GEMINI;
				break;
			case 6 :
				$zodiac = ($day <= 21) ? self::SIGN_GEMINI : self::SIGN_CANCER;
				break;
			case 7 :
				$zodiac = ($day <= 23) ? self::SIGN_CANCER : self::SIGN_LEO;
				break;
			case 8 :
				$zodiac = ($day <= 23) ? self::SIGN_LEO : self::SIGN_VIRGO;
				break;
			case 9 :
				$zodiac = ($day <= 23) ? self::SIGN_VIRGO : self::SIGN_LIBRA;
				break;
			case 10 :
				$zodiac = ($day <= 23) ? self::SIGN_LIBRA : self::SIGN_SCORPIO;
				break;
			case 11 :
				$zodiac = ($day <= 22) ? self::SIGN_SCORPIO : self::SIGN_SAGITTARIUS;
				break;
			case 12 :
				$zodiac = ($day <= 21) ? self::SIGN_SAGITTARIUS : self::SIGN_CAPRICORN;
				break;
		}
		return $zodiac;
	}

	/**
	 * ZodiacLib::getChineseSign()
	 *
	 * @param mixed $year
	 * @param mixed $month
	 * @param mixed $day
	 * @return void
	 */
	public function getChineseSign($year, $month, $day) {
		//TODO
	}

	/**
	 * ZodiacLib::getNativeAmericanSign()
	 *
	 * @param mixed $month
	 * @param mixed $day
	 * @return void
	 */
	public function getNativeAmericanSign($month, $day) {
		//TODO
	}

	/**
	 * List of all signs
	 *
	 * @return mixed
	 */
	public static function signs($value = null) {
		$res = array(
			self::SIGN_AQUARIUS	=> __('zodiacAquarius'),
			self::SIGN_PISCES	=> __('zodiacPisces'),
			self::SIGN_ARIES	=> __('zodiacAries'),
			self::SIGN_TAURUS	=> __('zodiacTaurus'),
			self::SIGN_GEMINI	=> __('zodiacGemini'),
			self::SIGN_CANCER	=> __('zodiacCancer'),
			self::SIGN_LEO	=> __('zodiacLeo'),
			self::SIGN_VIRGO	=> __('zodiacVirgo'),
			self::SIGN_LIBRA	=> __('zodiacLibra'),
			self::SIGN_SCORPIO	=> __('zodiacScorpio'),
			self::SIGN_SAGITTARIUS	=> __('zodiacSagittarius'),
			self::SIGN_CAPRICORN	=> __('zodiacCapricorn'),
		);
		if ($value === null) {
			return $res;
		}
		return $res[$value];
	}

	/**
	 * ZodiacLib::image()
	 *
	 * @param integer $sign
	 * @return string
	 */
	public static function image($sign) {
		return self::$res[$sign];
	}

	const SIGN_AQUARIUS = 1; # from 20.01. to 18.02.
	const SIGN_PISCES = 2; # from 19 Febbraio to 20 marzo
	const SIGN_ARIES = 3;
	const SIGN_TAURUS = 4;
	const SIGN_GEMINI = 5;
	const SIGN_CANCER = 6;
	const SIGN_LEO = 7;
	const SIGN_VIRGO = 8; # from 23.08. to 22.09.
	const SIGN_LIBRA = 9;
	const SIGN_SCORPIO = 10;
	const SIGN_SAGITTARIUS = 11;
	const SIGN_CAPRICORN = 12;

}

/*
	2 aries1.gif from 21 Marzo to 20 Aprile
	3 cancer1.gif from 22 Giugno to 22 luglio
	4 cap1.gif from 22 Dicembre to 20 gennaio
	5 gemini1.gif from 22 Maggio to 22 giugno
	6 leo1.gif from 22 Luglio to 21 agosto
	7 libra1.gif from 24 Settembre to 23 ottobre
	8
	9 sag1.gif from 22 Novembre to 22 dicembre
	10 scorpio1.gif from 24 Ottobre to 21 novembre
	11 taurus1.gif from 21 Aprile to 21 Maggio

Wassermann (21. Januar - 19. Februar)
Fische (20. Februar - 20. März)
Widder (21. März - 20. April)
Stier (21. April - 20. Mai)
Zwillinge (21. Mai - 21. Juni)
Krebs (22. Juni - 22. Juli)
Löwe (23. Juli - 23. August)
Jungfrau (24. August - 23. September)
Waage (24. September - 23. Oktober)
Skorpion (24. Oktober - 22. November)
Schütze (23. November - 21. Dezember)
Steinbock (22. Dezember - 20. Januar)

*/