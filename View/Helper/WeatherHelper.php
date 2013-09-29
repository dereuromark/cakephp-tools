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

	public $imagePath = ''; //'http://www.google.com/ig/images/weather/';

	public $imageUrl = '';

	public function __construct($View = null, $settings = array()) {
		parent::__construct($View, $settings);

		$this->imageUrl = $this->imagePath;
	}

	/**
	 * Display a ready table
	 *
	 * //TODO
	 * @return string
	 */
	public function display($location) {
		$weather = $this->get($location);

		$res = '';
		if (empty($weather)) {
			return $res;
		}

		$res .= '<table><tr>';
		//$res .= '<td>'.[].'</td>';
		$res .= '</tr></table>';

		$res .= '<h1>' . h($weather['city']) . ':</h1>';

		return $res;
	}

	/**
	 * @return string
	 */
	public function imageUrl($icon, $full = false) {
		return $this->imageUrl . $icon;
	}

	/**
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
