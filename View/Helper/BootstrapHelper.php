<?php
App::uses('AppHelper', 'View/Helper');

/**
 * Generic html snippets to display some specific widgets like accordion
 *
 * Note:
 * This is meant to work with TwitterBootstrap (Layout and Script), but
 * should also work with other custom solutions (if they use the same selectors).
 *
 * Dependencies:
 * The respective JS plugins for the widgets.
 *
 * @license MIT
 * @author Mark Scherer
 * @cakephp 2.x
 * @php 5
 * @version 1.0
 */
class BootstrapHelper extends AppHelper {

	public $helpers = array('Html', 'Form');

	protected $_count = 1;

	protected $_items = array();

	public function xx() {
		//
	}

	/**
	 * Complete typeahead form input element
	 *
	 * @param fieldName
	 * @param options:
	 * - data (array of strings)
	 * - items (defaults to 8)
	 * @return string html
	 */
	public function typeahead($fieldName, $options = array(), $inputOptions = array()) {
		$inputOptions['data-provide'] = 'typeahead';
		//$inputOptions['data-source'] = $this->_formatSource($options['data']);
		if (!empty($options['items'])) {
			$inputOptions['data-items'] = (int)$options['items'];
		}
		$class = 'typeahead_' . strtolower(Inflector::slug($fieldName)); // str_replace('.', '_', $fieldName);
		$inputOptions['class'] = empty($inputOptions['class']) ? $class : $inputOptions['class'] . ' ' . $class;

		$script = '
	$(\'.' . $class . '\').typeahead({
		source: ' . $this->_formatSource($options['data']) . '
	})
';
		$script = PHP_EOL . '<script>' . $script . '</script>';
		return $this->Form->input($fieldName, $inputOptions) . $script;
	}

	public function _formatSource($elements) {
		//$res = array();
		//return '[\''.implode('\',\'', $elements).'\']';
		return json_encode($elements);
	}

	/**
	 * Complete carousel container
	 *
	 * @param array $items (heading, content, active)
	 * @param id
	 * @param array $options
	 * @return string html
	 */
	public function carousel($items, $id = null, $globalOptions = array()) {
		$res = '<div id="myCarousel" class="carousel">
	<div class="carousel-inner">
		' . $this->carouselItems($items, $globalOptions) . '
	</div>
	' . $this->carouselControl() . '
</div>';
	return $res;
	}

	public function carouselControl() {
		$res = '<a class="carousel-control left" href="#myCarousel" data-slide="prev">&lsaquo;</a>
	<a class="carousel-control right" href="#myCarousel" data-slide="next">&rsaquo;</a>';
		return $res;
	}

	/**
	 * Items of a carousel container
	 *
	 * @param array $items (heading, content, active)
	 * - active (visible, true/false)
	 * @return string html
	 */
	public function carouselItems($items, $options = array()) {
		$res = array();
		foreach ($items as $key => $item) {
			$active = '';
			if ($key == 0 && !isset($item['active']) || !empty($item['active'])) {
				$active = ' active';
			}
			$tmp = $item['content'];
			if (!empty($item['heading'])) {
				$tmp .= '<div class="carousel-caption">' . $item['heading'] . '</div>';
			}
			$tmp = '<div class="item' . $active . '">' . $tmp . '</div>';
			$res[] = $tmp;
		}
		$res = implode(PHP_EOL, $res);
		return $res;
	}

	/**
	 * Complete accordion container
	 *
	 * @param array $records (heading, content, options)
	 * @param id
	 * @param array $options
	 * @return string html
	 */
	public function accordion($records, $id = null, $globalOptions = array()) {
		$res = '<div class="accordion" id="accordion' . $id . '">';
		foreach ($records as $record) {
			$options = $globalOptions;
			extract($record);
			$res .= $this->accordionGroup($heading, $content, $options);
		}
		$res .= '</div>';
		return $res;
	}

	/**
	 * A single group of an accordion container
	 *
	 * @param string $heading
	 * @param string $content
	 * @param array $options
	 * - active (collapsed, true/false)
	 * @return string html
	 */
	public function accordionGroup($heading, $content, $options = array()) {
		$i = $this->_count;
		$this->_count++;
		$in = '';
		if ($i == 1 && !isset($options['active']) || !empty($options['active'])) {
			$in = ' in';
		}

		$res = '<div class="accordion-group">';
		$res .= '	<div class="accordion-heading">';

		$res .= '		<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#collapse' . $i . '">';
		$res .= $heading;
		$res .= '		</a>';
		$res .= '	</div>';
		$res .= '	<div id="collapse' . $i . '" class="accordion-body collapse' . $in . '">';
		$res .= '	<div class="accordion-inner">';
		$res .= $content;
		$res .= '	</div>';
		$res .= '	</div>';
		$res .= '</div>';
		return $res;
	}

}
