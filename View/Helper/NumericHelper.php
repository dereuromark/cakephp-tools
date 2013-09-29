<?php

App::uses('NumberHelper', 'View/Helper');
//App::uses('NumberLib', 'Tools.Utility');

/**
 * Todo: rename to MyNumberHelper some day?
 * Aliasing it then as Number again in the project
 *
 */
class NumericHelper extends NumberHelper {

	//public $helpers = array();
	/*
	protected $_settings = array(
	);

	protected $code = null;
	protected $places = 0;
	protected $symbolRight = null;
	protected $symbolLeft = null;
	protected $decimalPoint = '.';
	protected $thousandsPoint = ',';
	*/

	public function __construct($View = null, $settings = array()) {
		$settings = Set::merge(array('engine' => 'Tools.NumberLib'), $settings);
		parent::__construct($View, $settings);
		/*
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
		*/
	}

}
