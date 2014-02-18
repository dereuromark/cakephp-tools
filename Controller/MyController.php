<?php
App::uses('Controller', 'Controller');

/**
 * DRY Controller stuff
 */
class MyController extends Controller {

	/**
	 * @var array
	 * @link https://github.com/cakephp/cakephp/pull/857
	 */
	public $paginate = array();

	/**
	 * Fix for asset compress to not run into fatal error loops
	 */
	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);
		if ($this->request !== null && (strpos($this->request->here, '/js/cjs/') === 0 || strpos($this->request->here, '/css/ccss/') === 0)) {
			unset($this->request->params['ext']);
		}
	}

	/**
	 * Add headers for IE8 etc to fix caching issues in those stupid browsers
	 *
	 * @overwrite to fix IE cacheing issues
	 * @return void
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
	 * @overwrite to fix encoding issues on Apache with mod_rewrite
	 * @param string|array $url A string or array-based URL
	 * @param integer $status Optional HTTP status code (eg: 404)
	 * @param boolean $exit If true, exit() will be called after the redirect
	 * @return void
	 */
	public function redirect($url, $status = null, $exit = true) {
		$run = Configure::read('App.additionalEncoding');
		if ($run && is_array($url)) {
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
	 * Handles automatic pagination of model records.
	 *
	 * @overwrite to support defaults like limit, querystring settings
	 * @param Model|string $object Model to paginate (e.g: model instance, or 'Model', or 'Model.InnerModel')
	 * @param string|array $scope Conditions to use while paginating
	 * @param array $whitelist List of allowed options for paging
	 * @return array Model query results
	 */
	public function paginate($object = null, $scope = array(), $whitelist = array()) {
		if ($defaultSettings = (array)Configure::read('Paginator')) {
			$this->paginate += $defaultSettings;
		}
		return parent::paginate($object, $scope, $whitelist);
	}

	/**
	 * Additionally encode string to match the htaccess files processing it.
	 *
	 * @param mixed Url piece
	 * @param integer $run How many times does the value have to be escaped
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
	 *
	 * @deprecated?
	 */
	public function beforeRender() {
		if (class_exists('Packages')) {
			Packages::initialize($this, __CLASS__);
		}
		parent::beforeRender();
	}

}
