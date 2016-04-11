<?php

namespace Tools\View\Helper;

use Cake\View\Helper;

/**
 * Cookie Helper.
 */
class CookieHelper extends Helper {

	/**
	 * Reads a cookie value for a key or return values for all keys.
	 *
	 * In your view: `$this->Cookie->read('key');`
	 *
	 * @param string|null $name the name of the cookie key you want to read
	 * @return mixed values from the cookie vars
	 */
	public function read($name = null) {
		return $this->request->cookie($name);
	}

	/**
	 * Checks if a cookie key has been set.
	 *
	 * In your view: `$this->Cookie->check('key');`
	 *
	 * @param string $name Cookie name to check.
	 * @return bool
	 */
	public function check($name) {
		return $this->request->cookie($name) !== null;
	}

	/**
	 * Event listeners.
	 *
	 * @return array
	 */
	public function implementedEvents() {
		return [];
	}

}
