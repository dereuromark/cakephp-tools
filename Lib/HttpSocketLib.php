<?php
App::uses('HttpSocket', 'Network/Http');
App::uses('CurlLib', 'Tools.Lib');

/**
 * Wrapper for curl, php or file_get_contents
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.0
 * 2011-10-14 ms
 */
class HttpSocketLib {

	// First tries with curl, then cake, then php
	public $use = array('curl' => true, 'cake'=> true, 'php' => true);
	public $debug = null;
	public $timeout = 5;
	public $cacheUsed = null;
	public $error = array();

	public function __construct($use = array()) {
		if (is_array($use)) {
			foreach ($use as $key => $value) {
				if (array_key_exists($key, $this->use)) {
					$this->use[$key] = $value;
				}
			}
		} elseif (array_key_exists($use, $this->use)) {
			$this->use[$use] = true;
			if ($use == 'cake') {
				$this->use['curl'] = false;
			} elseif ($use == 'php') {
				$this->use['curl'] = $this->use['cake'] = false;
			}
		}
	}

	public function setError($error) {
		if (empty($error)) {
			return;
		}
		$this->error[] = $error;
	}

	public function error($asString = true, $separator = ', ') {
		return implode(', ', $this->error);
	}

	public function reset() {
		$this->error = array();
		$this->debug = null;
	}


	/**
	 * fetches url with curl if available
	 * fallbacks: cake and php
	 * note: expects url with json encoded content
	 * @access private
	 **/
	public function fetch($url, $options = array()) {
		if (!is_array($options)) {
			$options = array('agent'=>$options);
		}
		$defaults = array(
			'agent' => 'cakephp http socket lib',
			'cache' => false,
			'clearCache' => false,
			'use' => $this->use,
			'timeout' => $this->timeout,
		);
		$options = am($defaults, $options);

		# cached?
		if ($options['cache']) {
			$cacheName = md5($url);
			$cacheConfig = $options['cache'] === true ? null: $options['cache'];
			$cacheConfig = !Cache::isInitialized($cacheConfig) ? null : $cacheConfig;

			if ($options['clearCache']) {
				Cache::delete('http_'.$cacheName, $cacheConfig);
			} elseif (($res = Cache::read('http_'.$cacheName, $cacheConfig)) !== false && $res !== null) {
				$this->cacheUsed = true;
				return $res;
			}
		}
		$res = $this->_fetch($url, $options);
		if ($options['cache']) {
			Cache::write('http_'.$cacheName, $res, $cacheConfig);
		}
		return $res;
	}

	public function _fetch($url, $options) {
		if ($options['use']['curl'] && function_exists('curl_init')) {
			$this->debug = 'curl';
			$Ch = new CurlLib();
			$Ch->setUserAgent($options['agent']);
			$data = $Ch->get($url);
			$response = $data[0];
			$status = $data[1]['http_code'];
			if ($status != '200') {
				$this->setError('Error '.$status);
				return false;
			}
			return $response;

		} elseif ($options['use']['cake']) {
			$this->debug = 'cake';

			$HttpSocket = new HttpSocket(array('timeout' => $options['timeout']));
			$response = $HttpSocket->get($url);
			if ($response->code != 200) { //TODO: status 200?
				return false;
			}
			return $response;

		} elseif ($options['use']['php']) {
			$this->debug = 'php';

			$response = file_get_contents($url, 'r');
			//TODO: status 200?
			if (empty($response)) {
				return false;
			}
			return $response;

		} else {
			throw new CakeException('no protocol given');
		}
		return null;
	}


}