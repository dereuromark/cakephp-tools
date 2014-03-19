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

}
