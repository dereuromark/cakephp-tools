<?php
App::uses('HttpSocket', 'Network/Http');
App::uses('CurlLib', 'Tools.Lib');

/**
 * Wrapper for curl, php or file_get_contents with some fixes or improvements:
 * - All 2xx OK status codes will return the proper result
 * - Response will be properly utf8 encoded on WINDOWS, as well
 * - Timeout is reduced to 5 by default and can easily be adjusted
 * - file_get_contents wrapper is fixed for HTTP1.1 to default to "Connection: close"
 *   to avoid leaving the connection open
 * - Caching possibilities included
 * - Auto-Fallback if curl is not available
 *
 * TODO: throw exceptions instead of error stuff here
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.x
 */
class HttpSocketLib {

	// First tries with curl, then cake, then php
	public $use = array('curl' => true, 'cake' => true, 'php' => true);

	public $debug = null;

	public $timeout = 5;

	public $cacheUsed = null;

	public $error = array();

	public $allowRedirects = array(301);

	public function __construct($use = array()) {
		if (is_array($use)) {
			foreach ($use as $key => $value) {
				if (array_key_exists($key, $this->use)) {
					$this->use[$key] = $value;
				}
			}
		} elseif (array_key_exists($use, $this->use)) {
			$this->use[$use] = true;
			if ($use === 'cake') {
				$this->use['curl'] = false;
			} elseif ($use === 'php') {
				$this->use['curl'] = $this->use['cake'] = false;
			}
		}
	}

	/**
	 * @param string $error
	 */
	public function setError($error) {
		if (empty($error)) {
			return;
		}
		$this->error[] = $error;
	}

	/**
	 * @return string
	 */
	public function error($asString = true, $separator = ', ') {
		return implode(', ', $this->error);
	}

	public function reset() {
		$this->error = array();
		$this->debug = null;
	}

	/**
	 * Fetches url with curl if available
	 * fallbacks: cake and php
	 * note: expects url with json encoded content
	 *
	 * @param string $url
	 * @param array $options
	 * @return string Response or false on failure
	 */
	public function fetch($url, $options = array()) {
		if (!is_array($options)) {
			$options = array('agent' => $options);
		}
		$defaults = array(
			'agent' => 'cakephp http socket lib',
			'cache' => false,
			'clearCache' => false,
			'use' => $this->use,
			'timeout' => $this->timeout,
		);
		$options = array_merge($defaults, $options);

		// cached?
		if ($options['cache']) {
			$cacheName = md5($url);
			$cacheConfig = $options['cache'] === true ? null : $options['cache'];
			$cacheConfig = !Cache::isInitialized($cacheConfig) ? null : $cacheConfig;

			if ($options['clearCache']) {
				Cache::delete('http_' . $cacheName, $cacheConfig);
			} elseif (($res = Cache::read('http_' . $cacheName, $cacheConfig)) !== false && $res !== null) {
				$this->cacheUsed = true;
				return $res;
			}
		}
		$res = $this->_fetch($url, $options);
		if ($options['cache']) {
			Cache::write('http_' . $cacheName, $res, $cacheConfig);
		}
		return $res;
	}

	/**
	 * @param string $url
	 * @param array $options
	 * @return string Response or false on failure
	 */
	public function _fetch($url, $options) {
		$allowedCodes = array_merge($this->allowRedirects, array(200, 201, 202, 203, 204, 205, 206));

		if ($options['use']['curl'] && function_exists('curl_init')) {
			$this->debug = 'curl';
			$Ch = new CurlLib();
			$Ch->setUserAgent($options['agent']);
			$data = $Ch->get($url);
			$response = $data[0];
			$statusCode = $data[1]['http_code'];
			if (!in_array($statusCode, $allowedCodes)) {
				$this->setError('Error ' . $statusCode);
				return false;
			}
			$response = $this->_assertEncoding($response);
			return $response;
		}
		if ($options['use']['cake']) {
			$this->debug = 'cake';

			$HttpSocket = new HttpSocket(array('timeout' => $options['timeout']));
			$response = $HttpSocket->get($url);
			if (!in_array($response->code, $allowedCodes)) {
				return false;
			}
			$response = $this->_assertEncoding($response);
			return $response;
		}
		if ($options['use']['php']) {
			$this->debug = 'php';

			$opts = array(
				'http' => array(
					'method' => 'GET',
					'header' => array('Connection: close'),
					'timeout' => $options['timeout']
				)
			);
			if (isset($options['http'])) {
				$opts['http'] = array_merge($opts['http'], $options['http']);
			}
			if (is_array($opts['http']['header'])) {
				$opts['http']['header'] = implode(PHP_EOL, $opts['http']['header']);
			}
			$context = stream_context_create($opts);
			$response = file_get_contents($url, false, $context);
			if (!isset($httpResponseHeader)) {
				return false;
			}
			preg_match('/^HTTP.*\s([0-9]{3})/', $httpResponseHeader[0], $matches);
			$statusCode = (int)$matches[1];
			if (!in_array($statusCode, $allowedCodes)) {
				return false;
			}
			$response = $this->_assertEncoding($response);
			return $response;
		}
		throw new CakeException('no protocol given');
	}

	/**
	 * It seems all three methods have encoding issues if not run through this method
	 *
	 * @param string $response
	 * @param string Correctly encoded response
	 */
	protected function _assertEncoding($response) {
		if (!WINDOWS) {
			return $response;
		}
		$x = mb_detect_encoding($response, 'auto', true);
		if ($x !== 'UTF-8') {
			$response = iconv(null, "utf-8", $response);
		}
		return $response;
	}

}
