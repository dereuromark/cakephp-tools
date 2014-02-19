<?php
App::uses('CakeLog', 'Log');

/**
 * Googl Url Shortener
 * @see http://goo.gl
 *
 * @author Eslam Mahmoud
 * @url http://hunikal.com/
 * @copyright Creative Commons Attribution-ShareAlike 3.0 Unported License.
 * @version 0.1
 *
 * TODO: implement OAuth
 *
 * @edited Mark Scherer
 */
class GooglLib {

	const PROJECTION_FULL = 'FULL';

	const PROJECTION_CLICKS = 'ANALYTICS_CLICKS';

	const PROJECTION_TOP = 'ANALYTICS_TOP_STRINGS';

	/**
	 * Application key
	 */
	protected $APIKey;

	/**
	 * Api url
	 */
	protected $API = "https://www.googleapis.com/urlshortener/v1/url";

	/**
	 * @param string $apiKey (optional)
	 */
	public function __construct($apiKey = null) {
		if ($apiKey === null) {
			$apiKey = Configure::read('Googl.key');
		}
		if ($apiKey) {
			$this->APIKey = $apiKey;
		}
	}

	/**
	 * Reverse the shortening process
	 * TODO: rename to expand
	 *
	 * @param strin $url
	 * @return result as array
	 */
	public function getLong($shortURL, $projection = null) {
		$vars = '?shortUrl=' . $shortURL;
		if ($projection) {
			$vars .= '&projection=' . $projection;
		}
		if ($this->APIKey) {
			$vars .= '&key=' . $this->APIKey;
		}
		$ch = curl_init($this->API . $vars);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$array = json_decode($result, true);
		return $array;
	}

	/**
	 * Shorten a long url
	 *
	 * @param string $url
	 * @return array result as array or false on failure
	 */
	public function getShort($longURL) {
		$vars = '';
		if ($this->APIKey) {
			$vars .= "?key=$this->APIKey";
		}

		$ch = curl_init($this->API . $vars);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, '{"key": "' . $this->APIKey . '", "longUrl": "' . $longURL . '"}');
		$result = curl_exec($ch);
		curl_close($ch);
		$array = json_decode($result, true);
		if (empty($array['id'])) {
			// throw error?
			CakeLog::write('googl', $longURL . ' - ' . print_r($array, true));
			return false;
		}
		$separator = strrpos($array['id'], '/');
		$array['key'] = substr($array['id'], $separator + 1);
		return $array;
	}

	/**
	 * FIXME: not working yet
	 * TODO: use oacurl etc
	 *
	 * @return array
	 */
	public function getHistory() {
		$vars = '';
		$url = $this->API . '/history';
		$ch = curl_init($url . $vars);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$array = json_decode($result, true);
		return $array;
	}

	/**
	 * Retrieve the url for the statistics page for this key
	 *
	 * @param string $key
	 * @return string url
	 */
	public static function statisticsUrl($key) {
		$url = 'http://goo.gl/#analytics/goo.gl/' . $key . '/all_time';
		return $url;
	}

}
