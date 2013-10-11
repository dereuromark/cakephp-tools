<?php

# idea: serve (tools) package files on the fly...
class MyAsset {

	protected $pluginPaths = array();

	protected $url;

	protected $parseData;

	/**
	 * MyAsset::js()
	 *
	 * @param boolean $setHeaders
	 * @return string Script tags.
	 */
	public function js($setHeaders = true) {
		$this->_init();

		$res = $this->_parse($_SERVER['QUERY_STRING'], 'js');

		if ($setHeaders) {
			$this->_headers('js');
		}
		return $this->_get($res, 'js');
	}

	/**
	 * MyAsset::css()
	 *
	 * @param boolean $setHeaders
	 * @return string Style tags.
	 */
	public function css($setHeaders = true) {
		$this->_init();

		$res = $this->_parse($_SERVER['QUERY_STRING'], 'css');

		if ($setHeaders) {
			$this->_headers('css');
		}
		return $this->_get($res, 'css');
	}

	/**
	 * MyAsset::_headers()
	 *
	 * @param string $type
	 * @return void
	 */
	public function _headers($type) {
		if ($type === 'js') {
			$type = 'text/javascript'; //'application/x-javascript';
		} elseif ($type === 'css') {
			$type = 'text/css';
		}
		header("Date: " . date("D, j M Y G:i:s ", time()) . 'GMT');
		header("Content-Type: " . $type . '; charset=utf-8');
		header("Expires: " . gmdate("D, j M Y H:i:s", time() + DAY) . " GMT");
		header("Cache-Control: max-age=" . DAY . ", must-revalidate"); // HTTP/1.1
		header("Pragma: cache"); // HTTP/1.0
	}

	/**
	 * searches, combines, packs, caches and returns scripts
	 */
	public function _get($assets, $type = null) {
		$script = array();
		foreach ($assets as $plugin => $packages) {
			foreach ($packages as $package => $packageFiles) {
				$path = $this->_path($plugin, $package, $type);
				foreach ($packageFiles as $file) {
					$script[] = $this->_read($path . $file);
				}
			}
		}
		$script = implode(PHP_EOL, $script);

		return $script;
	}

	public function _init() {
		if (empty($_SERVER['QUERY_STRING'])) {
			header('HTTP/1.1 404 Not Found');
			exit('File Not Found');
		}
		Configure::write('debug', 0);
	}

	/**
	 * Get the content of a single file
	 */
	public function _read($path) {
		$path = str_replace('/', DS, $path);
		if (strpos($path, '..') !== false) {
			trigger_error('MyAsset: Invalid file (' . $path . ') [' . $this->url . ']');
			return '';
		}
		if (!file_exists($path)) {
			trigger_error('MyAsset: File not exists (' . $path . ') [' . $this->url . ']');
			return '';
		}
		$data = file_get_contents($path);
		//TODO: compress?
		return $data;
	}

	/**
	 * @deprecated?
	 */
	public function _makeCleanCss($path, $name, $pack = false) {

		$data = file_get_contents($path);
		if (!$pack) {
			$output = " /* file: $name, uncompressed */ " . $data;
			return $output;
		}
		if (!isset($this->Css)) {
			App::import('Vendor', 'csspp' . DS . 'csspp');
			$this->Css = new csspp();
		}
		$output = $this->Css->compress($data);
		$ratio = 100 - (round(strlen($output) / strlen($data), 3) * 100);
		$output = " /* file: $name, ratio: $ratio% */ " . $output;
		return $output;
	}

	/**
	 * @return string result or bool FALSE on failure
	 */
	public function _readCssCache($path) {
		if (file_exists($path)) {
			return file_get_contents($path);
		}
		return false;
	}

	/**
	 * @return boolean result
	 * @deprecated?
	 */
	public function _writeCssCache($path, $content) {
		if (!is_dir(dirname($path))) {
			if (!mkdir(dirname($path), 0755, true)) {
				trigger_error('MyAsset: Cannot create cache folder');
				return false;
			}
		}
		$cache = new File($path);
		return $cache->write($content);
	}

	/**
	 * Get correct path of asset file.
	 *
	 * - PluginName.Asset => webroot/asset/ dir in plugin (new)
	 * - App.Webroot => webroot/ dir
	 * - App => packages
	 * - Root => packages
	 * - PluginName => packages in plugin
	 *
	 * @return string Path or bool false on failure.
	 */
	public function _path($plugin, $package, $type = null) {
		if ($type !== 'js' && $type !== 'css') {
			return false;
		}
		$pluginPathKey = $plugin;
		if ($package === 'Asset') {
			$pluginPathKey .= $package;
		}
		if (isset($this->pluginPaths[$pluginPathKey])) {
			return $this->pluginPaths[$pluginPathKey];
		}
		if ($plugin === 'App' && $package === 'Webroot') {
			$pluginPath = WWW_ROOT . $type . DS;
		} elseif ($plugin === 'App') {
			$pluginPath = APP . 'packages' . DS;
		} elseif ($plugin === 'Root') {
			$pluginPath = ROOT . DS . 'packages' . DS;
		} elseif ($package === 'Asset') {
			$pluginPath = App::pluginPath($plugin) . 'webroot' . DS . 'asset' . DS;
			$plugin = $plugin . 'Asset';
		} else {
			$pluginPath = App::pluginPath($plugin) . 'packages' . DS;
		}
		if (!$pluginPath) {
			return false;
		}
		if ($package === 'Webroot' || $package === 'Asset') {
			$packagePath = '';
		} else {
			$packagePath = strtolower($package) . DS . 'files' . DS;
		}

		$this->pluginPaths[$pluginPathKey] = $pluginPath;
		return $this->pluginPaths[$pluginPathKey] . $packagePath;
	}

	/**
	 * Url (example): file=x & file=Tools|y & file=Tools.Jquery|jquery/sub/z
	 * => x is in webroot/
	 * => y is in plugins/tools/webroot/
	 * => z is in plugins/tools/packages/jquery/files/jquery/sub/
	 */
	public function _parse($string, $type = null) {
		$parts = explode('&', urldecode($string));
		$res = array();
		foreach ($parts as $part) {
			if (preg_match('|\.\.|', $part)) {
				trigger_error('MyAsset: Invalid piece (' . $part . ')');
				continue;
			}
			$plugin = 'App';
			$package = 'Webroot';
			list($key, $content) = explode('=', $part, 2);
			if ($key !== 'file') {
				continue;
			}
			if (strpos($content, '|') !== false) {
				list($plugin, $content) = explode('|', $content, 2);
				if (strpos($plugin, '.') !== false) {
					list($plugin, $package) = explode('.', $plugin, 2);
				}
			}
			if ($type === 'js') {
				if (substr($content, -3) !== '.js') {
					$content .= '.js';
				}
			} elseif ($type === 'css') {
				if (substr($content, -4) !== '.css') {
					$content .= '.css';
				}
			}
			$res[$plugin][$package][] = $content;
		}
		$this->url = $string;
		$this->parseData = $res;
		return $res;
	}

}

/*

	if (preg_match('|\.\.|', $url) || !preg_match('|^ccss/(.+)$|i', $url, $regs)) {
		die('Wrong file name.');
	}

	$filename = 'css/' . $regs[1];
	$filepath = CSS . $regs[1];
	$cachepath = CACHE . 'css' . DS . str_replace(array('/','\\'), '-', $regs[1]);

	if (!file_exists($filepath)) {
		die('Wrong file name.');
	}

	if (file_exists($cachepath)) {
		$templateModified = filemtime($filepath);
		$cacheModified = filemtime($cachepath);

		if ($templateModified > $cacheModified) {
			$output = make_clean_css($filepath, $filename);
			write_css_cache($cachepath, $output);
		} else {
			$output = file_get_contents($cachepath);
		}
	} else {
		$output = make_clean_css($filepath, $filename);
		write_css_cache($cachepath, $output);
		$templateModified = time();
	}

	header("Date: " . date("D, j M Y G:i:s ", $templateModified) . 'GMT');
	header("Content-Type: text/css");
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + DAY) . " GMT");
	header("Cache-Control: max-age=86400, must-revalidate"); // HTTP/1.1
	header("Pragma: cache"); // HTTP/1.0
	print $output;

*/
