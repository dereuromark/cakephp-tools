<?php

App::uses('Component', 'Controller');

/**
 * Ajax Component to respond to AJAX requests.
 *
 * Works together with the AjaxView to easily switch
 * output type from HTML to JSON format.
 *
 * It will also avoid redirects and pass those down as content
 * of the JSON response object.
 *
 * Don't forget Configure::write('Ajax.flashKey', 'messages');
 * if you want to use it with Tools.Flash component.
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class AjaxComponent extends Component {

	public $Controller;

	public $components = ['Session'];

	public $respondAsAjax = false;

	protected $_defaultConfig = [
		'autoDetect' => true,
		'resolveRedirect' => true,
		'flashKey' => 'Message.flash' // Use "messages" for Tools plugin Flash component, set to false to disable
	];

	/**
	 * Constructor.
	 *
	 * @param ComponentCollection $collection
	 * @param array $config
	 */
	public function __construct(ComponentCollection $collection, $config = []) {
		$defaults = (array)Configure::read('Ajax') + $this->_defaultConfig;
		$config += $defaults;
		parent::__construct($collection, $config);
	}

	public function initialize(Controller $Controller) {
		$this->Controller = $Controller;

		if (!$this->settings['autoDetect']) {
			return;
		}
		$this->respondAsAjax = $this->Controller->request->is('ajax');
	}

	/**
	 * Called before the Controller::beforeRender(), and before
	 * the view class is loaded, and before Controller::render()
	 *
	 * @param Controller $controller Controller with components to beforeRender
	 * @return void
	 */
	public function beforeRender(Controller $controller) {
		if (!$this->respondAsAjax) {
			return;
		}
		$this->_respondAsAjax();
	}

	/**
	 * AjaxComponent::respondAsAjax()
	 *
	 * @return void
	 */
	protected function _respondAsAjax() {
		$this->Controller->viewClass = 'Tools.Ajax';

		// Set flash messages to the view
		if ($this->settings['flashKey']) {
			$_message = $this->Session->read($this->settings['flashKey']);
			$this->Session->delete($this->settings['flashKey']);
			$this->Controller->set(compact('_message'));
		}
	}

	/**
	 * Called before Controller::redirect(). Allows you to replace the URL that will
	 * be redirected to with a new URL. The return of this method can either be an array or a string.
	 *
	 * If the return is an array and contains a 'url' key. You may also supply the following:
	 *
	 * - `status` The status code for the redirect
	 * - `exit` Whether or not the redirect should exit.
	 *
	 * If your response is a string or an array that does not contain a 'url' key it will
	 * be used as the new URL to redirect to.
	 *
	 * @param Controller $controller Controller with components to beforeRedirect
	 * @param string|array $url Either the string or URL array that is being redirected to.
	 * @param int $status The status code of the redirect
	 * @param bool $exit Will the script exit.
	 * @return array|void Either an array or null.
	 */
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {
		if (!$this->respondAsAjax || !$this->settings['resolveRedirect']) {
			return parent::beforeRedirect($controller, $url, $status, $exit);
		}

		$url = Router::url($url, true);

		if (is_string($status)) {
			$codes = array_flip($this->response->httpCodes());
			if (isset($codes[$status])) {
				$status = $codes[$status];
			}
		}

		$this->Controller->autoRender = true;
		$this->Controller->set('_redirect', compact('url', 'status', 'exit'));
		$serializeKeys = ['_redirect', '_message'];
		if (!empty($this->Controller->viewVars['_serialize'])) {
			$serializeKeys = array_merge($serializeKeys, $this->Controller->viewVars['_serialize']);
		}
		$this->Controller->set('_serialize', $serializeKeys);

		return false;
	}

}
