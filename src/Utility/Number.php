<?php

namespace Tools\Utility;

use Cake\I18n\Number as CakeNumber;

/**
 * Extend CakeNumber with a few important improvements:
 * - config setting for format()
 * - spacer char for currency (initially from https://github.com/cakephp/cakephp/pull/1148)
 * - signed values possible
 */
class Number extends CakeNumber {

	/**
	 * @var array
	 */
	protected static $_currencies = [];

	/**
	 * @var string
	 */
	protected static $_currency = 'EUR';

	/**
	 * @var string
	 */
	protected static $_symbolRight = '€';

	/**
	 * @var string
	 */
	protected static $_symbolLeft = '';

	/**
	 * @var string
	 */
	protected static $_decimals = ',';

	/**
	 * @var string
	 */
	protected static $_thousands = '.';

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
	 * @param array $formatOptions Format options: currency=true/false, ... (leave empty for no special treatment)
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

		$defaults = ['before' => '', 'after' => '', 'places' => 2, 'thousands' => static::$_thousands, 'decimals' => static::$_decimals, 'escape' => false];
		$options = $formatOptions + $defaults;

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

	/**
	 * Format
	 *
	 * Additional options
	 * - signed
	 * - positive
	 *
	 * @param float $number
	 * @param array $options
	 * @return string
	 */
	public static function format($number, array $options = []): string {
		$defaults = [
			'positive' => '+', 'signed' => false,
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
	 * @param string|null $currency
	 * @param array $options
	 * @return string
	 */
	public static function currency($number, ?string $currency = null, array $options = []): string {
		$defaults = [
			'positive' => '+', 'signed' => false,
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
	 * @param string $decimals
	 * @return string Human readable size
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/number.html#NumberHelper::toReadableSize
	 */
	public static function _toReadableSize($size, $decimals = '.'): string {
		$size = parent::toReadableSize($size);
		if ($decimals !== '.') {
			$size = str_replace('.', $decimals, $size);
		}
		return $size;
	}

	/**
	 * Get the rounded average.
	 *
	 * @param array $values Values: int or float values
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
	public static function roundTo($number, $increment = 1.0) {
		$precision = static::getDecimalPlaces($increment);
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
	public static function roundUpTo($number, $increment = 1) {
		return ceil($number / $increment) * $increment;
	}

	/**
	 * Round value down.
	 *
	 * @param float $number
	 * @param int $increment
	 * @return float result
	 */
	public static function roundDownTo($number, $increment = 1) {
		return floor($number / $increment) * $increment;
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
	 * @return array currencySettings
	 */
	public static function getFormat($formatName) {
		if (!isset(static::$_currencies[$formatName])) {
			return [];
		}
		return static::$_currencies[$formatName];
	}

}
