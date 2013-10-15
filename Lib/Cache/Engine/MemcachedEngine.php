<?php
/**
 * Memcached storage engine for cache
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Memcached storage engine for cache.  Memcached has some limitations in the amount of
 * control you have over expire times far in the future. See MemcachedEngine::write() for
 * more information.
 *
 * Cache::clear() is not implemented due to lack of support from memcached api.
 * If clear() support is primordial to you, use the default memcache engine.
 *
 * Main advantage of this Memcached engine over the Memcache engine is
 * support of binary protocol, and igbibnary serialization
 * (if memcached extension compiled with --enable-igbinary).
 * Compressed keys can also be incremented/decremented.
 *
 */
class MemcachedEngine extends CacheEngine {

	/**
	 * Memcached wrapper.
	 *
	 * @var Memcache
	 */
	protected $_Memcached = null;

	/**
	 * @var string Keyname of the cache entry holding all the others key name
	 */
	protected $_keys = '_keys';

	/**
	 * @var string Token used to separe each keyname in the $_keys string
	 */
	protected $_keySeparator = '|';

	/**
	 * Settings
	 *
	 *  - servers = string or array of memcache servers, default => 127.0.0.1. If an
	 *    array MemcacheEngine will use them as a pool.
	 *  - compress = boolean, default => false
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Initialize the Cache Engine
	 *
	 * Called automatically by the cache frontend
	 * To reinitialize the settings call Cache::engine('EngineName', [optional] settings = array());
	 *
	 * @param array $settings array of setting for the engine
	 * @return boolean True if the engine has been successfully initialized, false if not
	 */
	public function init($settings = array()) {
		if (!class_exists('Memcached')) {
			return false;
		}
		if (!isset($settings['prefix'])) {
			$settings['prefix'] = Inflector::slug(APP_DIR) . '_';
		}
		$settings += array(
			'engine' => 'Memcached',
			'servers' => array('127.0.0.1'),
			'compress' => false,
			'persistent' => true
		);
		parent::init($settings);

		$this->_keys .= $this->settings['prefix'];

		if (!is_array($this->settings['servers'])) {
			$this->settings['servers'] = array($this->settings['servers']);
		}
		if (!isset($this->_Memcached)) {
			$return = false;
			$this->_Memcached = new Memcached($this->settings['persistent'] ? 'mc' : null);
			$this->_setOptions();

			if (!count($this->_Memcached->getServerList())) {
				$servers = array();
				foreach ($this->settings['servers'] as $server) {
					$servers[] = $this->_parseServerString($server);
				}

				if ($this->_Memcached->addServers($servers)) {
					$return = true;
				}

			}

			if (!$this->_Memcached->get($this->_keys)) $this->_Memcached->set($this->_keys, '');
			return $return;
		}

		return true;
	}

	/**
	 * Settings the memcached instance
	 *
	 */
	protected function _setOptions() {
		$this->_Memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
		$this->_Memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

		if (Memcached::HAVE_IGBINARY) {
			$this->_Memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
		}

		$this->_Memcached->setOption(Memcached::OPT_COMPRESSION, (bool)$this->settings['compress']);
	}

	/**
	 * Parses the server address into the host/port. Handles both IPv6 and IPv4
	 * addresses and Unix sockets
	 *
	 * @param string $server The server address string.
	 * @return array Array containing host, port
	 */
	protected function _parseServerString($server) {
		if ($server[0] === 'u') {
			return array($server, 0);
		}
		if (substr($server, 0, 1) === '[') {
			$position = strpos($server, ']:');
			if ($position !== false) {
				$position++;
			}
		} else {
			$position = strpos($server, ':');
		}
		$port = 11211;
		$host = $server;
		if ($position !== false) {
			$host = substr($server, 0, $position);
			$port = substr($server, $position + 1);
		}
		return array($host, (int)$port);
	}

	/**
	 * Write data for key into cache. When using memcache as your cache engine
	 * remember that the Memcache pecl extension does not support cache expiry times greater
	 * than 30 days in the future. Any duration greater than 30 days will be treated as never expiring.
	 *
	 * @param string $key Identifier for the data
	 * @param mixed $value Data to be cached
	 * @param integer $duration How long to cache the data, in seconds
	 * @return boolean True if the data was successfully cached, false on failure
	 * @see http://php.net/manual/en/memcache.set.php
	 */
	public function write($key, $value, $duration) {
		if ($duration > 30 * DAY) {
			$duration = 0;
		}

		$this->_Memcached->append($this->_keys, str_replace($this->settings['prefix'], '', $this->_keySeparator . $key));
		return $this->_Memcached->set($key, $value, $duration);
	}

	/**
	 * Read a key from the cache
	 *
	 * @param string $key Identifier for the data
	 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
	 */
	public function read($key) {
		return $this->_Memcached->get($key);
	}

	/**
	 * Increments the value of an integer cached key
	 *
	 * @param string $key Identifier for the data
	 * @param integer $offset How much to increment
	 * @return New incremented value, false otherwise
	 * @throws CacheException when you try to increment with compress = true
	 */
	public function increment($key, $offset = 1) {
		return $this->_Memcached->increment($key, $offset);
	}

	/**
	 * Decrements the value of an integer cached key
	 *
	 * @param string $key Identifier for the data
	 * @param integer $offset How much to subtract
	 * @return New decremented value, false otherwise
	 * @throws CacheException when you try to decrement with compress = true
	 */
	public function decrement($key, $offset = 1) {
		return $this->_Memcached->decrement($key, $offset);
	}

	/**
	 * Delete a key from the cache
	 *
	 * @param string $key Identifier for the data
	 * @return boolean True if the value was successfully deleted, false if it didn't exist or couldn't be removed
	 */
	public function delete($key) {
		return $this->_Memcached->delete($key);
	}

	/**
	 * Delete all keys from the cache
	 *
	 * @param boolean $check
	 * @return boolean True if the cache was successfully cleared, false otherwise
	 */
	public function clear($check) {
		$keys = array_slice(explode($this->_keySeparator, $this->_Memcached->get($this->_keys)), 1);

		foreach ($keys as $key)
			$this->_Memcached->delete($this->settings['prefix'] . $key);

		$this->_Memcached->delete($this->_keys);

		return true;
	}
}
