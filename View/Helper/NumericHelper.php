<?php

App::import('Helper', 'Number');

class NumericHelper extends NumberHelper {
	public $helpers = array('Session');

	protected $_settings = array(
	
	);

	protected $code = null;
	protected $places = 0;
	protected $symbolRight = null;
	protected $symbolLeft = null;
	protected $decimalPoint = '.';
	protected $thousandsPoint = ',';

	public function __construct($View = null, $settings = array()) {
		parent::__construct($View, $settings);	
			
		$i18n = Configure::read('Currency');
		if (!empty($i18n['code'])) {
			$this->code = $i18n['code'];
		}
		if (!empty($i18n['places'])) {
			$this->places = $i18n['places'];
		}
		if (!empty($i18n['symbolRight'])) {
			$this->symbolRight = $i18n['symbolRight'];
		}
		if (!empty($i18n['symbolLeft'])) {
			$this->symbolLeft = $i18n['symbolLeft'];
		}
		if (isset($i18n['decimals'])) {
			$this->decimalPoint = $i18n['decimals'];
		}
		if (isset($i18n['thousands'])) {
			$this->thousandsPoint = $i18n['thousands'];
		}
	}
	
	/**
	 * like price but with negative values allowed
	 * @return string
	 * 2011-10-05 ms
	 */
	public function money($amount, $places = null, $formatOptions = array()) {
		$formatOptions['allowNegative'] = true;
		return $this->price($amount, null, $places, $formatOptions);
	}
	
	/**
	 * @param price
	 * @param specialPrice (outranks the price)
	 * @param places
	 * @param options
	 * - allowNegative (defaults to false - price needs to be > 0)
	 * - currency (defaults to true)
	 * @return string
	 * 2011-07-30 ms
	 */
	public function price($price, $specialPrice = null, $places = null, $formatOptions = array()) {
		if ($specialPrice !== null && (float)$specialPrice > 0) {
			$val = $specialPrice;
		} elseif ((float)$price > 0 || !empty($formatOptions['allowNegative'])) {
			$val = $price;
		} else {
			return '---';
		}

		if ($places === null) {
			$places = 2;
		}
		$options = array('currency' => true);
		if (!empty($formatOptions)) {
			$options = array_merge($options, $formatOptions); # Set::merge not neccessary
		}
		
		
		return $this->format($val, $places, $options); // ->currency()
	}

	/**
	 * format numeric values
	 * @param float $number
	 * @param int $places (0 = int, 1..x places after dec, -1..-x places before dec)
	 * @param array $option : currency=true/false, ... (leave empty for no special treatment)
	 * //TODO: automize per localeconv() ?
	 * 2009-04-03 ms
	 */
	public function format($number, $places = null, $formatOptions = array()) {
		if (!is_numeric($number)) {
			return '---';
		}
		if (!is_integer($places)) {
			$places = 2;
		}
		$options = array('before' => '', 'after' => '', 'places' => $places, 'thousands' => $this->thousandsPoint, 'decimals' => $this->
			decimalPoint, 'escape' => false);

		if (!empty($formatOptions['currency'])) {
			if (!empty($this->symbolRight)) {
				$options['after'] = ' ' . $this->symbolRight;
			} elseif (!empty($this->symbolLeft)) {
				$options['before'] = $this->symbolLeft . ' ';
			} else {

			}
		} else {
			if (!empty($formatOptions['after'])) {
				$options['after'] = $formatOptions['after'];
			}
			if (!empty($formatOptions['before'])) {
				$options['before'] = $formatOptions['before'];
			}
		}

		if (!empty($formatOptions['thousands'])) {
			$options['thousands'] = $formatOptions['thousands'];
		}
		if (!empty($formatOptions['decimals'])) {
			$options['decimals'] = $formatOptions['decimals'];
		}
		if ($places < 0) {
			$number = round($number, $places);
		}
		return parent::format($number, $options);
	}

	/**
	 * Returns the English ordinal suffix (th, st, nd, etc) of a number.
	 *
	 *     echo 2, Num::ordinal(2);   // "2nd"
	 *     echo 10, Num::ordinal(10); // "10th"
	 *     echo 33, Num::ordinal(33); // "33rd"
	 *
	 * @param   integer  number
	 * @return  string
	 */
	public static function ordinal($number) {
		if ($number % 100 > 10 and $number % 100 < 14) {
			return 'th';
		}
		switch ($number % 10) {
			case 1:
				return 'st';
			case 2:
				return 'nd';
			case 3:
				return 'rd';
			default:
				return 'th';
		}
	}

}