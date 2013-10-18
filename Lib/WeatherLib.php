<?php
/**
 * alternatives:
 * http://simplepie.org/wiki/addons/yahoo_weather / http://cam.pl24.de/homepage-wetter.php
 * http://www.phpclasses.org/browse/file/11524.html
 * example: http://weather.yahooapis.com/forecastrss?p=GMXX0154
 * http://www.webmashup.com/API/Weather/Yahoo-Weather-API-l1871.html
 * http://developer.yahoo.com/weather/
 * http://www.webmashup.com/API/Weather/AccuWeather-API-l1862.html
 * http://www2.voegeli.li/no_cache/code-tutorials/php-scripts/class-weather-v2.html?L=1
 */
App::uses('Xml', 'Utility');
App::uses('HttpSocket', 'Network/Http');

/**
 * WeatherLib to retreive the current weather + forecast
 *
 * You can use Configure::write('Weather', ...) to adjust settings for it globabally via configs:
 * - key (recommended)
 * - free (true/false)
 * - format (json, csv, xml)
 * - num_of_days (defaults to 5)
 *
 * @author Mark Scherer
 * @license MIT
 * @see http://developer.worldweatheronline.com/documentation
 */
class WeatherLib {

	const API_URL = 'http://api.worldweatheronline.com/premium/v1/';

	const API_URL_FREE = 'http://api.worldweatheronline.com/free/v1/';

	public $settings = array(
		'format' => 'xml',
		'num_of_days' => 5,
		'q' => '', # e.g. 48.00,11.00
		'key' => '',
		'free' => true,
	);

	public function __construct() {
		$this->settings = array_merge($this->settings, (array)Configure::read('Weather'));
	}

	/**
	 * Get weather data - cached.
	 * Provide cache=>false to prevent/clear the cache
	 *
	 * @return array Data or false on failure
	 */
	public function get($q, $options = array()) {
		$options = array_merge($this->settings, $options);
		$options['q'] = urlencode($q);
		$data = $this->_get('weather.ashx', $options);
		if (empty($data) || empty($data['data'])) {
			return false;
		}
		return $data['data'];
	}

	/**
	 * Get weather conditions - cached.
	 * Provide cache=>false to prevent/clear the cache
	 *
	 * @return array
	 */
	public function conditions() {
		$options = array();
		$options['format'] = $this->settings['format'];
		if ($options['format'] === 'json') {
			$options['format'] = 'xml';
		}
		$conditions = $this->_get('wwoConditionCodes.xml', $options);
		if (empty($conditions) || empty($conditions['codes']['condition'])) {
			return array();
		}
		return $conditions['codes']['condition'];
	}

	//.../feed/weather.ashx?q=Neufahrn&format=json&num_of_days=2&key=598dfbdaeb121715111208

	public function _get($url, $options) {
		if (isset($options['cache'])) {
			$cache = $options['cache'];
			unset($options['cache']);
		}
		$url = $this->_url($url, $options);
		if (!empty($cache)) {
			if (!Cache::isInitialized('data')) {
				Cache::set('duration', $cache, 'data');
			}
			if ($cacheContent = Cache::read(md5($url), 'data')) {
				return $cacheContent;
			}
		}

		$Socket = new HttpSocket(array('timeout' => 5));
		$file = $Socket->get($url);
		$content = $file->body;
		if (empty($content)) {
			return false;
		}
		switch ($options['format']) {
			case 'json':
				$res = json_decode($content);
				break;
			case 'xml':
				$res = Xml::build($content);
				$res = Xml::toArray($res);
				break;
			case 'csv':
				//TODO?
				$res = array();
				throw new CakeException('Not implemented yet');
		}

		if (!empty($cache)) {
			Cache::write(md5($url), $res, 'data');
		}

		if (empty($res)) {
			return false;
		}
		return $res;
	}

	/**
	 * @return string Url
	 */
	public function _url($url, $options = array()) {
		$params = array();
		foreach ($options as $key => $option) {
			$params[] = $key . '=' . $option;
		}
		$params = (!empty($params) ? '?' : '') . implode('&', $params);
		$domain = $this->settings['free'] ? self::API_URL_FREE : self::API_URL;
		return $domain . $url . $params;
	}

}
