<?php
App::uses('ShimController', 'Shim.Controller');

/**
 * DRY Controller stuff
 */
class MyController extends ShimController {

	/**
	 * Fix for asset compress to not run into fatal error loops
	 */
	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);
		if ($this->request !== null && (strpos($this->request->here, '/js/cjs/') === 0 || strpos($this->request->here, '/css/ccss/') === 0)) {
			unset($this->request->params['ext']);
		}
	}

}
