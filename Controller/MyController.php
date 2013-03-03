<?php
App::uses('Controller', 'Controller');

/**
 * DRY Controller stuff
 * 2011-02-01 ms
 */
class MyController extends Controller {

	/**
	 * Fix for asset compress to not run into fatal error loops
	 * 2012-12-25 ms
	 */
	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);
		if (strpos($this->here, '/js/cjs/') === 0 || strpos($this->here, '/css/ccss/') === 0) {
			unset($this->request->params['ext']);
		}
	}

	/**
	 * Add headers for IE8 etc to fix caching issues in those stupid browsers
	 * 2012-12-25 ms
	 */
	public function disableCache() {
		$this->response->header(array(
			'Pragma' => 'no-cache',
		));
		return parent::disableCache();
	}

	/**
	 * Fix encoding issues on Apache with mod_rewrite
	 * Uses Configure::read('App.additionalEncoding') to additionally escape
	 *
	 * Tip: Set it to `1` for normal mod_rewrite websites routing directly into webroot
	 * If you use another setup (like localhost/app/webroot) where you use multiple htaccess files or rewrite
	 * rules you need to raise it accordingly.
	 *
	 * @param string|array $url A string or array-based URL
	 * @param integer $status Optional HTTP status code (eg: 404)
	 * @param boolean $exit If true, exit() will be called after the redirect
	 * @return void
	 * 2013-03-02 ms
	 */
	public function redirect($url, $status = null, $exit = true) {
		$run = Configure::read('App.additionalEncoding');
		if ($run) {
			foreach ($url as $key => $value) {
				if ($key === '?') {
					continue;
				}
				$value = $this->_encodeUrlPiece($value, $run);

				$url[$key] = $value;
			}
		}
		return parent::redirect($url, $status, $exit);
	}

	/**
	 * @param mixed Url piece
	 * @param int $run How many times does the value have to be escaped
	 * @return mixed Escaped piece
	 */
	protected function _encodeUrlPiece($value, $run) {
		if (!is_array($value)) {
			for ($i = 0; $i < $run; $i++) {
				$value = urlencode($value);
			}
			return $value;
		}
		return $this->_encodeUrlPiece($value, $run);
	}

	/**
	 * Init Packages class if enabled/included
	 * @deprecated?
	 * 2012-12-25 ms
	 */
	public function beforeRender() {
		if (class_exists('Packages')) {
			Packages::initialize($this, __CLASS__);
		}
		parent::beforeRender();
	}

}
