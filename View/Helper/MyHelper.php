<?php
App::uses('Helper', 'View');
App::uses('Router', 'Routing');
App::uses('UrlCacheManager', 'Tools.Routing');

/**
 * Helper enhancements for CakePHP
 *
 * @author Mark Scherer
 * @license MIT
 */
class MyHelper extends Helper {

	/**
	 * Manually load helpers.
	 *
	 * Also makes sure callbacks are triggered.
	 *
	 * @param array $helpers (either strings, or [string => array(config...)])
	 * @param boolean $callbacks - trigger missed callbacks
	 * @return void
	 */
	public function loadHelpers($helpers = array(), $callbacks = false) {
		foreach ((array)$helpers as $helper => $config) {
			if (is_int($helper)) {
				$helper = $config;
				$config = array();
			}
			list($plugin, $helperName) = pluginSplit($helper, true);
			if (isset($this->{$helperName})) {
				continue;
			}
			App::uses($helperName . 'Helper', $plugin . 'View/Helper');
			$helperFullName = $helperName . 'Helper';
			$this->{$helperName} = new $helperFullName($this->_View, (array)$config);

			if ($callbacks) {
				if (method_exists($helper, 'beforeRender')) {
					$this->{$helperName}->beforeRender();
				}
			}
		}
	}

	/**
	 * This function is responsible for setting up the Url cache before the application starts generating urls in views
	 *
	 * @return void
	 */
	public function beforeRender($viewFile) {
		if (!Configure::read('UrlCache.active') || Configure::read('UrlCache.runtime.beforeRender')) {
			return;
		}

		// todo: maybe lazy load with HtmlHelper::url()?
		UrlCacheManager::init($this->_View);
		Configure::write('UrlCache.runtime.beforeRender', true);
	}

	/**
	 * This method will store the current generated urls into a persistent cache for next use
	 *
	 * @return void
	 */
	public function afterLayout($layoutFile) {
		if (!Configure::read('UrlCache.active') || Configure::read('UrlCache.runtime.afterLayout')) {
			return;
		}

		UrlCacheManager::finalize();
		Configure::write('UrlCache.runtime.afterLayout', true);
	}

	/**
	 * Intercepts the parent url function to first look if the cache was already generated for the same params
	 *
	 * @param mixed $url url to generate using cakephp array syntax
	 * @param boolean|array $full whether to generate a full url or not (http scheme). As array: full, escape.
	 * @return string
	 * @see Helper::url()
	 */
	public function url($url = null, $full = false) {
		if (is_array($full)) {
			$escape = isset($full['ecape']) ? $full['escape'] : true;
			$full = isset($full['full']) ? $full['full'] : false;
		} else {
			$escape = true;
		}

		if (Configure::read('UrlCache.active')) {
			if ($cachedUrl = UrlCacheManager::get($url, $full)) {
				return $cachedUrl;
			}
		}
		if (!$escape) {
			$routerUrl = Router::url($url, $full);
		} else {
			$routerUrl = parent::url($url, $full);
		}
		if (Configure::read('UrlCache.active')) {
			UrlCacheManager::set($routerUrl);
		}
		return $routerUrl;
	}

	/**
	 * Generate url for given asset file. Depending on options passed provides full url with domain name.
	 * Also calls Helper::assetTimestamp() to add timestamp to local files.
	 * Uses Configure::read('App.assetBaseUrl') for CDN setup.
	 *
	 * @param string|array Path string or url array
	 * @param array $options Options array. Possible keys:
	 *   `fullBase` Return full url with domain name
	 *   `pathPrefix` Path prefix for relative URLs
	 *   `ext` Asset extension to append
	 *   `plugin` False value will prevent parsing path as a plugin
	 * @return string Generated url
	 */
	public function assetUrl($path, $options = array()) {
		if (!Configure::read('App.assetBaseUrl')) {
			return parent::assetUrl($path, $options);
		}
		if (is_array($path)) {
			return $this->url($path, !empty($options['fullBase']));
		}
		if (strpos($path, '://') !== false) {
			return $path;
		}
		if (!array_key_exists('plugin', $options) || $options['plugin'] !== false) {
			list($plugin, $path) = $this->_View->pluginSplit($path, false);
		}
		if (!empty($options['pathPrefix']) && $path[0] !== '/') {
			$path = $options['pathPrefix'] . $path;
		}
		if (
			!empty($options['ext']) &&
			strpos($path, '?') === false &&
			substr($path, -strlen($options['ext']) + 1) !== '.' . $options['ext']
		) {
			$path .= '.' . $options['ext'];
		}
		if (isset($plugin)) {
			$path = Inflector::underscore($plugin) . '/' . $path;
		}

		$path = $this->_encodeUrl($this->assetTimestamp($this->webroot($path)));
		$path = rtrim(Configure::read('App.assetBaseUrl'), '/') . '/' . ltrim($path, '/');

		return $path;
	}

}
