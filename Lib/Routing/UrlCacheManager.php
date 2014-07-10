<?php

/**
 * This class will statically hold in memory url's indexed by a custom hash
 *
 * @licence MIT
 * @modified Mark Scherer
 * - now easier to integrate
 * - optimization for `pageFiles` (still stores urls with only controller/action keys in global file)
 * - can handle legacy `prefix` urls
 *
 */
class UrlCacheManager {

	/**
	 * Holds all generated urls so far by the application indexed by a custom hash
	 *
	 */
	public static $cache = array();

	/**
	 * Holds all generated urls so far by the application indexed by a custom hash
	 *
	 */
	public static $cachePage = array();

	/**
	 * Holds all generated urls so far by the application indexed by a custom hash
	 *
	 */
	public static $extras = array();

	/**
	 * Type for the current set (triggered by last get)
	 */
	public static $type = 'cache';

	/**
	 * Key for current get/set
	 */
	public static $key = null;

	/**
	 * Cache key for pageFiles
	 */
	public static $cacheKey = 'url_map';

	/**
	 * Cache key for pageFiles
	 */
	public static $cachePageKey = null;

	/**
	 * Params that will always be present and will determine the global cache if pageFiles is used
	 */
	public static $paramFields = array('controller', 'plugin', 'action', 'prefix');

	/**
	 * Should be called in beforeRender()
	 *
	 */
	public static function init(View $View) {
		$params = $View->request->params;
		if (Configure::read('UrlCache.pageFiles')) {
			$cachePageKey = '_misc';
			if (is_object($View)) {
				$path = $View->request->here;
				if ($path === '/') {
					$path = 'uc_homepage';
				} else {
					$path = strtolower(Inflector::slug($path));
				}
				if (empty($path)) {
					$path = 'uc_error';
				}
				$cachePageKey = '_' . $path;
			}
			static::$cachePageKey = static::$cacheKey . $cachePageKey;
			static::$cachePage = Cache::read(static::$cachePageKey, '_cake_core_');
		}
		static::$cache = Cache::read(static::$cacheKey, '_cake_core_');

		// still old "prefix true/false" syntax?
		if (Configure::read('UrlCache.verbosePrefixes')) {
			unset(static::$paramFields[3]);
			static::$paramFields = array_merge(static::$paramFields, (array)Configure::read('Routing.prefixes'));
		}
		static::$extras = array_intersect_key($params, array_combine(static::$paramFields, static::$paramFields));
		$defaults = array();
		foreach (static::$paramFields as $field) {
			$defaults[$field] = '';
		}
		static::$extras = array_merge($defaults, static::$extras);
	}

	/**
	 * Should be called in afterLayout()
	 *
	 */
	public static function finalize() {
		Cache::write(static::$cacheKey, static::$cache, '_cake_core_');
		if (Configure::read('UrlCache.pageFiles') && !empty(static::$cachePage)) {
			Cache::write(static::$cachePageKey, static::$cachePage, '_cake_core_');
		}
	}

	/**
	 * Returns the stored url if it was already generated, false otherwise
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get($url, $full) {
		$keyUrl = $url;
		if (is_array($keyUrl)) {
			$keyUrl += static::$extras;
			// prevent different hashs on different orders
			ksort($keyUrl, SORT_STRING);
			// prevent different hashs on different types (int/string/bool)
			foreach ($keyUrl as $key => $val) {
				$keyUrl[$key] = (string) $val;
			}
		}
		static::$key = md5(serialize($keyUrl) . $full);

		if (Configure::read('UrlCache.pageFiles')) {
			static::$type = 'cachePage';
			if (is_array($keyUrl)) {
				$res = array_diff_key($keyUrl, static::$extras);
				if (empty($res)) {
					static::$type = 'cache';
				}
			}
			if (static::$type === 'cachePage') {
				return isset(static::$cachePage[static::$key]) ? static::$cachePage[static::$key] : false;
			}
		}
		return isset(static::$cache[static::$key]) ? static::$cache[static::$key] : false;
	}

	/**
	 * Stores a ney key in memory cache
	 *
	 * @param string $key
	 * @param mixed data to be stored
	 * @return void
	 */
	public static function set($data) {
		if (Configure::read('UrlCache.pageFiles') && static::$type === 'cachePage') {
			static::$cachePage[static::$key] = $data;
		} else {
			static::$cache[static::$key] = $data;
		}
	}

}
