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
