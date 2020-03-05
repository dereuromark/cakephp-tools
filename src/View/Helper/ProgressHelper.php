<?php

namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Cake\View\View;
use InvalidArgumentException;
use Tools\Utility\Number;

/**
 * The progress element represents the progress of a task.
 *
 * Tip: Use the <progress> tag in conjunction with JavaScript to display the progress of a task.
 *
 * Note: The <progress> tag is not suitable for representing a gauge (e.g. disk space usage or relevance of a query result).
 * To represent a gauge, use the Meter helper instead.
 *
 * @author Mark Scherer
 * @license MIT
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ProgressHelper extends Helper {

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
	];

	/**
	 * @param \Cake\View\View $View
	 * @param array $config
	 */
	public function __construct(View $View, array $config = []) {
		$defaults = (array)Configure::read('Progress') + $this->_defaults;
		$config += $defaults;

		parent::__construct($View, $config);
	}

	/**
	 * Creates HTML5 progress element.
	 *
	 * Note: This requires a textual fallback for IE9 and below.
	 *
	 * Options:
	 *
	 * @param float $value Value 0...1
	 * @param array $options
	 * @param array $attributes
	 * @return string
	 */
	public function htmlProgressBar($value, array $options = [], array $attributes = []) {
		$defaults = [
			'fallbackHtml' => null,
		];
		$options += $defaults;

		$progress = $this->roundPercentage($value);

		$attributes += [
			'value' => number_format($progress * 100, 0),
			'max' => '100',
			'title' => Number::toPercentage($progress, 0, ['multiply' => true]),
		];

		$fallback = '';
		if ($options['fallbackHtml']) {
			$fallback = $options['fallbackHtml'];
		}

		return $this->Html->tag('progress', $fallback, $attributes);
	}

	/**
	 * @param float $value Value 0...1
	 * @param int $length As char count
	 * @param array $attributes
	 * @return string
	 */
	public function progressBar($value, $length, array $attributes = []) {
		$bar = $this->draw($value, $length);

		$attributes += [
			'title' => Number::toPercentage($this->roundPercentage($value), 0, ['multiply' => true]),
		];

		return $this->Html->tag('span', $bar, $attributes);
	}

	/**
	 * Render the progress bar based on the current state.
	 *
	 * @param float $complete Value between 0 and 1.
	 * @param int $length Bar length.
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function draw($complete, $length) {
		if ($complete < 0.0 || $complete > 1.0) {
			throw new InvalidArgumentException('Min/Max overflow for value `' . $complete . '` (0...1)');
		}
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
	 * @param float|int $total
	 * @param float|int $is
	 * @return float
	 */
	public function calculatePercentage($total, $is) {
		$percentage = $total ? $is / $total : 0.0;

		return $this->roundPercentage($percentage);
	}

	/**
	 * @param float $percentage
	 * @return float
	 */
	public function roundPercentage($percentage) {
		$percentageRounded = round($percentage, 2);
		if ($percentageRounded === 0.00 && $percentage > 0.0) {
			$percentage = 0.01;
		}
		if ($percentageRounded === 1.00 && $percentage < 1.0) {
			$percentage = 0.99;
		}

		return (float)$percentage;
	}

	/**
	 * @param float $complete
	 * @param int $length
	 * @return int
	 */
	protected function calculateBarLength($complete, $length) {
		$barLength = (int)round($length * $complete, 0);
		if ($barLength === 0 && $complete > 0.0) {
			$barLength = 1;
		}
		if ($barLength === $length && $complete < 1.0) {
			$barLength = $length - 1;
		}

		return $barLength;
	}

}
