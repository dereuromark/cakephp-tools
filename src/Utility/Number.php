<?php
namespace Tools\Utility;

use Cake\Core\Configure;
use Cake\I18n\Number as CakeNumber;

/**
 * Extend CakeNumber with a few important improvements:
 * - config setting for format()
 * - spacer char for currency (initially from https://github.com/cakephp/cakephp/pull/1148)
 * - signed values possible
 *
 */
class Number extends CakeNumber {

	protected static $_currency = 'EUR';

	protected static $_symbolRight = '€';

	protected static $_symbolLeft = '';

	protected static $_decimals = ',';

	protected static $_thousands = '.';

	/**
	 * Correct the defaul values according to localization
	 *
	 * @return void
	 */
	public static function config($options = []) {
		$config = $options + (array)Configure::read('Localization');
		foreach ($config as $key => $value) {
			$key = '_' . $key;
			if (!isset(static::${$key})) {
				continue;
			}
			static::${$key} = $value;
		}
	}

	/**
	 * Convenience method to display the default currency
	 *
	 * @param float $amount
	 * @param array $formatOptions
	 * @return string
	 */
	public static function money($amount, array $formatOptions = []) {
		return static::currency($amount, null, $formatOptions);
	}

	/**
	 * Format numeric values
	 * should not be used for currencies
	 * //TODO: automize per localeconv() ?
	 *
	 * @param float $number
	 * @param array $options : currency=true/false, ... (leave empty for no special treatment)
	 * @return string
	 */
	public static function _format($number, array $formatOptions = []) {
		if (!is_numeric($number)) {
			$default = '---';
			if (!empty($options['default'])) {
				$default = $options['default'];
			}
			return $default;
		}
		if ($formatOptions === false) {
			$formatOptions = [];
		} elseif (!is_array($formatOptions)) {
			$formatOptions = ['places' => $formatOptions];
		}
		$options = ['before' => '', 'after' => '', 'places' => 2, 'thousands' => static::$_thousands, 'decimals' => static::$_decimals, 'escape' => false];
		$options = $formatOptions + $options;

		if (!empty($options['currency'])) {
			if (!empty(static::$_symbolRight)) {
				$options['after'] = ' ' . static::$_symbolRight;
			} elseif (!empty(static::$_symbolLeft)) {
				$options['before'] = static::$_symbolLeft . ' ';
			}
		}

		/*
		if ($spacer !== false) {
			$spacer = ($spacer === true) ? ' ' : $spacer;
			if ((string)$before !== '') {
				$before .= $spacer;
			}
			if ((string)$after !== '') {
				$after = $spacer . $after;
			}
		}

		*/
		if ($options['places'] < 0) {
			$number = round($number, $options['places']);
		}
		$sign = '';
		if ($number > 0 && !empty($options['signed'])) {
			$sign = '+';
		}
		if (isset($options['signed'])) {
			unset($options['signed']);
		}
		return $sign . parent::format($number, $options);
	}

	public static function format($number, array $options = []) {
		$defaults = [
			'positive' => '+', 'signed' => false
		];
		$options += $defaults;
		$sign = '';
		if ($number > 0 && !empty($options['signed'])) {
			$sign = '+';
		}
		if (isset($options['signed'])) {
			unset($options['signed']);
		}
		return $sign . parent::format($number, $options);
	}

	/**
	 * Overwrite to allow
	 *
	 * - signed: true/false
	 *
	 * @param float $number
	 * @param string $currency
	 * @param array $options
	 * @return string
	 */
	public static function currency($number, $currency = null, array $options = []) {
		$defaults = [
			'positive' => '+', 'signed' => false
		];
		$options += $defaults;
		$sign = '';
		if ($number > 0 && !empty($options['signed'])) {
			$sign = $options['positive'];
		}
		return $sign . parent::currency($number, $currency, $options);
	}

	/**
	 * Returns a formatted-for-humans file size.
	 *
	 * @param int $size Size in bytes
	 * @return string Human readable size
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
	 */
	public static function _toReadableSize($size, $decimals = '.') {
		$size = parent::toReadableSize($size);
		if ($decimals !== '.') {
			$size = str_replace('.', $decimals, $size);
		}
		return $size;
	}

	/**
	 * Get the rounded average.
	 *
	 * @param array $values: int or float values
	 * @param int $precision
	 * @return float Average
	 */
	public static function average($values, $precision = 0) {
		if (empty($values)) {
			return 0.0;
		}
		return round(array_sum($values) / count($values), $precision);
	}

	/**
	 * Round value.
	 *
	 * @param float $number
	 * @param float $increment
	 * @return float result
	 */
	public static function roundTo($number, $increments = 1.0) {
		$precision = static::getDecimalPlaces($increments);
		$res = round($number, $precision);
		if ($precision <= 0) {
			$res = (int)$res;
		}
		return $res;
	}

	/**
	 * Round value up.
	 *
	 * @param float $number
	 * @param int $increment
	 * @return float result
	 */
	public static function roundUpTo($number, $increments = 1) {
		return (ceil($number / $increments) * $increments);
	}

	/**
	 * Round value down.
	 *
	 * @param float $number
	 * @param int $increment
	 * @return float result
	 */
	public static function roundDownTo($number, $increments = 1) {
		return (floor($number / $increments) * $increments);
	}

	/**
	 * Get decimal places
	 *
	 * @param float $number
	 * @return int decimalPlaces
	 */
	public static function getDecimalPlaces($number) {
		$decimalPlaces = 0;
		while ($number > 1 && $number != 0) {
			$number /= 10;
			$decimalPlaces -= 1;
		}
		while ($number < 1 && $number != 0) {
			$number *= 10;
			$decimalPlaces += 1;
		}
		return $decimalPlaces;
	}

	/**
	 * Returns the English ordinal suffix (th, st, nd, etc) of a number.
	 *
	 * echo NumberLib::ordinal(2); // "nd"
	 * echo NumberLib::ordinal(10); // "th"
	 * echo NumberLib::ordinal(33); // "rd"
	 *
	 * @param int $number
	 * @return string
	 */
	public static function ordinal($number) {
		if ($number % 100 > 10 && $number % 100 < 14) {
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

	/**
	 * Can compare two float values
	 *
	 * @link http://php.net/manual/en/language.types.float.php
	 * @param float $x
	 * @param float $y
	 * @param float $precision
	 * @return bool
	 */
	public static function isFloatEqual($x, $y, $precision = 0.0000001) {
		return ($x + $precision >= $y) && ($x - $precision <= $y);
	}

	/**
	 * Get the settings for a specific formatName
	 *
	 * @param string $formatName (EUR, ...)
	 * @return array currencySettings or null on failure
	 */
	public static function getFormat($formatName) {
		if (!isset(static::$_currencies[$formatName])) {
			return null;
		}
		return static::$_currencies[$formatName];
	}

}
