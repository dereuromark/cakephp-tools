<?php

namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Cake\View\View;
use InvalidArgumentException;
use Tools\Utility\Number;

/**
 * Use the meter element to display data within a given range (a gauge).
 *
 * Examples: Disk usage, the relevance of a query result, etc. Fixed values.
 *
 * Note: The <meter> tag should not be used to indicate progress (as in a progress bar). Use Progress helper here.
 *
 * @author Mark Scherer
 * @license MIT
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class MeterHelper extends Helper {

	const LENGTH_MIN = 3;
	const CHAR_EMPTY = '░';
	const CHAR_FULL = '█';

	/**
	 * @var array
	 */
	protected $helpers = ['Html'];

	/**
	 * @var array
	 */
	protected $_defaults = [
		'empty' => self::CHAR_EMPTY,
		'full' => self::CHAR_FULL,
		'precision' => 6,
	];

	/**
	 * @param \Cake\View\View $View
	 * @param array $config
	 */
	public function __construct(View $View, array $config = []) {
		$defaults = (array)Configure::read('Meter') + $this->_defaults;
		$config += $defaults;

		parent::__construct($View, $config);
	}

	/**
	 * Creates HTML5 meter element.
	 *
	 * Note: This requires a textual fallback for IE12 and below.
	 *
	 * Options:
	 * - fallbackHtml: Use a fallback string if the browser cannot display this type of HTML5 element
	 * - overflow: Set to true to allow the value to move the max/min boundaries
	 *
	 * @param float $value
	 * @param float $max
	 * @param float|null $min
	 * @param array $options
	 * @param array $attributes
	 * @return string
	 */
	public function htmlMeterBar($value, $max, $min = null, array $options = [], array $attributes = []) {
		$defaults = [
			'fallbackHtml' => null,
			'overflow' => false,
		];
		$options += $defaults;

		$value = $this->prepareValue($value, $max, $min, $options['overflow']);
		$max = $this->prepareMax($value, $max, $options['overflow']);
		$min = $this->prepareMin($value, $min, $options['overflow']);

		$progress = $this->calculatePercentage($max - $min, $value - $min);

		$attributes += [
			'value' => $value,
			'min' => $min < 0 ? 0 : $min,
			'max' => $max,
			'title' => Number::toPercentage($progress, 0, ['multiply' => true]),
		];

		$fallback = '';
		if ($options['fallbackHtml']) {
			$fallback = $options['fallbackHtml'];
		}

		return $this->Html->tag('meter', $fallback, $attributes);
	}

	/**
	 * @param float|int $total
	 * @param float|int $is
	 * @return float
	 */
	protected function calculatePercentage($total, $is) {
		$percentage = $total ? $is / $total : 0.0;

		return $percentage;
	}

	/**
	 * Creates text based meter element.
	 *
	 * @param float $value
	 * @param float $max
	 * @param float $min
	 * @param int $length As char count
	 * @param array $options
	 * @param array $attributes
	 * @return string
	 */
	public function meterBar($value, $max, $min, $length, array $options = [], array $attributes = []) {
		$defaults = [
			'overflow' => false,
		];
		$options += $defaults;

		$value = $this->prepareValue($value, $max, $min, $options['overflow']);
		$max = $this->prepareMax($value, $max, $options['overflow']);
		$min = $this->prepareMin($value, $min, $options['overflow']);

		$progress = $this->calculatePercentage($max - $min, $value - $min);
		$bar = $this->draw($progress, $length);

		$attributes += [
			'title' => Number::toPercentage($progress, 0, ['multiply' => true]),
		];

		return $this->Html->tag('span', $bar, $attributes);
	}

	/**
	 * Render the progress bar based on the current state.
	 *
	 * @param float $complete
	 * @param int $length
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function draw($complete, $length) {
		if ($length < static::LENGTH_MIN) {
			throw new InvalidArgumentException('Min length for such a progress bar is ' . static::LENGTH_MIN);
		}

		$barLength = $this->calculateBarLength($complete, $length);

		$bar = '';
		if ($barLength > 0) {
			$bar = str_repeat($this->getConfig('full'), $barLength);
		}

		$pad = $length - $barLength;
		if ($pad > 0) {
			$bar .= str_repeat($this->getConfig('empty'), $pad);
		}

		return $bar;
	}

	/**
	 * @param float $complete Value between 0 and 1.
	 * @param int $length
	 * @return int
	 */
	protected function calculateBarLength($complete, $length) {
		$barLength = (int)round($length * $complete, 0);

		return $barLength;
	}

	/**
	 * Prepares the input value based on max/min and if
	 * - overflow: adjust the min/max by value
	 * - not overflow: adjust the value by min/max
	 *
	 * Also: Rounds as per reasonable precision based on exponent
	 *
	 * @param float $value
	 * @param float $max
	 * @param float $min
	 * @param bool $overflow
	 * @return float
	 * @throws \InvalidArgumentException
	 */
	protected function prepareValue($value, $max, $min, $overflow) {
		if ($max < $min) {
			throw new InvalidArgumentException('Max needs to be larger than Min.');
		}

		if ($value > $max && !$overflow) {
			$value = $max;
		}
		if ($value < $min && !$overflow) {
			$value = $min;
		}

		return $this->roundValue($value);
	}

	/**
	 * Prepares the max value
	 * - overflow: adjust the min/max by value
	 * - not overflow: adjust the value by min/max
	 *
	 * Also: Rounds as per reasonable precision based on exponent
	 *
	 * @param float $value
	 * @param float $max
	 * @param bool $overflow
	 * @return float
	 */
	protected function prepareMax($value, $max, $overflow) {
		if ($value <= $max) {
			return $max;
		}

		if ($overflow) {
			return $value;
		}

		return $max;
	}

	/**
	 * Prepares the min value
	 * - overflow: adjust the min/max by value
	 * - not overflow: adjust the value by min/max
	 *
	 * Also: Rounds as per reasonable precision based on exponent
	 *
	 * @param float $value
	 * @param float $min
	 * @param bool $overflow
	 * @return float
	 */
	protected function prepareMin($value, $min, $overflow) {
		if ($value > $min) {
			return $min;
		}

		if ($overflow) {
			return $value;
		}

		return $min;
	}

	/**
	 * @param float $value
	 *
	 * @return float
	 */
	protected function roundValue($value) {
		$precision = (int)$this->getConfig('precision');

		$string = (string)$value;
		if ($precision === -1 || strlen($string) < $precision) {
			return $value;
		}

		$separatorIndex = strpos($string, '.');
		$positive = $separatorIndex ?: 0;

		$left = $precision - $positive;

		return round($value, $left);
	}

}
