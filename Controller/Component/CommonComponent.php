<?php

App::uses('Component', 'Controller');
App::uses('Sanitize', 'Utility');
App::uses('Utility', 'Tools.Utility');

/**
 * A component included in every app to take care of common stuff.
 *
 * @author Mark Scherer
 * @copyright 2012 Mark Scherer
 * @license MIT
 */
class CommonComponent extends Component {

	public $components = array('Session', 'RequestHandler');

	public $userModel = 'User';

	/**
	 * For automatic startup
	 * for this helper the controller has to be passed as reference
	 *
	 * @return void
	 */
	public function initialize(Controller $Controller) {
		parent::initialize($Controller);

		$this->Controller = $Controller;
	}

	/**
	 * For this helper the controller has to be passed as reference
	 * for manual startup with $disableStartup = true (requires this to be called prior to any other method)
	 *
	 * @return void
	 */
	public function startup(Controller $Controller = null) {
		parent::startup($Controller);

		// Data preparation
		if (!empty($this->Controller->request->data) && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->request->data = $this->trimDeep($this->Controller->request->data);
		}
		if (!empty($this->Controller->request->query) && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->request->query = $this->trimDeep($this->Controller->request->query);
		}
		if (!empty($this->Controller->request->params['named']) && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->request->params['named'] = $this->trimDeep($this->Controller->request->params['named']);
		}
		if (!empty($this->Controller->request->params['pass']) && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->request->params['pass'] = $this->trimDeep($this->Controller->request->params['pass']);
		}

		// Deprecation notices
		if (Configure::read('App.warnAboutNamedParams')) {
			if (!empty($Controller->request->params['named']) && ($referer = $Controller->request->referer(true)) && $referer !== '/') {
				trigger_error('Named params ' . json_encode($Controller->request->params['named']) . ' - from ' . $referer, E_USER_DEPRECATED);
			}
		}

		// Information gathering
		if (!Configure::read('App.disableMobileDetection') && ($mobile = $this->Session->read('Session.mobile')) === null) {
			App::uses('UserAgentLib', 'Tools.Lib');
			$UserAgentLib = new UserAgentLib();
			$mobile = (int)$UserAgentLib->isMobile();
			$this->Session->write('Session.mobile', $mobile);
		}

		// Auto layout switch
		if ($this->Controller->request->is('ajax')) {
			$this->Controller->layout = 'ajax';
		}
	}

	/**
	 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
	 * Controller::render()
	 *
	 * @param object $Controller Controller with components to beforeRender
	 * @return void
	 */
	public function beforeRender(Controller $Controller) {
		if ($messages = $this->Session->read('Message')) {
			foreach ($messages as $message) {
				$this->flashMessage($message['message'], 'error');
			}
			$this->Session->delete('Message');
		}

		if ($this->Controller->request->is('ajax')) {
			$ajaxMessages = array_merge(
				(array)$this->Session->read('messages'),
				(array)Configure::read('messages')
			);
			// The header can be read with JavaScript and a custom Message can be displayed
			$this->Controller->response->header('X-Ajax-Flashmessage', json_encode($ajaxMessages));

			$this->Session->delete('messages');
		}

		// Custom options
		if (isset($Controller->options)) {
			$Controller->set('options', $Controller->options);
		}
	}

	/**
	 * List all direct actions of a controller
	 *
	 * @return array Actions
	 */
	public function listActions() {
		$class = Inflector::camelize($this->Controller->name) . 'Controller';
		$parentClassMethods = get_class_methods(get_parent_class($class));
		$subClassMethods = get_class_methods($class);
		$classMethods = array_diff($subClassMethods, $parentClassMethods);
		foreach ($classMethods as $key => $value) {
			if (substr($value, 0, 1) === '_') {
				unset($classMethods[$key]);
			}
		}
		return $classMethods;
	}

	/**
	 * Convenience method to check on POSTED data.
	 * Doesn't matter if it's POST or PUT.
	 *
	 * @return boolean isPost
	 */
	public function isPosted() {
		return $this->Controller->request->is('post') || $this->Controller->request->is('put');
	}

	/**
	 * Updates FlashMessage SessionContent (to enable unlimited messages of one case)
	 *
	 * @param string messagestring
	 * @param string class ['error', 'warning', 'success', 'info']
	 * @return void
	 */
	public function flashMessage($messagestring, $class = null) {
		switch ($class) {
			case 'error':
			case 'warning':
			case 'success':
				break;
			default:
				$class = 'info';
				break;
		}

		$old = (array)$this->Session->read('messages');
		if (isset($old[$class]) && count($old[$class]) > 99) {
			array_shift($old[$class]);
		}
		$old[$class][] = $messagestring;
		$this->Session->write('messages', $old);
	}

	/**
	 * FlashMessages that are not saved (only for current view)
	 * will be merged into the session flash ones prior to output
	 *
	 * @param string messagestring
	 * @param string class ['error', 'warning', 'success', 'info']
	 * @return void
	 */
	public static function transientFlashMessage($messagestring, $class = null) {
		switch ($class) {
			case 'error':
			case 'warning':
			case 'success':
				break;
			default:
				$class = 'info';
				break;
		}

		$old = (array)Configure::read('messages');
		if (isset($old[$class]) && count($old[$class]) > 99) {
			array_shift($old[$class]);
		}
		$old[$class][] = $messagestring;
		Configure::write('messages', $old);
	}

	/**
	 * Add helper just in time (inside actions - only when needed)
	 * aware of plugins
	 * @param mixed $helpers (single string or multiple array)
	 */
	public function loadHelper($helpers = array()) {
		$this->Controller->helpers = array_merge($this->Controller->helpers, (array)$helpers);
	}

	/**
	 * Add lib just in time (inside actions - only when needed)
	 * aware of plugins and config array (if passed)
	 * ONLY works if constructor consists only of one param (settings)!
	 * @param mixed $libs (single string or multiple array)
	 * e.g.: array('Tools.MyLib'=>array('key'=>'value'), ...)
	 */
	public function loadLib($libs = array()) {
		foreach ((array)$libs as $lib => $config) {
			if (is_int($lib)) {
				$lib = $config;
				$config = null;
			}

			list($plugin, $libName) = pluginSplit($lib);
			if (isset($this->Controller->{$libName})) {
				continue;
			}
			$package = 'Lib';
			if ($plugin) {
				$package = $plugin . '.' . $package;
			}
			App::uses($libName, $package);
			$this->Controller->{$libName} = new $libName($config);
		}
	}

	/**
	 * Add component just in time (inside actions - only when needed)
	 * aware of plugins and config array (if passed)
	 * @param mixed $components (single string or multiple array)
	 * @poaram bool $callbacks (defaults to true)
	 */
	public function loadComponent($components = array(), $callbacks = true) {
		foreach ((array)$components as $component => $config) {
			if (is_int($component)) {
				$component = $config;
				$config = array();
			}
			list($plugin, $componentName) = pluginSplit($component);
			if (isset($this->Controller->{$componentName})) {
				continue;
			}

			$this->Controller->{$componentName} = $this->Controller->Components->load($component, $config);
			if (!$callbacks) {
				continue;
			}
			if (method_exists($this->Controller->{$componentName}, 'initialize')) {
				$this->Controller->{$componentName}->initialize($this->Controller);
			}
			if (method_exists($this->Controller->{$componentName}, 'startup')) {
				$this->Controller->{$componentName}->startup($this->Controller);
			}
		}
	}

	/**
	 * Used to get the value of a passed param.
	 *
	 * @param mixed $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function getPassedParam($var, $default = null) {
		return (isset($this->Controller->request->params['pass'][$var])) ? $this->Controller->request->params['pass'][$var] : $default;
	}

	/**
	 * Used to get the value of a named param.
	 * @deprecated - move to query strings instead
	 *
	 * @param mixed $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function getNamedParam($var, $default = null) {
		return (isset($this->Controller->request->params['named'][$var])) ? $this->Controller->request->params['named'][$var] : $default;
	}

	/**
	 * Used to get the value of a get query.
	 * @deprecated - use request->query() instead
	 *
	 * @param mixed $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function getQueryParam($var, $default = null) {
		trigger_error('deprecated - use $this->request->query()');
		return (isset($this->Controller->request->query[$var])) ? $this->Controller->request->query[$var] : $default;
	}

	/**
	 * Returns defaultUrlParams including configured prefixes.
	 *
	 * @return array Url params
	 */
	public static function defaultUrlParams() {
		$defaults = array('plugin' => false);
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
	 * @param boolean $asString: defaults to false = array
	 * @return mixed Url
	 */
	public function currentUrl($asString = false) {
		if (isset($this->Controller->request->params['prefix']) && mb_strpos($this->Controller->request->params['action'], $this->Controller->request->params['prefix']) === 0) {
			$action = mb_substr($this->Controller->request->params['action'], mb_strlen($this->Controller->request->params['prefix']) + 1);
		} else {
			$action = $this->Controller->request->params['action'];
		}

		$url = array_merge($this->Controller->request->params['named'], $this->Controller->request->params['pass'], array('prefix' => isset($this->Controller->request->params['prefix']) ? $this->Controller->request->params['prefix'] : null,
			'plugin' => $this->Controller->request->params['plugin'], 'action' => $action, 'controller' => $this->Controller->request->params['controller']));

		if ($asString === true) {
			return Router::url($url);
		}
		return $url;
	}

	/**
	 * Tries to allow super admin access for certain urls via `Config.pwd`.
	 * Only used in admin actions and only to prevent accidental data loss due to incorrect access.
	 * Do not assume this to be a safe access control mechanism!
	 *
	 * Password can be passed as named param or query string param.
	 *
	 * @return boolean Success
	 */
	public function validAdminUrlAccess() {
		$pwd = Configure::read('Config.pwd');
		if (!$pwd) {
			return false;
		}
		$urlPwd = $this->getNamedParam('pwd');
		if (!$urlPwd) {
			$urlPwd = $this->getQueryParam('pwd');
		}
		if (!$urlPwd) {
			return false;
		}
		return $pwd === $urlPwd;
	}

	/**
	 * Direct login for a specific user id.
	 * Will respect full login scope (if defined in auth setup) as well as contained data and
	 * can therefore return false if the login fails due to unmatched scope.
	 *
	 * @see DirectAuthentication auth adapter
	 * @param mixed $id User id
	 * @param array $settings Settings for DirectAuthentication
	 * - fields
	 * @return boolean Success
	 */
	public function manualLogin($id, $settings = array()) {
		$requestData = $this->Controller->request->data;
		$authData = $this->Controller->Auth->authenticate;
		$settings = array_merge($authData, $settings);
		$settings['fields'] = array('username' => 'id');

		$this->Controller->request->data = array($this->userModel => array('id' => $id));
		$this->Controller->Auth->authenticate = array('Tools.Direct' => $settings);
		$result = $this->Controller->Auth->login();

		$this->Controller->Auth->authenticate = $authData;
		$this->Controller->request->data = $requestData;
		return $result;
	}

	/**
	 * Force login for a specific user id.
	 * Only fails if the user does not exist or if he is already
	 * logged in as it ignores the usual scope.
	 *
	 * Better than Auth->login($data) since it respects the other auth configs such as
	 * fields, contain, recursive and userModel.
	 *
	 * @param mixed $id User id
	 * @return boolean Success
	 */
	public function forceLogin($id) {
		$settings = array(
			'scope' => array(),
		);
		return $this->manualLogin($id, $settings);
		/*
		if (!isset($this->User)) {
			$this->User = ClassRegistry::init(defined('CLASS_USER') ? CLASS_USER : $this->userModel);
		}
		$data = $this->User->get($id);
		if (!$data) {
			return false;
		}
		$data = $data[$this->userModel];
		return $this->Controller->Auth->login($data);
		*/
	}

	/**
	 * Smart Referer Redirect - will try to use an existing referer first
	 * otherwise it will use the default url
	 *
	 * @param mixed $url
	 * @param boolean $allowSelf if redirect to the same controller/action (url) is allowed
	 * @param integer $status
	 * @return void
	 */
	public function autoRedirect($whereTo, $allowSelf = true, $status = null) {
		if ($allowSelf || $this->Controller->referer(null, true) !== '/' . $this->Controller->request->url) {
			$this->Controller->redirect($this->Controller->referer($whereTo, true), $status);
		}
		$this->Controller->redirect($whereTo, $status);
	}

	/**
	 * Should be a 303, but:
	 * Note: Many pre-HTTP/1.1 user agents do not understand the 303 status. When interoperability with such clients is a concern, the 302 status code may be used instead, since most user agents react to a 302 response as described here for 303.
	 *
	 * TODO: change to 303 with backwardscompatability for older browsers...
	 *
	 * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
	 * @param mixed $url
	 * @param integer $status
	 * @return void
	 */
	public function postRedirect($whereTo, $status = 302) {
		$this->Controller->redirect($whereTo, $status);
	}

	/**
	 * Combine auto with post
	 * also allows whitelisting certain actions for autoRedirect (use Controller::$autoRedirectActions)
	 * @param mixed $url
	 * @param boolean $conditionalAutoRedirect false to skip whitelisting
	 * @param integer $status
	 * @return void
	 */
	public function autoPostRedirect($whereTo, $conditionalAutoRedirect = true, $status = 302) {
		$referer = $this->Controller->referer($whereTo, true);
		if (!$conditionalAutoRedirect && !empty($referer)) {
			$this->postRedirect($referer, $status);
		}

		if (!empty($referer)) {
			$referer = Router::parse($referer);
		}

		if (!$conditionalAutoRedirect || empty($this->Controller->autoRedirectActions) || is_array($referer) && !empty($referer['action'])) {
			$refererController = Inflector::camelize($referer['controller']);
			// fixme
			if (!isset($this->Controller->autoRedirectActions)) {
				$this->Controller->autoRedirectActions = array();
			}
			foreach ($this->Controller->autoRedirectActions as $action) {
				list($controller, $action) = pluginSplit($action);
				if (!empty($controller) && $refererController !== '*' && $refererController != $controller) {
					continue;
				}
				if (empty($controller) && $refererController != Inflector::camelize($this->Controller->request->params['controller'])) {
					continue;
				}
				if (!in_array($referer['action'], $this->Controller->autoRedirectActions)) {
					continue;
				}
				$this->autoRedirect($whereTo, true, $status);
			}
		}
		$this->postRedirect($whereTo, $status);
	}

	/**
	 * Automatically add missing url parts of the current url including
	 * - querystring (especially for 3.x then)
	 * - named params (until 3.x when they will become deprecated)
	 * - passed params
	 *
	 * @param mixed $url
	 * @param intger $status
	 * @param boolean $exit
	 * @return void
	 */
	public function completeRedirect($url = null, $status = null, $exit = true) {
		if ($url === null) {
			$url = $this->Controller->request->params;
			unset($url['named']);
			unset($url['pass']);
			unset($url['isAjax']);
		}
		if (is_array($url)) {
			$url += $this->Controller->request->params['named'];
			$url += $this->Controller->request->params['pass'];
		}
		return $this->Controller->redirect($url, $status, $exit);
	}

	/**
	 * Only redirect to itself if cookies are on
	 * Prevents problems with lost data
	 * Note: Many pre-HTTP/1.1 user agents do not understand the 303 status. When interoperability with such clients is a concern, the 302 status code may be used instead, since most user agents react to a 302 response as described here for 303.
	 *
	 * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
	 * TODO: change to 303 with backwardscompatability for older browsers...
	 */
	public function prgRedirect($status = 302) {
		if (!empty($_COOKIE[Configure::read('Session.cookie')])) {
			$this->Controller->redirect('/' . $this->Controller->request->url, $status);
		}
	}

	/**
	 * Handler for passing some meta data to the view
	 * uses CommonHelper to include them in the layout
	 *
	 * @param type (relevance):
	 * - title (10), description (9), robots(7), language(5), keywords (0)
	 * - custom: abstract (1), category(1), GOOGLEBOT(0) ...
	 * @return void
	 */
	public function setMeta($type, $content, $prep = true) {
		if (!in_array($type, array('title', 'canonical', 'description', 'keywords', 'robots', 'language', 'custom'))) {
			trigger_error(__('Meta Type invalid'), E_USER_WARNING);
			return;
		}
		if ($type === 'canonical' && $prep) {
			$content = Router::url($content);
		}
		if ($type === 'canonical' && $prep) {
			$content = h($content);
		}
		Configure::write('Meta.' . $type, $content);
	}

	/**
	 * Set headers to cache this request.
	 * Opposite of Controller::disableCache()
	 * TODO: set response class header instead
	 *
	 * @param integer $seconds
	 * @return void
	 */
	public function forceCache($seconds = HOUR) {
		$this->Controller->response->header('Cache-Control', 'public, max-age=' . $seconds);
		$this->Controller->response->header('Last-modified', gmdate("D, j M Y H:i:s", time()) . " GMT");
		$this->Controller->response->header('Expires', gmdate("D, j M Y H:i:s", time() + $seconds) . " GMT");
	}

	/**
	 * Referrer checking (where does the user come from)
	 * Only returns true for a valid external referrer.
	 *
	 * @return boolean Success
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

	/**
	 * CommonComponent::denyAccess()
	 *
	 * @return void
	 */
	public function denyAccess() {
		$ref = env('HTTP_USER_AGENT');
		if ($this->isForeignReferer($ref)) {
			if (strpos(strtolower($ref), 'http://anonymouse.org/') === 0) {
				$this->cakeError('error406', array());
			}
		}
	}

	/**
	 * CommonComponent::monitorCookieProblems()
	 *
	 * @return void
	 */
	public function monitorCookieProblems() {
		$ip = $this->RequestHandler->getClientIP();
		$host = gethostbyaddr($ip);
		$sessionId = session_id();
		if (empty($sessionId)) {
			$sessionId = '--';
		}
		if (empty($_REQUEST[Configure::read('Session.cookie')]) && !($res = Cache::read($ip))) {
			$this->log('CookieProblem:: SID: ' . $sessionId . ' | IP: ' . $ip . ' (' . $host . ') | REF: ' . $this->Controller->referer() . ' | Agent: ' . env('HTTP_USER_AGENT'), 'noscript');
			Cache::write($ip, 1);
		}
	}

	/**
	 * //todo: move to Utility?
	 *
	 * @return boolean true if disabled (bots, etc), false if enabled
	 */
	public static function cookiesDisabled() {
		if (!empty($_COOKIE) && !empty($_COOKIE[Configure::read('Session.cookie')])) {
			return false;
		}
		return true;
	}

	/**
	 * Quick sql debug from controller dynamically
	 * or statically from just about any other place in the script
	 *
	 * @param boolean $exit If script should exit.
	 * @return boolean Success
	 */
	public function sql($exit = true) {
		if (isset($this->Controller)) {
			$object = $this->Controller->{$this->Controller->modelClass};
		} else {
			$object = ClassRegistry::init(defined('CLASS_USER') ? CLASS_USER : $this->userModel);
		}

		$log = $object->getDataSource()->getLog(false, false);
		foreach ($log['log'] as $key => $value) {
			if (strpos($value['query'], 'SHOW ') === 0 || strpos($value['query'], 'SELECT CHARACTER_SET_NAME ') === 0) {
				unset($log['log'][$key]);
				continue;
			}
		}

		if ($die) {
			debug($log);
			die();
		}

		$log = print_r($log, true);
		App::uses('CakeLog', 'Log');
		return CakeLog::write('sql', $log);
	}

	/**
	 * Localize
	 *
	 * @return boolean Success
	 */
	 public function localize($lang = null) {
		if ($lang === null) {
			$lang = Configure::read('Config.language');
		}
		if (empty($lang)) {
			return false;
		}

		if (($pos = strpos($lang, '-')) !== false) {
			$lang = substr($lang, 0, $pos);
		}
		if ($lang == DEFAULT_LANGUAGE) {
			return null;
		}

		if (!((array)$pattern = Configure::read('LocalizationPattern.' . $lang))) {
			return false;
		}
		foreach ($pattern as $key => $value) {
			Configure::write('Localization.' . $key, $value);
		}
		return true;
	}

	/**
	 * Main controller function for consistency in controller naming
	 */
	public function ensureControllerConsistency() {
		// problems with plugins
		if (!empty($this->Controller->request->params['plugin'])) {
			return;
		}

		if (($name = strtolower(Inflector::underscore($this->Controller->name))) !== $this->Controller->request->params['controller']) {
			$this->Controller->log('301: ' . $this->Controller->request->params['controller'] . ' => ' . $name . ' (Ref ' . $this->Controller->referer() . ')', '301'); // log problem with controller naming
			if (!$this->Controller->RequestHandler->isPost()) {
				// underscored version is the only valid one to avoid duplicate content
				$url = array('controller' => $name, 'action' => $this->Controller->request->params['action']);
				$url = array_merge($url, $this->Controller->request->params['pass'], $this->Controller->request->params['named']);
				//TODO: add plugin/admin stuff which right now is supposed to work automatically
				$this->Controller->redirect($url, 301);
			}
		}
		return true;
		// problem with extensions (rss etc)

		if (empty($this->Controller->request->params['prefix']) && ($currentUrl = $this->currentUrl(true)) != $this->Controller->here) {
			//pr($this->Controller->here);
			//pr($currentUrl);
			$this->log('301: ' . $this->Controller->here . ' => ' . $currentUrl . ' (Referer ' . $this->Controller->referer() . ')', '301');

			if (!$this->Controller->RequestHandler->isPost()) {
				$url = array('controller' => $this->Controller->request->params['controller'], 'action' => $this->Controller->request->params['action']);
				$url = array_merge($url, $this->Controller->request->params['pass'], $this->Controller->request->params['named']);
				$this->Controller->redirect($url, 301);
			}
		}
	}

	/**
	 * Main controller function for seo-slugs
	 * passed titleSlug != current title => redirect to the expected one
	 */
	public function ensureConsistency($id, $passedTitleSlug, $currentTitle) {
		$expectedTitle = slug($currentTitle);
		if (empty($passedTitleSlug) || $expectedTitle != $passedTitleSlug) { # case sensitive!!!
			$ref = env('HTTP_REFERER');
			if (!$this->isForeignReferer($ref)) {
				$this->Controller->log('Internal ConsistencyProblem at \'' . $ref . '\' - [' . $passedTitleSlug . '] instead of [' . $expectedTitle . ']', 'referer');
			} else {
				$this->Controller->log('External ConsistencyProblem at \'' . $ref . '\' - [' . $passedTitleSlug . '] instead of [' . $expectedTitle . ']', 'referer');
			}
			$this->Controller->redirect(array($id, $expectedTitle), 301);
		}
	}

	/**
	 * Try to detect group for a multidim array for select boxes.
	 * Extracts the group name of the selected key.
	 *
	 * @param array $array
	 * @param string $key
	 * @param array $matching
	 * @return string result
	 */
	public static function getGroup($multiDimArray, $key, $matching = array()) {
		if (!is_array($multiDimArray) || empty($key)) {
			return '';
		}
		foreach ($multiDimArray as $group => $data) {
			if (array_key_exists($key, $data)) {
				if (!empty($matching)) {
					if (array_key_exists($group, $matching)) {
						return $matching[$group];
					}
					return '';
				}
				return $group;
			}
		}
		return '';
	}

/*** DEEP FUNCTIONS ***/

	/**
	 * Move to boostrap?
	 */
	public function trimDeep($value) {
		$value = is_array($value) ? array_map(array($this, 'trimDeep'), $value) : trim($value);
		return $value;
	}

	/**
	 * Move to boostrap?
	 */
	public function specialcharsDeep($value) {
		$value = is_array($value) ? array_map(array($this, 'specialcharsDeep'), $value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		return $value;
	}

	/**
	 * Move to boostrap?
	 */
	public function deep($function, $value) {
		$value = is_array($value) ? array_map(array($this, $function), $value) : $function($value);
		return $value;
	}

	/**
	 * Takes list of items and transforms it into an array
	 * + cleaning (trim, no empty parts, etc).
	 * Similar to String::tokenize, but with more logic.
	 *
	 * //TODO: 3.4. parameter as array, move to Lib
	 *
	 * @param string $string containing the parts
	 * @param string $separator (defaults to ',')
	 * @param boolean $camelize (true/false): problems with äöüß etc!
	 * @return array Results as list
	 */
	public function parseList($string, $separator = null, $camelize = false, $capitalize = true) {
		if ($separator === null) {
			$separator = ',';
		}

		// parses the list, but leaves tokens untouched inside () brackets
		$stringArray = String::tokenize($string, $separator);
		$returnArray = array();

		if (empty($stringArray)) {
			return array();
		}

		foreach ($stringArray as $t) {
			$t = trim($t);
			if (!empty($t)) {

				if ($camelize === true) {
					$t = mb_strtolower($t);
					$t = Inflector::camelize(Inflector::underscore($t)); # problems with non-alpha chars!!
				} elseif ($capitalize === true) {
					$t = ucwords($t);
				}
				$returnArray[] = $t;
			}
		}
		return $returnArray;
	}

	/**
	 * //todo move to lib!!!
	 *
	 * @param string $s
	 * @return mixed
	 */
	public static function separators($s = null, $valueOnly = false) {
		$separatorsValues = array(SEPARATOR_COMMA => ',', SEPARATOR_SEMI => ';', SEPARATOR_SPACE => ' ', SEPARATOR_TAB => TB, SEPARATOR_NL => NL);

		$separators = array(SEPARATOR_COMMA => '[ , ] ' . __('Comma'), SEPARATOR_SEMI => '[ ; ] ' . __('Semicolon'), SEPARATOR_SPACE => '[ &nbsp; ] ' . __('Space'), SEPARATOR_TAB =>
			'[ &nbsp;&nbsp;&nbsp;&nbsp; ] ' . __('Tabulator'), SEPARATOR_NL => '[ \n ] ' . __('New Line'));

		if ($s !== null) {
			if (array_key_exists($s, $separators)) {
				if ($valueOnly) {
					return $separatorsValues[$s];
				}
				return $separators[$s];
			}
			return '';
		}
		return $valueOnly ? $separatorsValues : $separators;
	}

	/**
	 * //TODO: move somewhere else
	 * Assign Array to Char Array
	 *
	 * PROTECTED NAMES (content cannot contain those): undefined
	 *
	 * @var content array
	 * @var char array
	 * @return array: chars with content
	 */
	public function assignToChar($contentArray, $charArray = null) {
		$res = array();
		$res['undefined'] = array();

		if (empty($charArray)) {
			$charArray = $this->alphaFilterSymbols();
		}

		foreach ($contentArray as $content) {
			$done = false;

			// loop them trough
			foreach ($charArray as $char) {
				if (empty($res[$char])) { // throws warnings otherwise
					$res[$char] = array();
				}
				if (!empty($content) && strtolower(substr($content, 0, 1)) == $char) {
					$res[$char][] = $content;
					$done = true;
				}
			}

			// no match?
			if (!empty($content) && !$done) {
				$res['undefined'][] = $content;
			}

		}
		return $res;
	}

	/**
	 * Expects email to be valid!
	 * TODO: move to Lib
	 * @return array email - pattern: array('email'=>,'name'=>)
	 */
	public function splitEmail($email, $abortOnError = false) {
		$array = array('email' => '', 'name' => '');
		if (($pos = mb_strpos($email, '<')) !== false) {
			$name = substr($email, 0, $pos);
			$email = substr($email, $pos + 1);
		}
		if (($pos = mb_strrpos($email, '>')) !== false) {
			$email = substr($email, 0, $pos);
		}
		$email = trim($email);
		if (!empty($email)) {
			$array['email'] = $email;
		}
		if (!empty($name)) {
			$array['name'] = trim($name);
		}

		return $array;
	}

	/**
	 * TODO: move to Lib
	 * @param string $email
	 * @param string $name (optional, will use email otherwise)
	 */
	public function combineEmail($email, $name = null) {
		if (empty($email)) {
			return '';
		}
		if (empty($name)) {
			$name = $email;
		}
		return $name . ' <' . $email['email'] . '>';
	}

	/**
	 * TODO: move to Lib
	 * returns type
	 * - username: everything till @ (xyz@abc.de => xyz)
	 * - hostname: whole domain (xyz@abc.de => abc.de)
	 * - tld: top level domain only (xyz@abc.de => de)
	 * - domain: if available (xyz@e.abc.de => abc)
	 * - subdomain: if available (xyz@e.abc.de => e)
	 * @param string $email: well formatted email! (containing one @ and one .)
	 * @param string $type (TODO: defaults to return all elements)
	 * @return string or false on failure
	 */
	public function extractEmailInfo($email, $type = null) {
		//$checkpos = strrpos($email, '@');
		$nameParts = explode('@', $email);
		if (count($nameParts) !== 2) {
			return false;
		}

		if ($type === 'username') {
			return $nameParts[0];
		}
		if ($type === 'hostname') {
			return $nameParts[1];
		}

		$checkpos = strrpos($nameParts[1], '.');
		$tld = trim(mb_substr($nameParts[1], $checkpos + 1));

		if ($type === 'tld') {
			return $tld;
		}

		$server = trim(mb_substr($nameParts[1], 0, $checkpos));

		//TODO; include 3rd-Level-Label
		$domain = '';
		$subdomain = '';
		$checkpos = strrpos($server, '.');
		if ($checkpos !== false) {
			$subdomain = trim(mb_substr($server, 0, $checkpos));
			$domain = trim(mb_substr($server, $checkpos + 1));
		}

		if ($type === 'domain') {
			return $domain;
		}
		if ($type === 'subdomain') {
			return $subdomain;
		}

		//$hostParts = explode();
		//$check = trim(mb_substr($email, $checkpos));
		return '';
	}

	/**
	 * Returns searchArray (options['wildcard'] TRUE/FALSE)
	 * TODO: move to SearchLib etc
	 *
	 * @param string $keyword
	 * @param string $searchphrase
	 * @param array $options
	 * @return array Cleaned array('keyword'=>'searchphrase') or array('keyword LIKE'=>'searchphrase')
	 */
	public function getSearchItem($keyword = null, $searchphrase = null, $options = array()) {

		if (isset($options['wildcard']) && $options['wildcard'] == true) {
			if (strpos($searchphrase, '*') !== false || strpos($searchphrase, '_') !== false) {
				$keyword .= ' LIKE';
				$searchphrase = str_replace('*', '%', $searchphrase);
				// additionally remove % ?
				//$searchphrase = str_replace(array('%','_'), array('',''), $searchphrase);
			}
		} else {
			// allow % and _ to remain in searchstring (without LIKE not problematic), * has no effect either!
		}

		return array($keyword => $searchphrase);
	}

	/**
	 * Returns auto-generated password
	 *
	 * @param string $type: user, ...
	 * @param integer $length (if no type is submitted)
	 * @return pwd on success, empty string otherwise
	 * @deprecated - use RandomLib
	 */
	public static function pwd($type = null, $length = null) {
		trigger_error('deprecated');
		App::uses('RandomLib', 'Tools.Lib');
		if (!empty($type) && $type === 'user') {
			return RandomLib::pronounceablePwd(6);
		}
		if (!empty($length)) {
			return RandomLib::pronounceablePwd($length);
		}
		return '';
	}

	/**
	 * TODO: move to Lib
	 * Checks if string contains @ sign
	 *
	 * @param string
	 * @return true if at least one @ is in the string, false otherwise
	 */
	public static function containsAtSign($string = null) {
		if (!empty($string) && strpos($string, '@') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * Get the Corresponding Message to an HTTP Error Code
	 *
	 * @param integer $code: 100...505
	 * @param boolean $autoTranslate
	 * @return array codes if code is NULL, otherwise string $code (empty string on failure)
	 */
	public function responseCodes($code = null, $autoTranslate = false) {
		//TODO: use core ones Controller::httpCodes
		$responses = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Time-out',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Large',
			415 => 'Unsupported Media Type',
			416 => 'Requested range not satisfiable',
			417 => 'Expectation Failed',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Time-out',
			505 => 'HTTP Version not supported' # MOD 2009-07-21 ms: 505 added!!!
		);
		if ($code === null) {
			if ($autoTranslate) {
				foreach ($responses as $key => $value) {
					$responses[$key] = __($value);
				}
			}
			return $responses;
		}
		// RFC 2616 states that all unknown HTTP codes must be treated the same as the
		// base code in their class.
		if (!isset($responses[$code])) {
			$code = floor($code / 100) * 100;
		}

		if (!empty($code) && array_key_exists((int)$code, $responses)) {
			if ($autoTranslate) {
				return __($responses[$code]);
			}
			return $responses[$code];
		}
		return '';
	}

	/**
	 * Get the Corresponding Message to an HTTP Error Code
	 *
	 * @param integer $code: 4xx...5xx
	 * @return string
	 */
	public function smtpResponseCodes($code = null, $autoTranslate = false) {
		// 550 5.1.1 User is unknown
		// 552 5.2.2 Storage Exceeded
		$responses = array(
			451 => 'Need to authenticate',
			550 => 'User Unknown',
			552 => 'Storage Exceeded',
			554 => 'Refused'
		);
		if (!empty($code) && array_key_exists((int)$code, $responses)) {
			if ($autoTranslate) {
				return __($responses[$code]);
			}
			return $responses[$code];
		}
		return '';
	}

}
