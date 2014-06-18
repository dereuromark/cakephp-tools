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

	protected $_defaultConfig = array(
		'imageUrl' => 'http://www.google.com/ig/images/weather/'
	);

	public function __construct($View = null, $settings = array()) {
		$this->_defaultConfig = (array)Configure::read('Weather') + $this->_defaultConfig;
		parent::__construct($View, $settings + $this->_defaultConfig);
	}

	/**
	 * Generates icon URL.
	 *
	 * @param string $icon
	 * @param string $ext
	 * @param bool $full
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
	public function get($location, $options = array()) {
		$Weather = new WeatherLib();

		$defaults = array(
			'cache' => '+1 hour'
		);
		$options += $defaults;
		return $Weather->get($location, $options);
	}

}
