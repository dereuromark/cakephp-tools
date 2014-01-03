<?php

/**
 * Curl wrapper with goodies
 * - can switch the UA to test certain browser sensitive features
 * - can simulate/establish tor connection
 *
 * @license MIT
 * @cakephp 2.x
 */
class CurlLib {

	public $settings = array(
		'CURLOPT_SSL_VERIFYPEER' => false,
	);

	protected $Ch = null;

	public $cookie = null;

	public $tor = '127.0.0.1:9050';

	public $header = array();

	public $persistentHeader = array();

	protected $lastUrl = '';

	public $ua = array(
		'Firefox' => array(
			'Firefox/3.0.2 Linux' => 'Mozilla/5.0 (X11; U; Linux i686; de; rv:1.9.0.2) Gecko/2008091700 SUSE/3.0.2-5.2 Firefox/3.0.2'
		),
		'IE' => array(
			'6' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
			'7' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
			'8' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)'
		),
		'Konqueror' => array(
			'Konqueror/3.5' => 'Mozilla/5.0 (compatible; Konqueror/3.5; Linux) KHTML/3.5.5 (like Gecko).'
		),
		'Opera' => array(
			'9.60' => 'Opera/9.60 (X11; Linux i686; U; de) Presto/2.1.1',
			'10' => 'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.2.15 Version/10.00'
		),
		'Safari' => array(
			'1.0' => 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; de-de) AppleWebKit/85.7 (KHTML, like Gecko) Safari/85.7',
			'1.2' => 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; de-de) AppleWebKit/125.2 (KHTML, like Gecko) Safari/125.8',
			'3.3' => 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X; de-de) AppleWebKit/522.15.5 (KHTML, like Gecko) Version/3.0.3 Safari/522.15.5'
		),
		'Chrome' => array(
			'8' => 'Mozilla/5.0 (X11; U; Linux x86_64; en-US) AppleWebKit/540.0 (KHTML, like Gecko) Ubuntu/10.10 Chrome/8.1.0.0 Safari/540.0'
		),
		'Bots' => array(
			'Google' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'
		)
	);

	public function set($key, $value) {
		return curl_setopt($this->Ch, $key, $value);
	}

	public function __construct($timeout = 5, $cookie = true) {
		$this->cookie = null;
		if ($cookie !== false) {
			if ($cookie === true) {
				$this->cookie['file'] = tempnam(sys_get_temp_dir(), 'curl_cookie');
				$this->cookie['remove'] = true;
			} else {
				$this->cookie['remove'] = false;
				$this->cookie['file'] = $cookie;
			}
		}
		$this->Ch = curl_init();
		if ($this->cookie !== false) {
			$this->set(CURLOPT_COOKIEJAR, $this->cookie['file']);
			$this->set(CURLOPT_COOKIEFILE, $this->cookie['file']);
		}
		$this->set(CURLOPT_FOLLOWLOCATION, true);
		$this->set(CURLOPT_ENCODING, "");
		$this->set(CURLOPT_RETURNTRANSFER, true);
		$this->set(CURLOPT_AUTOREFERER, true);
		$this->set(CURLOPT_CONNECTTIMEOUT, $timeout);
		$this->set(CURLOPT_TIMEOUT, $timeout);
		$this->set(CURLOPT_MAXREDIRS, 10);
		$this->setUserAgent();
	}

	public function setUserAgent($ua = 'Firefox', $version = null) {
		if (isset($this->userAgents[$ua])) {
			if ($version !== null && isset($this->userAgents[$ua][$version])) {
				$ua = $this->userAgents[$ua][$version];
			} else {
				$ua = array_values($this->userAgents[$ua]);
				krsort($ua);
				list($ua) = $ua;
			}
		}
		return $this->set(CURLOPT_USERAGENT, $ua);
	}

	//TODO: use Dummy.FakerLib instead

	public function randomizeUserAgent() {
		//list of browsers
		$agentBrowser = array(
			'Firefox',
			'Safari',
			'Opera',
			'Flock',
			'Internet Explorer',
			'Seamonkey',
			'Konqueror',
			'GoogleBot'
		);
		//list of operating systems
		$agentOS = array(
			'Windows 3.1',
			'Windows 95',
			'Windows 98',
			'Windows 2000',
			'Windows NT',
			'Windows XP',
			'Windows Vista',
			'Redhat Linux',
			'Ubuntu',
			'Fedora',
			'AmigaOS',
			'OS 10.5'
		);
		//randomly generate UserAgent
		$ua = $agentBrowser[rand(0, count($agentBrowser) - 1)] . '/' . rand(1, 8) . '.' . rand(0, 9) . ' (' . $agentOS[rand(0, count($agentOS) - 1)] . ' ' . rand(1, 7) . '.' . rand(0, 9) . '; en-US;)';
		$this->setUserAgent($ua);
		return $ua;
	}

	public function setSocks5Proxy($proxy = false) {
		$this->set(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		if ($proxy) {
			return $this->set(CURLOPT_PROXY, $proxy);
		} else {
			return $this->set(CURLOPT_PROXY, false);
		}
	}

	public function setHttpProxy($proxy = false) {
		$this->set(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		if ($proxy) {
			return $this->set(CURLOPT_PROXY, $proxy);
		} else {
			return $this->set(CURLOPT_PROXY, false);
		}
	}

	public function setTor($tor = null) {
		if ($tor === null) {
			$tor = $this->tor;
		}
		return $this->setSocks5Proxy($tor);
	}

	public function setHeader($key, $header, $persistent = false) {
		if ($persistent) {
			$this->persistentHeader[$key] = $header;
		} else {
			$this->header[$key] = $header;
		}
	}

	public function unsetHeader($key, $persistent = false) {
		if ($persistent) {
			unset($this->persistentHeader[$key]);
		} else {
			unset($this->header[$key]);
		}
	}

	public function exec() {
		$header = array();
		foreach ($this->header as $tk => $tv) {
			$header[] = $tk . ': ' . $tv;
		}
		$this->set(CURLOPT_HTTPHEADER, $header);
		$this->header = $this->persistentHeader;
		$content = curl_exec($this->Ch);
		$info = curl_getinfo($this->Ch);
		return array($content, $info);
	}

	/**
	 * Get/set referer
	 *
	 * @param url
	 * @return mixed
	 */
	public function referer($url = null) {
		if ($url === null) {
			if ($this->lastUrl !== null) {
				return $this->set(CURLOPT_REFERER, $this->lastUrl);
			}
		} else {
			$this->lastUrl = null;
			return $this->set(CURLOPT_REFERER, $url);
		}
		return false;
	}

	protected function _prepareData($url, $getdata = array(), $data = array()) {
		if (strpos($url, '?') === false && ( //If Url has not a "?" in it
				(is_array($getdata) && !empty($getdata)) || //And $getdata is array and has more than one value
				(!is_array($getdata) && strlen($getdata) > 0))) { //or its a a string and is longer than 0
			$url .= '?';
		}
		$data = array(
			$getdata,
			$data
		);
		foreach ($data as $i => $part) {
			if (is_array($part)) {
				$string = '';
				foreach ($part as $key => $value) {
					$string .= urlencode($key) . '=' . urlencode($value) . '&';
				}
				$part = rtrim($string, '&');
			} else {
				$part = urlencode($part);
			}
			$data[$i] = $part;
		}
		$data[0] = $url . $data[0];
		return $data;
	}

	public function post($url, $data = array(), $getdata = array()) {
		$this->referer();
		$this->set(CURLOPT_POST, true);

		$data = $this->_prepareData($url, $getdata, $data);

		$this->set(CURLOPT_URL, $data[0]);
		$this->set(CURLOPT_POSTFIELDS, $data[1]);
		return $this->exec();
	}

	public function get($url, $data = array()) {
		$this->referer();
		$this->set(CURLOPT_HTTPGET, true);

		$data = $this->_prepareData($url, $data);

		$this->set(CURLOPT_URL, $data[0]);
		$this->set(CURLOPT_SSL_VERIFYPEER, false);
		$this->lastUrl = $url;
		return $this->exec();
	}

	public function __destruct() {
		if ($this->cookie !== false) {
			if (isset($this->cookie['handle'])) {
				fclose($this->cookie['handle']);
			}
			if ($this->cookie['remove']) {
				unlink($this->cookie['file']);
			}
		}
		curl_close($this->Ch);
	}

}
