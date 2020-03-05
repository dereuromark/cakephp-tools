<?php

namespace Tools\View\Helper;

use Cake\I18n\FrozenTime;
use Cake\View\Helper;

/**
 * TimelineHelper for easy output of a timeline with multiple items.
 *
 * You need to include your css and js file, manually:
 *
 *   echo $this->Html->script('timeline/timeline');
 *   echo $this->Html->css('/js/timeline/timeline');
 *
 * @link http://almende.github.io/chap-links-library/timeline.html
 * @author Mark Scherer
 * @license MIT
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class TimelineHelper extends Helper {

	/**
	 * @var array
	 */
	protected $helpers = ['Html'];

	/**
	 * Possible values are (with their default values):
	 *  - 'min',
	 *  - 'max',
	 *  - 'width'
	 *  - 'height'
	 *  - 'minHeight'
	 *  - 'selectable' => false,
	 *  - 'editable' => false,
	 *  - 'moveable' => true
	 *  - 'animate' => true,
	 *  - 'animateZoom' => true,
	 *  - 'axisOnTop' => false,
	 *  - 'cluster' => false
	 *  - 'locale' (string)
	 *  - 'style' (string)
	 *  - ...
	 *
	 * @link http://almende.github.io/chap-links-library/js/timeline/doc/
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'id' => 'mytimeline',
		'selectable' => false,
		'editable' => false,
		'min' => null, // Min date.
		'max' => null, // Max date.
		'width' => '100%',
		'height' => null, // Auto.
		'style' => 'box',
		'current' => null, // Current time.
	];

	/**
	 * @var array
	 */
	protected $_items = [];

	/**
	 * Add timeline item.
	 *
	 * Requires at least:
	 * - start (date or datetime)
	 * - content (string)
	 * Further data options:
	 * - end (date or datetime)
	 * - group (string)
	 * - className (string)
	 * - editable (boolean)
	 *
	 * @link http://almende.github.io/chap-links-library/js/timeline/doc/
	 * @param array $item
	 * @return void
	 */
	public function addItem($item) {
		$this->_items[] = $item;
	}

	/**
	 * Add timeline items as an array of items.
	 *
	 * @see \Tools\View\Helper\TimelineHelper::addItem()
	 * @param array $items
	 * @return void
	 */
	public function addItems($items) {
		foreach ($items as $item) {
			$this->_items[] = $item;
		}
	}

	/**
	 * Finalize the timeline and write the javascript to the buffer.
	 * Make sure that your view does also output the buffer at some place!
	 *
	 * @param bool $return If the output should be returned instead
	 * @param array $scriptOptions
	 *
	 * @return string|null Javascript if $return is true
	 */
	public function finalize($return = false, array $scriptOptions = []) {
		$settings = $this->getConfig();
		$timelineId = $settings['id'];
		$data = $this->_format($this->_items);

		$current = '';
		if ($settings['current']) {
			$now = new FrozenTime();
			$current = 'timeline.setCurrentTime(' . $this->_date($now) . ');';
		}
		unset($settings['id']);
		unset($settings['current']);
		$options = $this->_options($settings);

		$script = <<<JS
var timeline;
var data;
var options;

// Called when the Visualization API is loaded.
function drawVisualization() {
	// Create a JSON data table
	data = $data
	options = $options

	// Instantiate our timeline object.
	timeline = new links.Timeline(document.getElementById('$timelineId'));

	// Draw our timeline with the created data and options
	timeline.draw(data, options);
	$current
}

drawVisualization();
JS;
		if (!$return) {
			$this->_buffer($script, $scriptOptions);

			return null;
		}

		return $script;
	}

	/**
	 * Format options to JS code
	 *
	 * @param array $options
	 * @return string
	 */
	protected function _options($options) {
		$e = [];
		foreach ($options as $option => $value) {
			if ($value === null) {
				continue;
			}
			if (is_string($value)) {
				$value = '\'' . $value . '\'';
			} elseif (is_object($value)) { // Datetime?
				$value = $this->_date($value);
			} elseif (is_bool($value)) {
				$value = $value ? 'true' : 'false';
			} else {
				$value = str_replace('\'', '\\\'', $value);
			}
			$e[] = '\'' . $option . '\': ' . $value;
		}
		$string = '{' . PHP_EOL . "\t" . implode(',' . PHP_EOL . "\t", $e) . PHP_EOL . '}';
		return $string;
	}

	/**
	 * Format items to JS code
	 *
	 * @see \Tools\View\Helper\TimelineHelper::addItem()
	 * @param array $items
	 * @return string
	 */
	protected function _format($items) {
		$e = [];
		foreach ($items as $item) {
			$tmp = [];
			foreach ($item as $key => $row) {
				switch ($key) {
					case 'editable':
						$tmp[] = $row ? 'true' : 'false';
						break;
					case 'start':
					case 'end':
						$tmp[] = '\'' . $key . '\': ' . $this->_date($row);
						break;
					default:
						$tmp[] = '\'' . $key . '\': \'' . str_replace('\'', '\\\'', $row) . '\'';
				}
			}
			$e[] = '{' . implode(',' . PHP_EOL, $tmp) . '}';
		}
		$string = '[' . implode(',' . PHP_EOL, $e) . '];';
		return $string;
	}

	/**
	 * Format date to JS code.
	 *
	 * @param \DateTimeInterface|null $date
	 * @return string
	 */
	protected function _date($date = null) {
		if ($date === null || !$date instanceof \DateTimeInterface) {
			return '';
		}
		$datePieces = [];
		$datePieces[] = $date->format('Y');
		// JavaScript uses 0-indexed months, so we need to subtract 1 month from PHP's output
		$datePieces[] = (int)($date->format('m')) - 1;
		$datePieces[] = (int)$date->format('d');
		$datePieces[] = (int)$date->format('H');
		$datePieces[] = (int)$date->format('i');
		$datePieces[] = (int)$date->format('s');

		return 'new Date(' . implode(', ', $datePieces) . ')';
	}

	/**
	 * Options:
	 * - `safe` (boolean) Whether or not the $script should be wrapped in `<![CDATA[ ]]>`.
	 *   Defaults to `false`.
	 * - `block` You can chose a different view block to write to (defaults to "script" one).
	 *
	 * @param string $script
	 * @param array $options
	 *
	 * @return void
	 */
	protected function _buffer($script, array $options = []) {
		$defaults = [
			'block' => true,
		];
		$options += $defaults;

		$this->Html->scriptBlock($script, $options);
	}

}
