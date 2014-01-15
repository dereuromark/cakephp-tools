<?php
App::uses('View', 'View');

/**
 * A view to handle AJAX requests.
 *
 * Expects all incoming requests to be of extension "json" and that the expected result
 * will also be in JSON format.
 * A response to an invalid request may be just HTTP status "code" and error "message"
 * (e.g, on 4xx or 5xx).
 * A response to a valid request will always contain at least "content" and "error" keys.
 * You can add more data using _serialize.
 *
 * @author Mark Scherer
 * @license MIT
 */
class AjaxView extends View {

	/**
	 * List of variables to collect from the associated controller.
	 *
	 * @var array
	 */
	protected $_passedVars = array(
			'viewVars', 'autoLayout', 'ext', 'helpers', 'view', 'layout', 'name', 'theme',
			'layoutPath', 'viewPath', 'request', 'plugin', 'passedArgs', 'cacheAction', 'subDir'
	);

	/**
	 * The subdirectory. AJAX views are always in ajax.
	 *
	 * @var string
	 */
	public $subDir = 'ajax';

	/**
	 * Name of layout to use with this View.
	 *
	 * @var string
	 */
	public $layout = false;

	/**
	 * Constructor
	 *
	 * @param Controller $controller
	 */
	public function __construct(Controller $controller = null) {
		parent::__construct($controller);
		// Unfortunately, layout gets overwritten via passed Controller attribute
		if ($this->layout === 'default' || $this->layout === 'ajax') {
			$this->layout = false;
		}
		if ($this->subDir === null) {
			$this->subDir = 'ajax';
		}

		if (isset($controller->response) && $controller->response instanceof CakeResponse) {
			$controller->response->type('json');
		}
	}

	/**
	 * Renders an AJAX view.
	 * The rendered content will be part of the JSON response object and
	 * can be accessed via response.content in JavaScript.
	 *
	 * If an error has been set, the rendering will be skipped.
	 *
	 * @param string $view The view being rendered.
	 * @param string $layout The layout being rendered.
	 * @return string The rendered view.
	 */
	public function render($view = null, $layout = null) {
		$response = array(
			'error' => null,
			'content' => null,
		);

		if (!empty($this->viewVars['error'])) {
			$view = false;
		}

		if ($view !== false && $this->_getViewFileName($view)) {
			$response['content'] = parent::render($view, $layout);
		}
		if (isset($this->viewVars['_serialize'])) {
			$response = $this->_serialize($response, $this->viewVars['_serialize']);
		}
		return json_encode($response);
	}

	/**
	 * Serializes view vars.
	 *
	 * @param array $response Response data array.
	 * @param array $serialize The viewVars that need to be serialized.
	 * @return array The serialized data.
	 */
	protected function _serialize($response, $serialize) {
		if (is_array($serialize)) {
			foreach ($serialize as $alias => $key) {
				if (is_numeric($alias)) {
					$alias = $key;
				}
				if (array_key_exists($key, $this->viewVars)) {
					$response[$alias] = $this->viewVars[$key];
				}
			}
		} else {
			$response[$serialize] = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
		}
		return $response;
	}

}