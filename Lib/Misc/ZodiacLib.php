<?php

/**
 * Zodiac Signs
 */
class ZodiacLib {

	public $error = null;

	public static $res = [
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
	];

	public function error() {
		return $this->error;
	}

	/**
	 * @param int $sign
	 * @return array(array(m, d), array(m, d)) (first is min, second is max)
	 */
	public function getRange($sign) {
		$range = null;
		switch ($sign) {
			case static::SIGN_AQUARIUS:
				$range = [[1, 21], [2, 19]];
				break;
			case static::SIGN_PISCES:
				$range = [[2, 20], [3, 20]];
				break;
			case static::SIGN_ARIES:
				$range = [[3, 21], [4, 20]];
				break;
			case static::SIGN_TAURUS:
				$range = [[4, 21], [5, 21]];
				break;
			case static::SIGN_GEMINI:
				$range = [[5, 22], [6, 21]];
				break;
			case static::SIGN_CANCER:
				$range = [[6, 22], [7, 23]];
				break;
			case static::SIGN_LEO:
				$range = [[7, 24], [8, 23]];
				break;
			case static::SIGN_VIRGO:
				$range = [[8, 24], [9, 23]];
				break;
			case static::SIGN_LIBRA:
				$range = [[9, 24], [10, 23]];
				break;
			case static::SIGN_SCORPIO:
				$range = [[10, 24], [11, 22]];
				break;
			case static::SIGN_SAGITTARIUS:
				$range = [[11, 23], [12, 21]];
				break;
			case static::SIGN_CAPRICORN:
				$range = [[12, 22], [1, 20]];
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
	 * @return int sign or false on failure
	 */
	public function getSign($month, $day) {
		switch ($month) {
			case 1:
				$zodiac = ($day <= 20) ? static::SIGN_CAPRICORN : static::SIGN_AQUARIUS;
				break;
			case 2:
				$zodiac = ($day <= 19) ? static::SIGN_AQUARIUS : static::SIGN_PISCES;
				break;
			case 3:
				$zodiac = ($day <= 20) ? static::SIGN_PISCES : static::SIGN_ARIES;
				break;
			case 4:
				$zodiac = ($day <= 20) ? static::SIGN_ARIES : static::SIGN_TAURUS;
				break;
			case 5 :
				$zodiac = ($day <= 21) ? static::SIGN_TAURUS : static::SIGN_GEMINI;
				break;
			case 6 :
				$zodiac = ($day <= 21) ? static::SIGN_GEMINI : static::SIGN_CANCER;
				break;
			case 7 :
				$zodiac = ($day <= 23) ? static::SIGN_CANCER : static::SIGN_LEO;
				break;
			case 8 :
				$zodiac = ($day <= 23) ? static::SIGN_LEO : static::SIGN_VIRGO;
				break;
			case 9 :
				$zodiac = ($day <= 23) ? static::SIGN_VIRGO : static::SIGN_LIBRA;
				break;
			case 10 :
				$zodiac = ($day <= 23) ? static::SIGN_LIBRA : static::SIGN_SCORPIO;
				break;
			case 11 :
				$zodiac = ($day <= 22) ? static::SIGN_SCORPIO : static::SIGN_SAGITTARIUS;
				break;
			case 12 :
				$zodiac = ($day <= 21) ? static::SIGN_SAGITTARIUS : static::SIGN_CAPRICORN;
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
		$res = [
			static::SIGN_AQUARIUS	=> __d('tools', 'zodiacAquarius'),
			static::SIGN_PISCES	=> __d('tools', 'zodiacPisces'),
			static::SIGN_ARIES	=> __d('tools', 'zodiacAries'),
			static::SIGN_TAURUS	=> __d('tools', 'zodiacTaurus'),
			static::SIGN_GEMINI	=> __d('tools', 'zodiacGemini'),
			static::SIGN_CANCER	=> __d('tools', 'zodiacCancer'),
			static::SIGN_LEO	=> __d('tools', 'zodiacLeo'),
			static::SIGN_VIRGO	=> __d('tools', 'zodiacVirgo'),
			static::SIGN_LIBRA	=> __d('tools', 'zodiacLibra'),
			static::SIGN_SCORPIO	=> __d('tools', 'zodiacScorpio'),
			static::SIGN_SAGITTARIUS	=> __d('tools', 'zodiacSagittarius'),
			static::SIGN_CAPRICORN	=> __d('tools', 'zodiacCapricorn'),
		];
		if ($value === null) {
			return $res;
		}
		return $res[$value];
	}

	/**
	 * ZodiacLib::image()
	 *
	 * @param int $sign
	 * @return string
	 */
	public static function image($sign) {
		return static::$res[$sign];
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
