<?php
App::uses('AppHelper', 'View/Helper');
App::uses('WeatherLib', 'Tools.Lib');

/**
 * Display weather in the view
 *
 * @author Mark Scherer
 * @license MIT
 */
class WeatherHelper extends AppHelper {

	public $helpers = array('Html');

	protected $_defaults = array(
		'imageUrl' => 'http://www.google.com/ig/images/weather/'
	);

	public function __construct($View = null, $settings = array()) {
		$this->_defaults = (array)Configure::read('Weather') + $this->_defaults;
		parent::__construct($View, $settings + $this->_defaults);
	}

	/**
	 * Generates icon URL.
	 *
	 * @param string $icon
	 * @param string $ext
	 * @param boolean $full
	 * @return string URL
	 */
	public function imageUrl($icon, $ext = 'gif', $full = false) {
		return $this->Html->url($this->settings['imageUrl'] . $icon . '.' . $ext, $full);
	}

	/**
	 * Gets weather data.
	 *
	 * @return array
	 */
	public function get($location, $cOptions = array()) {
		$Weather = new WeatherLib();

		$options = array(
			'cache' => '+1 hour'
		);
		$options = array_merge($options, $cOptions);
		return $Weather->get($location, $options);
	}

}
