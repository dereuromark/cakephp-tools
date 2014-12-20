<?php
namespace Tools\View\Helper;

use Cake\View\Helper;

/**
 * Cookie Helper.
 */
class CookieHelper extends Helper {

/**
 * Used to read a cookie values set in a controller for a key or return values for all keys.
 *
 * In your view: `$this->Cookie->read('Controller.sessKey');`
 * Calling the method without a param will return all cookie vars
 *
 * @param string $name the name of the cookie key you want to read
 * @return mixed values from the cookie vars
 */
	public function read($name = null) {
		return $this->request->cookie($name);
	}

/**
 * Used to check is a session key has been set
 *
 * In your view: `$this->Session->check('Controller.sessKey');`
 *
 * @param string $name Session key to check.
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
