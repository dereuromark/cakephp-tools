<?php
App::uses('AppHelper', 'View/Helper');
App::uses('DiffLib', 'Tools.Lib');

/**
 * DiffHelper class
 *
 * This class is a wrapper for PEAR Text_Diff with modified renderers from Horde
 * You need the stable Text_Diff from PEAR and (if you want to use them) two
 * renderers attached with this helper (sidebyside.php and character.php)
 *
 * To use this helper you either have to use the Vendor files in the Tools Plugin.
 * Wraps the DiffLib (which does all the heavy lifting) for the view layer.
 *
 * @author Marcin Domanski aka kabturek <blog@kabturek.info>
 * @license MIT
 * @modified Mark Scherer (Make it work with 2.x and clean it up into Lib + Helper)
 */
class DiffHelper extends AppHelper {

	public $helpers = array('Html');

	/**
	 * Construct function
	 * Loads the vendor classes and sets the include path for autoloader to work
	 *
	 */
	public function __construct($View = null, $settings = array()) {
		parent::__construct($View, $settings);

		$this->Diff = new DiffLib();
	}

	/**
	 * @param string $renderType
	 * 'unified', 'inline', 'context', 'sidebyside'
	 * defaults to "inline"
	 * @return boolean Success
	 */
	public function renderType($type = null) {
		return $this->Diff->renderType($type);
	}

	/**
	 * @param string $engineType
	 * 'auto', 'native', 'xdiff', 'shell', 'string'
	 * defaults to "auto"
	 * @return boolean Success
	 */
	public function engineType($type = null) {
		return $this->Diff->engineType($type);
	}

	/**
	 * Compare function
	 * Compares two strings/arrays using the specified method and renderer
	 *
	 * @param mixed $original
	 * @param mixed $changed
	 * @param array $options
	 * - div: true/false
	 * - class: defaults to "diff"
	 * - escape: defaults to true
	 * @return string output
	 */
	public function compare($original, $changed, $options = array()) {
		$original = $this->_prep($original);

		$changed = $this->_prep($changed);

		$string = $this->Diff->compare($original, $changed, $options);
		if (isset($options['div']) && $options['div'] === false) {
			return $string;
		}
		$defaults = array(
			'class' => 'diff'
		);
		$options = array_merge($defaults, $options);
		$options['escape'] = null;
		return $this->Html->tag('div', $string, $options);
	}

	/**
	 * @param string $string Either context or unified diff snippet
	 * @param array $options
	 * - mode (autodetect, context, unified)
	 * @return string
	 */
	public function reverse($string, $options = array()) {
		$string = $this->Diff->reverse($string, $options);
		if (isset($options['div']) && $options['div'] === false) {
			return $string;
		}
		$defaults = array(
			'class' => 'diff'
		);
		$options = array_merge($defaults, $options);
		$options['escape'] = null;
		return $this->Html->tag('div', $string, $options);
	}

	/**
	 * Prep for security
	 * maybe switch to do that after comparison?
	 *
	 * @param string $string
	 * @param array $options
	 * @return string
	 */
	protected function _prep($string, $options = array()) {
		if ($this->renderer === 'inline' || isset($options['escape']) && $options['escape'] === false) {
			return $string;
		}
		return h($string);
	}

}
