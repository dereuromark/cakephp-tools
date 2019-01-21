<?php

namespace Tools\Controller\Component;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Routing\Router;
use Shim\Controller\Component\Component;
use Tools\Utility\Utility;

/**
 * A component included in every app to take care of common stuff.
 *
 * @author Mark Scherer
 * @license MIT
 */
class CommonComponent extends Component {

	/**
	 * For this helper the controller has to be passed as reference
	 * for manual startup with $disableStartup = true (requires this to be called prior to any other method)
	 *
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function startup(Event $event) {
		// Data preparation
		if ($this->Controller->getRequest()->getData() && !Configure::read('DataPreparation.notrim')) {
			$request = $this->Controller->getRequest();
			$newData = Utility::trimDeep($this->Controller->getRequest()->getData());
			foreach ($newData as $k => $v) {
				if ($this->Controller->getRequest()->getData($k) !== $v) {
					$request = $request->withData($k, $v);
				}
			}
			$this->Controller->setRequest($request);
		}
		if ($this->Controller->getRequest()->getQuery() && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(Utility::trimDeep($this->Controller->getRequest()->getQuery())));
		}
		if ($this->Controller->getRequest()->getParam('pass') && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->setRequest($this->Controller->getRequest()->withParam('pass', Utility::trimDeep($this->Controller->getRequest()->getParam('pass'))));
		}
	}

	/**
	 * Returns internal redirect only, otherwise falls back to default.
	 *
	 * Lookup order:
	 * - POST data
	 * - query string
	 * - provided default
	 *
	 * @param string|array $default
	 * @param string|array|null $data
	 * @param string $key
	 *
	 * @return string|array
	 */
	public function getSafeRedirectUrl($default, $data = null, $key = 'redirect') {
		$redirectUrl = $data ?: ($this->Controller->getRequest()->getData($key) ?: $this->Controller->getRequest()->getQuery($key));
		if ($redirectUrl && (substr($redirectUrl, 0, 1) !== '/' || substr($redirectUrl, 0, 2) === '//')) {
			$redirectUrl = null;
		}

		return $redirectUrl ?: $default;
	}

	/**
	 * List all direct actions of a controller
	 *
	 * @return array Actions
	 */
	public function listActions() {
		$parentClassMethods = get_class_methods(get_parent_class($this->Controller));
		$subClassMethods = get_class_methods($this->Controller);
		$classMethods = array_diff($subClassMethods, $parentClassMethods);
		foreach ($classMethods as $key => $classMethod) {
			if (substr($classMethod, 0, 1) === '_') {
				unset($classMethods[$key]);
			}
		}
		return $classMethods;
	}

	/**
	 * Convenience method to check on POSTED data.
	 * Doesn't matter if it's POST, PUT or PATCH.
	 *
	 * Note that you can also use request->is(array('post', 'put', 'patch') directly.
	 *
	 * @return bool If it is of type POST/PUT/PATCH
	 */
	public function isPosted() {
		return $this->Controller->getRequest()->is(['post', 'put', 'patch']);
	}

	/**
	 * Add component just in time (inside actions - only when needed)
	 * aware of plugins and config array (if passed)
	 *
	 * @param string $component Component
	 * @param array $config
	 * @param bool $callbacks (defaults to true)
	 * @return void
	 */
	public function loadComponent($component, array $config = [], $callbacks = true) {
		list($plugin, $componentName) = pluginSplit($component);
		$this->Controller->loadComponent($component, $config);
		if (!$callbacks) {
			return;
		}
		if (method_exists($this->Controller->{$componentName}, 'beforeFilter')) {
			$this->Controller->{$componentName}->beforeFilter(new Event('Controller.initialize', $this->Controller->{$componentName}));
		}
		if (method_exists($this->Controller->{$componentName}, 'startup')) {
			$this->Controller->{$componentName}->startup(new Event('Controller.startup', $this->Controller->{$componentName}));
		}
	}

	/**
	 * Add helper just in time (inside actions - only when needed)
	 * aware of plugins
	 *
	 * @deprecated In 3.x, but kept for easier migration for now. Will be removed in the future.
	 * @param mixed $helpers (single string or multiple array)
	 * @return void
	 */
	public function loadHelper($helpers = []) {
		$this->Controller->viewBuilder()->setHelpers((array)$helpers, true);
	}

	/**
	 * Used to get the value of a passed param.
	 *
	 * @param mixed $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function getPassedParam($var, $default = null) {
		$passed = $this->Controller->getRequest()->getParam('pass');

		return (isset($passed[$var])) ? $passed[$var] : $default;
	}

	/**
	 * Returns defaultUrlParams including configured prefixes.
	 *
	 * @return array URL params
	 */
	public static function defaultUrlParams() {
		$defaults = ['plugin' => false];
		$prefixes = (array)Configure::read('Routing.prefixes');
		foreach ($prefixes as $prefix) {
			$defaults[$prefix] = false;
		}
		return $defaults;
	}

	/**
	 * Returns current url (with all missing params automatically added).
	 * Necessary for Router::url() and comparison of urls to work.
	 *
	 * @param bool $asString Defaults to false = array
	 * @return mixed URL
	 */
	public function currentUrl($asString = false) {
		$action = $this->Controller->getRequest()->getParam('action');

		$passed = (array)$this->Controller->getRequest()->getParam('pass');
		$url = [
			'prefix' => $this->Controller->getRequest()->getParam('prefix'),
			'plugin' => $this->Controller->getRequest()->getParam('plugin'),
			'action' => $action,
			'controller' => $this->Controller->getRequest()->getParam('controller'),
		];
		$url = array_merge($passed, $url);

		if ($asString === true) {
			return Router::url($url);
		}
		return $url;
	}

	/**
	 * Smart Referer Redirect - will try to use an existing referer first
	 * otherwise it will use the default url
	 *
	 * @param mixed $whereTo URL
	 * @param bool $allowSelf if redirect to the same controller/action (url) is allowed
	 * @param int $status
	 * @return \Cake\Http\Response
	 */
	public function autoRedirect($whereTo, $allowSelf = false, $status = 302) {
		if ($allowSelf || $this->Controller->referer(null, true) !== $this->Controller->getRequest()->getRequestTarget()) {
			return $this->Controller->redirect($this->Controller->referer($whereTo, true), $status);
		}
		return $this->Controller->redirect($whereTo, $status);
	}

	/**
	 * Should be a 303, but:
	 * Note: Many pre-HTTP/1.1 user agents do not understand the 303 status. When interoperability with such clients is a concern, the 302 status code may be used instead, since most user agents react to a 302 response as described here for 303.
	 *
	 * TODO: change to 303 with backwardscompatability for older browsers...
	 *
	 * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
	 *
	 * @param mixed $whereTo URL
	 * @param int $status
	 * @return \Cake\Http\Response
	 */
	public function postRedirect($whereTo, $status = 302) {
		return $this->Controller->redirect($whereTo, $status);
	}

	/**
	 * Combine auto with post
	 * also allows whitelisting certain actions for autoRedirect (use Controller::$autoRedirectActions)
	 *
	 * @param mixed $whereTo URL
	 * @param bool $conditionalAutoRedirect false to skip whitelisting
	 * @param int $status
	 * @return \Cake\Http\Response
	 */
	public function autoPostRedirect($whereTo, $conditionalAutoRedirect = true, $status = 302) {
		$referer = $this->Controller->referer($whereTo, true);
		if (!$conditionalAutoRedirect && !empty($referer)) {
			return $this->postRedirect($referer, $status);
		}

		if (!empty($referer)) {
			$referer = Router::parse($referer);
		}

		if ($conditionalAutoRedirect && !empty($this->Controller->autoRedirectActions) && is_array($referer) && !empty($referer['action'])) {
			// Be sure that controller offset exists, otherwise you
			// will run into problems, if you use url rewriting.
			$refererController = null;
			if (isset($referer['controller'])) {
				$refererController = $referer['controller'];
			}
			// fixme
			if (!isset($this->Controller->autoRedirectActions)) {
				$this->Controller->autoRedirectActions = [];
			}

			foreach ($this->Controller->autoRedirectActions as $action) {
				list($controller, $action) = pluginSplit($action);
				if (!empty($controller) && $refererController !== '*' && $refererController !== $controller) {
					continue;
				}
				if (empty($controller) && $refererController !== $this->Controller->getRequest()->getParam('controller')) {
					continue;
				}
				if (!in_array($referer['action'], (array)$this->Controller->autoRedirectActions, true)) {
					continue;
				}
				return $this->autoRedirect($whereTo, true, $status);
			}
		}
		return $this->postRedirect($whereTo, $status);
	}

	/**
	 * Automatically add missing URL parts of the current URL including
	 * - querystring (especially for 3.x then)
	 * - passed params
	 *
	 * @param mixed|null $url
	 * @param int|null $status
	 * @return \Cake\Http\Response
	 */
	public function completeRedirect($url = null, $status = 302) {
		if ($url === null) {
			$url = $this->Controller->getRequest()->params;
			unset($url['pass']);
			unset($url['isAjax']);
		}
		if (is_array($url)) {
			$url += $this->Controller->getRequest()->params['pass'];
		}
		return $this->Controller->redirect($url, $status);
	}

	/**
	 * Set headers to cache this request.
	 * Opposite of Controller::disableCache()
	 *
	 * @param int $seconds
	 * @return void
	 */
	public function forceCache($seconds = HOUR) {
		$response = $this->Controller->getResponse();

		$response = $response->withHeader('Cache-Control', 'public, max-age=' . $seconds)
			->withHeader('Last-modified', gmdate('D, j M Y H:i:s', time()) . ' GMT')
			->withHeader('Expires', gmdate('D, j M Y H:i:s', time() + $seconds) . ' GMT');

		$this->Controller->setResponse($response);
	}

	/**
	 * Referrer checking (where does the user come from)
	 * Only returns true for a valid external referrer.
	 *
	 * @param string|null $ref Referer
	 * @return bool Success
	 */
	public function isForeignReferer($ref = null) {
		if ($ref === null) {
			$ref = env('HTTP_REFERER');
		}
		if (!$ref) {
			return false;
		}
		$base = Configure::read('App.fullBaseUrl') . '/';
		if (strpos($ref, $base) === 0) {
			return false;
		}
		return true;
	}

}
