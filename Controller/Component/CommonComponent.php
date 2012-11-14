<?php

/* just some common functions - by mark */
App::uses('Component', 'Controller');
App::uses('Sanitize', 'Utility');
App::uses('Utility', 'Tools.Utility');

/**
 * A component included in every app to take care of common stuff
 *
 * @author Mark Scherer
 * @copyright 2012 Mark Scherer
 * @license MIT
 *
 * 2012-02-08 ms
 */
class CommonComponent extends Component {

	public $components = array('Session', 'RequestHandler');

	public $allowedChars = array('Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß');
	public $removeChars = false;

	public $paginationMaxLimit = 100;
	public $counterStartTime = null;
	//public $disableStartup = true;

	static $debugContent = array();

	/**
	 * for automatic startup
	 * for this helper the controller has to be passed as reference
	 * 2009-12-19 ms
	 */
	public function initialize(Controller $Controller) {
		parent::initialize($Controller);

		$this->Controller = $Controller;
	}

	/**
	 * //TODO: log loop redirects!
	 * 2010-11-03 ms
	 */
	/*
	public function beforeRedirect(Controller $Controller) {

	}
	*/

	/**
	 * for this helper the controller has to be passed as reference
	 * for manual startup with $disableStartup = true (requires this to be called prior to any other method)
	 * 2009-12-19 ms
	 */
	public function startup(Controller $Controller = null) {
		/** DATA PREPARATION **/

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

		/** Information Gathering **/
		if (!Configure::read('App.disableMobileDetection') && ($mobile = $this->Session->read('Session.mobile')) === null) {
			App::uses('UserAgentLib', 'Tools.Lib');
			$UserAgentLib = new UserAgentLib();
			$mobile = (int)$UserAgentLib->isMobile();
			$this->Session->write('Session.mobile', $mobile);
		}

		/** Layout **/
		if ($this->Controller->request->is('ajax')) {
			$this->Controller->layout = 'ajax';
		}
	}

	/**
	 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
	 * Controller::render()
	 *
	 * Created: 2010-10-10
	 * @param object $Controller Controller with components to beforeRender
	 * @return void
	 * @access public
	 * @author deltachaos
	 */
	public function beforeRender(Controller $Controller) {
		if ($this->RequestHandler->isAjax()) {
			$ajaxMessages = array_merge(
				(array)$this->Session->read('messages'),
				(array)Configure::read('messages')
			);
			# The Header can be read with JavaScript and a custom Message can be displayed
			header('X-Ajax-Flashmessage:' . json_encode($ajaxMessages));

			# AJAX debug off
			Configure::write('debug', 0);
		}

		# custom options
		if (isset($Controller->options)) {
			$Controller->set('options', $Controller->options);
		}

		if ($messages = $Controller->Session->read('Message')) {
			foreach ($messages as $message) {
				$this->flashMessage($message['message'], 'error');
			}
			$Controller->Session->delete('Message');
		}
		# Generates validation error messages for HABTM fields
		//$this->_habtmValidation();
	}

/*** Important Helper Methods ***/

	/**
	 * convinience method to check on POSTED data
	 * doesnt matter if its post or put
	 * @return bool $isPost
	 * 2011-12-09 ms
	 */
	public function isPosted() {
		return $this->Controller->request->is('post') || $this->Controller->request->is('put');
	}

	//deprecated - use isPosted instead
	public function isPost() {
		trigger_error('deprecated - use isPosted()');
		return $this->Controller->request->is('post') || $this->Controller->request->is('put');
	}

	/**
	 * Updates FlashMessage SessionContent (to enable unlimited messages of one case)
	 *
	 * @param STRING messagestring
	 * @param STRING class ['error', 'warning', 'success', 'info']
	 * @return void
	 * 2008-11-06 ms
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
	 * flashMessages that are not saved (only for current view)
	 * will be merged into the session flash ones prior to output
	 *
	 * @param STRING messagestring
	 * @param STRING class ['error', 'warning', 'success', 'info']
	 * @return void
	 * @access static
	 * 2010-05-01 ms
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
	 * not fully tested yet!
	 */
	public function postAndRedirect($url, $data) {
		/*
		$fields = array();
		foreach ($data as $key => $val) {
			$fields[] = $key.'='.$val;
		}
		*/
		$ch = curl_init(Router::url($url, true));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($ch, CURLOPT_USERAGENT, env('HTTP_USER_AGENT'));
		curl_exec($ch);
		curl_close($ch);
		die();
	}

	/**
	 * @deprecated
	 */
	public function addHelper($helpers = array()) {
		trigger_error('deprecated');
		$this->loadHelper($helpers);
	}

	/**
	 * @deprecated
	 */
	public function addComponent($helpers = array()) {
		trigger_error('deprecated');
		$this->loadComponent($helpers);
	}


	/**
	 * add helper just in time (inside actions - only when needed)
	 * aware of plugins
	 * @param mixed $helpers (single string or multiple array)
	 * 2010-10-06 ms
	 */
	public function loadHelper($helpers = array()) {
		$this->Controller->helpers = array_merge($this->Controller->helpers, (array)$helpers);
	}

	/**
	 * add lib just in time (inside actions - only when needed)
	 * aware of plugins and config array (if passed)
	 * ONLY works if constructor consists only of one param (settings)!
	 * @param mixed $libs (single string or multiple array)
	 * e.g.: array('Tools.MyLib'=>array('key'=>'value'), ...)
	 * 2010-11-10 ms
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
			//App::import('Lib', $lib);
			$package = 'Lib';
			if ($plugin) {
				$package = $plugin.'.'.$package;
			}
			App::uses($libName, $package);
			$this->Controller->{$libName} = new $libName($config);
		}
	}

	/**
	 * add component just in time (inside actions - only when needed)
	 * aware of plugins and config array (if passed)
	 * @param mixed $components (single string or multiple array)
	 * @poaram bool $callbacks (defaults to true)
	 * 2011-11-02 ms
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
			//$this->Paypal->initialize($this);
			//App::import('Component', $component);

			//$componentFullName = $componentName.'Component';
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
	 * Used to get the value of a named param
	 * @param mixed $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function getPassedParam($var, $default = null) {
		return (isset($this->Controller->request->params['pass'][$var])) ? $this->Controller->request->params['pass'][$var] : $default;
	}

	/**
	 * Used to get the value of a named param
	 * @param mixed $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function getNamedParam($var, $default = null) {
		return (isset($this->Controller->request->params['named'][$var])) ? $this->Controller->request->params['named'][$var] : $default;
	}

	/**
	 * Used to get the value of a get query
	 * @deprecated - use request->query() instead
	 *
	 * @param mixed $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function getQueryParam($var, $default = null) {
		return (isset($this->Controller->request->query[$var])) ? $this->Controller->request->query[$var] : $default;
	}

	/**
	 * 2011-11-02 ms
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
	 * return current url (with all missing params automatically added)
	 * necessary for Router::url() and comparison of urls to work
	 * @param bool $asString: defaults to false = array
	 * 2009-12-26 ms
	 */
	public function currentUrl($asString = false) {

		if (isset($this->Controller->request->params['prefix']) && mb_strpos($this->Controller->request->params['action'], $this->Controller->request->params['prefix']) === 0) {
			$action = mb_substr($this->Controller->request->params['action'], mb_strlen($this->Controller->request->params['prefix']) + 1);
		} else {
			$action = $this->Controller->request->params['action'];
		}

		$url = array_merge($this->Controller->request->params['named'], $this->Controller->request->params['pass'], array('prefix' => isset($this->Controller->request->params['prefix'])?$this->Controller->request->params['prefix'] : null,
			'plugin' => $this->Controller->request->params['plugin'], 'action' => $action, 'controller' => $this->Controller->request->params['controller']));

		if ($asString === true) {
			return Router::url($url);
		}
		return $url;
	}


	### Controller Stuff ###

	/**
	 * Force login for a specific user id
	 * @see DirectAuthentication auth adapter
	 *
	 * @param array $data
	 * - id
	 * @return boolean Success
	 * 2012-11-05 ms
	 */
	public function manualLogin($id, $settings = array()) {
		$requestData = $this->Controller->request->data;
		$authData = $this->Controller->Auth->authenticate;
		$settings = array_merge($authData, $settings);
		$settings['fields'] = array('username' => 'id');

		$this->Controller->request->data = array('User' => array('id' => $id));
		$this->Controller->Auth->authenticate = array('Tools.Direct' => $settings);
		$result = $this->Controller->Auth->login();

		$this->Controller->Auth->authenticate = $authData;
		$this->Controller->request->data = $requestData;
		return $result;
	}

	/**
	 * Smart Referer Redirect - will try to use an existing referer first
	 * otherwise it will use the default url
	 *
	 * @param mixed $url
	 * @param bool $allowSelf if redirect to the same controller/action (url) is allowed
	 * @param int $status
	 * returns nothing and automatically redirects
	 * 2010-11-06 ms
	 */
	public function autoRedirect($whereTo, $allowSelf = true, $status = null) {
		if ($allowSelf || $this->Controller->referer(null, true) != '/' . $this->Controller->request->url) {
			$this->Controller->redirect($this->Controller->referer($whereTo, true), $status);
		}
		$this->Controller->redirect($whereTo, $status);
	}

	/**
	 * should be a 303, but:
	 * Note: Many pre-HTTP/1.1 user agents do not understand the 303 status. When interoperability with such clients is a concern, the 302 status code may be used instead, since most user agents react to a 302 response as described here for 303.
	 * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
	 * @param mixed $url
	 * @param int $status
	 * TODO: change to 303 with backwardscompatability for older browsers...
	 * 2011-06-14 ms
	 */
	public function postRedirect($whereTo, $status = 302) {
		$this->Controller->redirect($whereTo, $status);
	}

	/**
	 * combine auto with post
	 * also allows whitelisting certain actions for autoRedirect (use Controller::$autoRedirectActions)
	 * @param mixed $url
	 * @param bool $conditionalAutoRedirect false to skip whitelisting
	 * @param int $status
	 * 2012-03-17 ms
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
			# fixme
			if (!isset($this->Controller->autoRedirectActions)) {
				$this->Controller->autoRedirectActions = array();
			}
			foreach ($this->Controller->autoRedirectActions as $action) {
				list($controller, $action) = pluginSplit($action);
				if (!empty($controller) && $refererController != '*' && $refererController != $controller) {
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
	 * only redirect to itself if cookies are on
	 * prevents problems with lost data
	 * Note: Many pre-HTTP/1.1 user agents do not understand the 303 status. When interoperability with such clients is a concern, the 302 status code may be used instead, since most user agents react to a 302 response as described here for 303.
	 * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
	 * TODO: change to 303 with backwardscompatability for older browsers...
	 * 2011-08-10 ms
	 */
	public function prgRedirect($status = 302) {
		if (!empty($_COOKIE[Configure::read('Session.cookie')])) {
			$this->Controller->redirect('/'.$this->Controller->request->url, $status);
		}
	}

	/**
	 * Handler for passing some meta data to the view
	 * uses CommonHelper to include them in the layout
	 * @param type (relevance):
	 * - title (10), description (9), robots(7), language(5), keywords (0)
	 * - custom: abstract (1), category(1), GOOGLEBOT(0) ...
	 * 2010-12-29 ms
	 */
	public function setMeta($type, $content, $prep = true) {
		if (!in_array($type, array('title', 'canonical', 'description', 'keywords', 'robots', 'language', 'custom'))) {
			trigger_error(__('Meta Type invalid'), E_USER_WARNING);
			return;
		}
		if ($type == 'canonical' && $prep) {
			$content = Router::url($content);
		}
		if ($type == 'canonical' && $prep) {
			$content = h($content);
		}
		# custom: <meta name=”GOOGLEBOT” content=”unavailable_after: … GMT”>
		Configure::write('Meta.'.$type, $content);
	}


/*** Other helpers and debug features **/

	/**
	* Checks to see if there is a limit set for pagination results
	* to prevent overloading the database
	*
	* @param string $value
	* @return void
	* @author Jose Gonzalez (savant)
	* @deprecated (cake2.0 has it)
	*/
	protected function _paginationLimit() {
		if (isset($this->Controller->paginationMaxLimit)) {
			$this->paginationMaxLimit = $this->Controller->paginationMaxLimit;
		}
		if (isset($this->Controller->passedArgs['limit']) && is_numeric($this->paginationMaxLimit)) {
			$this->Controller->passedArgs['limit'] = min(
				$this->paginationMaxLimit,
				(int)$this->Controller->passedArgs['limit']
			);
		}
	}

	/**
	 * Generates validation error messages for HABTM fields
	 *
	 * @return void
	 * @author Dean
	 */
	protected function _habtmValidation() {
		$model = $this->Controller->modelClass;
		if (isset($this->Controller->{$model}) && isset($this->Controller->{$model}->hasAndBelongsToMany)) {
			foreach ($this->Controller->{$model}->hasAndBelongsToMany as $alias => $options) {
				if (isset($this->Controller->{$model}->validationErrors[$alias])) {
					$this->Controller->{$model}->{$alias}->validationErrors[$alias] = $this->Controller->{$model}->validationErrors[$alias];
				}
			}
		}
	}

	/**
	 * set headers to cache this request
	 * @param int $seconds
	 * @return void
	 * 2009-12-26 ms
	 */
	public function forceCache($seconds = HOUR) {
		header('Cache-Control: public, max-age='.$seconds);
		header('Last-modified: '.gmdate("D, j M Y H:i:s", time())." GMT");
		header('Expires: '.gmdate("D, j M Y H:i:s", time() + $seconds)." GMT");
	}


	/**
	 * referer checking (where does the user come from)
	 * 2009-12-19 ms
	 */
	public function isForeignReferer($ref = null) {
		if ($ref === null) {
			$ref = env('HTTP_REFERER');
		}
		$base = FULL_BASE_URL . $this->Controller->webroot;
		if (strpos($ref, $base) === 0) { // @ position 1 already the same
			return false;
		}
		return true;
	}


	public function denyAccess() {
		$ref = env('HTTP_USER_AGENT');
		if ($this->isForeignReferer($ref)) {
			if (eregi('http://Anonymouse.org/', $ref)) {
				//echo returns(Configure::read('Config.language'));
				$this->cakeError('error406', array());
			}
		}
	}

	public function monitorCookieProblems() {
		/*
		if (($language = Configure::read('Config.language')) === null) {
		//$this->log('CookieProblem: SID '.session_id().' | '.env('REMOTE_ADDR').' | Ref: '.env('HTTP_REFERER').' |Agent: '.env('HTTP_USER_AGENT'));
		}
		*/
		$ip = $this->RequestHandler->getClientIP(); //env('REMOTE_ADDR');
		$host = gethostbyaddr($ip);
		$sessionId = session_id();
		if (empty($sessionId)) {
			$sessionId = '--';
		}
		if (empty($_REQUEST[Configure::read('Session.cookie')]) && !($res = Cache::read($ip))) {
			$this->log('CookieProblem:: SID: '.$sessionId.' | IP: '.$ip.' ('.$host.') | REF: '.$this->Controller->referer().' | Agent: '.env('HTTP_USER_AGENT'), 'noscript');
			Cache::write($ip, 1);
		}
	}



	/**
	 * //todo: move to Utility?
	 *
	 * @return boolean true if disabled (bots, etc), false if enabled
	 * @static
	 * 2010-11-20 ms
	 */
	public static function cookiesDisabled() {
		if (!empty($_COOKIE) && !empty($_COOKIE[Configure::read('Session.cookie')])) {
			return false;
		}
		return true;
	}

	/**
	 * quick sql debug from controller dynamically
	 * or statically from just about any other place in the script
	 * @param bool $die: TRUE to output and die, FALSE to log to file and continue
	 * 2011-06-30 ms
	 */
	public function sql($die = true) {
		if (isset($this->Controller)) {
			$object = $this->Controller->{$this->Controller->modelClass};
		} else {
			$object = ClassRegistry::init(defined('CLASS_USER')?CLASS_USER:'User');
		}

		$log = $object->getDataSource()->getLog(false, false);
		foreach ($log['log'] as $key => $value) {
			if (strpos($value['query'], 'SHOW ') === 0 || strpos($value['query'], 'SELECT CHARACTER_SET_NAME ') === 0) {
				unset($log['log'][$key]);
				continue;
			}
		}
		# output and die?
		if ($die) {
			debug($log);
			die();
		}
		# log to file then and continue
		$log = print_r($log, true);
		App::uses('CakeLog', 'Log');
		CakeLog::write('sql', $log);
	}


	/**
	 * temporary check how often current cache fails!
	 * 2010-05-07 ms
	 */
	public function ensureCacheIsOk() {
		$x = Cache::read('xyz012345');
		if (!$x) {
			$x = Cache::write('xyz012345', 1);
			$this->log(date(FORMAT_DB_DATETIME), 'cacheprob');
			return false;
		}
		return true;
	}



	/**
	 * localize
	 * 2010-04-29 ms
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

		if (!((array)$pattern = Configure::read('LocalizationPattern.'.$lang))) {
			return false;
		}
		foreach ($pattern as $key => $value) {
			Configure::write('Localization.'.$key, $value);
		}
		return true;
	}

	/**
	 * bug fix for i18n
	 * 2010-01-01 ms
	 */
	public function ensureDefaultLanguage() {
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			//Configure::write('Config.language', DEFAULT_LANGUAGE);
		}
	}

	/**
	 * main controller function for consistency in controller naming
	 * 2009-12-19 ms
	 */
	public function ensureControllerConsistency() {
		# problems with plugins
		if (!empty($this->Controller->request->params['plugin'])) {
			return;
		}

		if (($name = strtolower(Inflector::underscore($this->Controller->name))) !== $this->Controller->request->params['controller']) {
			$this->Controller->log('301: '.$this->Controller->request->params['controller'].' => '.$name.' (Ref '.$this->Controller->referer().')', '301'); // log problem with controller naming
			if (!$this->Controller->RequestHandler->isPost()) {
				# underscored version is the only valid one to avoid duplicate content
				$url = array('controller' => $name, 'action' => $this->Controller->request->params['action']);
				$url = array_merge($url, $this->Controller->request->params['pass'], $this->Controller->request->params['named']);
				//TODO: add plugin/admin stuff which right now is supposed to work automatically
				$this->Controller->redirect($url, 301);
			}
		}

		/*
		pr(Router::url());
		pr($this->currentUrl());
		pr($this->currentUrl(true));
		pr($this->Controller->here);
		*/

		return true;
		# problem with extensions (rss etc)

		if (empty($this->Controller->request->params['prefix']) && ($currentUrl = $this->currentUrl(true)) != $this->Controller->here) {
			//pr($this->Controller->here);
			//pr($currentUrl);
			$this->log('301: '.$this->Controller->here.' => '.$currentUrl.' (Referer '.$this->Controller->referer().')', '301');

			if (!$this->Controller->RequestHandler->isPost()) {
				$url = array('controller' => $this->Controller->request->params['controller'], 'action' => $this->Controller->request->params['action']);
				$url = array_merge($url, $this->Controller->request->params['pass'], $this->Controller->request->params['named']);
				$this->Controller->redirect($url, 301);
			}
		}
	}

	/**
	 * main controller function for seo-slugs
	 * passed titleSlug != current title => redirect to the expected one
	 * 2009-07-31 ms
	 */
	public function ensureConsistency($id, $passedTitleSlug, $currentTitle) {
		$expectedTitle = slug($currentTitle);
		if (empty($passedTitleSlug) || $expectedTitle != $passedTitleSlug) { # case sensitive!!!
			$ref = env('HTTP_REFERER');
			if (!$this->isForeignReferer($ref)) {
				$this->Controller->log('Internal ConsistencyProblem at \''.$ref.'\' - ['.$passedTitleSlug.'] instead of ['.$expectedTitle.']', 'referer');
			} else {
				$this->Controller->log('External ConsistencyProblem at \''.$ref.'\' - ['.$passedTitleSlug.'] instead of ['.$expectedTitle.']', 'referer');
			}
			$this->Controller->redirect(array($id, $expectedTitle), 301);
		}
	}





/*** deprecated ***/

	/**
	 * add protocol prefix if necessary (and possible)
	 * static?
	 * 2010-06-02 ms
	 */
	public function autoPrefixUrl($url, $prefix = null) {
		return Utility::autoPrefixUrl($url, $prefix);
	}


	/**
	 * remove unnessary stuff + add http:// for external urls
	 * TODO: protocol to lower!
	 * @static
	 * 2009-12-22 ms
	 */
	public static function cleanUrl($url, $headerRedirect = false) {
		return Utility::cleanUrl($url, $headerRedirect);
	}

	/**
	 * @static
	 * 2009-12-26 ms
	 */
	public static function getHeaderFromUrl($url) {
		return Utility::getHeaderFromUrl($url);
	}


	/**
	 * get the current ip address
	 * @param bool $safe
	 * @return string $ip
	 * 2011-11-02 ms
	 */
	public static function getClientIp($safe = null) {
		return Utility::getClientIp($safe);
	}

	/**
	 * get the current referer
	 * @param bool $full (defaults to false and leaves the url untouched)
	 * @return string $referer (local or foreign)
	 * 2011-11-02 ms
	 */
	public static function getReferer($full = false) {
		return Utility::getReferer($full);
	}

	/**
	 * returns true only if all values are true
	 * @return bool $result
	 * maybe move to bootstrap?
	 * 2011-11-02 ms
	 */
	public static function logicalAnd($array) {
		return Utility::logicalAnd($array);
	}

	/**
	 * returns true if at least one value is true
	 * @return bool $result
	 * maybe move to bootstrap?
	 * 2011-11-02 ms
	 */
	public static function logicalOr($array) {
		return Utility::logicalOr($array);
	}

	/**
	 * convinience function for automatic casting in form methods etc
	 * @return safe value for DB query, or NULL if type was not a valid one
	 * @static
	 * maybe move to bootstrap?
	 * 2008-12-12 ms
	 */
	public static function typeCast($type = null, $value = null) {
		return Utility::typeCast($type, $value);
	}

	/**
	 * try to get group for a multidim array for select boxes
	 * @param array $array
	 * @param string $result
	 * 2011-03-12 ms
	 */
	public function getGroup($multiDimArray, $key, $matching = array()) {
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


	/*** Time Stuff ***/

	/**
	 * for month and year it returns the amount of days of this month
	 * year is necessary due to leap years!
	 * @param int $year
	 * @param int $month
	 * @static
	 * TODO: move to TimeLib etc
	 * 2009-12-26 ms
	 */
	public function daysInMonth($year, $month) {
		trigger_error('deprecated - use Tools.TimeLib instead');
		App::uses('TimeLib', 'Tools.Utility');
		return TimeLib::daysInMonth($year, $month);
	}


	/*** DEEP FUNCTIONS ***/

	/**
	 * @static?
	 * move to boostrap?
	 * 2009-07-07 ms
	 */
	public function trimDeep($value) {
		$value = is_array($value) ? array_map(array($this, 'trimDeep'), $value) : trim($value);
		return $value;
	}

	/**
	 * @static?
	 * move to boostrap?
	 * 2009-07-07 ms
	 */
	public function specialcharsDeep($value) {
		$value = is_array($value) ? array_map(array($this, 'specialcharsDeep'), $value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		return $value;
	}

	/**
	 * @static?
	 * move to boostrap?
	 * 2009-07-07 ms
	 */
	public function deep($function, $value) {
		$value = is_array($value) ? array_map(array($this, $function), $value) : $function($value);
		return $value;
	}


	/**
	 * MAIN Sanitize Array-FUNCTION
	 * @param string $type: html, paranoid
	 * move to boostrap?
	 * 2008-11-06 ms
	 */
	public function sanitizeDeep($value, $type = null, $options = null) {
		switch ($type) {
			case 'html':
				if (isset($options['remove']) && is_bool($options['remove'])) {
					$this->removeChars = $options['remove'];
				}
				$value = $this->htmlDeep($value);
				break;
			case 'paranoid':
			default:
				if (isset($options['allowed']) && is_array($options['allowed'])) {
					$this->allowedChars = $options['allowed'];
				}
				$value = $this->paranoidDeep($value);
		}
		return $value;
	}

	/**
	 * removes all except A-Z,a-z,0-9 and allowedChars (allowedChars array)
	 * move to boostrap?
	 * 2009-07-07 ms
	 */
	public function paranoidDeep($value) {
		$mrClean = new Sanitize();
		$value = is_array($value)?array_map(array($this, 'paranoidDeep'), $value) : $mrClean->paranoid($value, $this->allowedChars);
		return $value;
	}

	/**
	 * transfers/removes all < > from text (remove TRUE/FALSE)
	 * move to boostrap?
	 * 2009-07-07 ms
	 */
	public function htmlDeep($value) {
		$mrClean = new Sanitize();
		$value = is_array($value)?array_map(array($this, 'htmlDeep'), $value) : $mrClean->html($value, $this->removeChars);
		return $value;
	}


	/*** Filtering Stuff ***/

	/**
	 * get the rounded average
	 * @param array $values: int or float values
	 * @return int $average
	 * @static
	 * move to lib
	 * 2009-09-05 ms
	 */
	public static function average($values, $precision = 0) {
		trigger_error('deprecated - use Tools.NumberLib instead');
		App::uses('NumberLib', 'Tools.Utility');
		return NumberLib::average($values, $precision);
	}


	/**
	 * @deprecated: use TextLib
	 * //TODO use str_word_count() instead!!!
	 * @return int
	 * @static
	 * 2009-11-11 ms
	 */
	public static function numberOfWords($text) {
		$count = 0;
		$words = explode(' ', $text);
		foreach ($words as $word) {
			$word = trim($word);
			if (!empty($word)) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * @deprecated: use TextLib
	 * //TODO: dont count spaces, otherwise we could use mb_strlen() right away!
	 * @return int
	 * @static
	 * 2009-11-11 ms
	 */
	public function numberOfChars($text) {
		return mb_strlen($text);
	}

	/**
	 * takes list of items and transforms it into an array
	 * + cleaning (trim, no empty parts, etc)
	 * @param string $string containing the parts
	 * @param string $separator (defaults to ',')
	 * @param boolean $camelize (true/false): problems with äöüß etc!
	 *
	 * @return array $results as array list
	 * @static
	 * //TODO: 3.4. parameter as array, move to Lib
	 * 2009-08-13 ms
	 */
	public function parseList($string, $separator = null, $camelize = false, $capitalize = true) {
		if (empty($separator)) {
			$separator = ',';
		}

		# parses the list, but leaves tokens untouched inside () brackets
		$string_array = String::tokenize($string, $separator); //explode($separator, $string);
		$return_array = array();

		if (empty($string_array)) {
			return array();
		}

		foreach ($string_array as $t) {
			$t = trim($t);
			if (!empty($t)) {

				if ($camelize === true) {
					$t = mb_strtolower($t);
					$t = Inflector::camelize(Inflector::underscore($t)); # problems with non-alpha chars!!
				} elseif ($capitalize === true) {
					$t = ucwords($t);
				}
				$return_array[] = $t;
			}
		}

		return $return_array;
	}


	/**
	 * //todo move to lib!!!
	 * static
	 * 2009-12-21 ms
	 */
	public function separators($s = null, $valueOnly = false) {
		$separatorsValues = array(SEPARATOR_COMMA => ',', SEPARATOR_SEMI => ';', SEPARATOR_SPACE => ' ', SEPARATOR_TAB => TB, SEPARATOR_NL => NL);

		$separators = array(SEPARATOR_COMMA => '[ , ] '.__('Comma'), SEPARATOR_SEMI => '[ ; ] '.__('Semicolon'), SEPARATOR_SPACE => '[ &nbsp; ] '.__('Space'), SEPARATOR_TAB =>
			'[ &nbsp;&nbsp;&nbsp;&nbsp; ] '.__('Tabulator'), SEPARATOR_NL => '[ \n ] '.__('New Line'));

		if ($s !== null) {
			if (array_key_exists($s, $separators)) {
				if ($valueOnly) {
					return $separatorsValues[$s];
				}
				return $separators[$s];
			} else {
				return '';
			}
		}
		return $valueOnly?$separatorsValues : $separators;
	}


	/**
	 * //TODO: move somewhere else
	 * Returns an array with chars
	 * up = uppercase, low = lowercase
	 * @var char type: NULL/up/down | default: NULL (= down)
	 * @return array with the a-z
	 *
	 * @deprecated: USE range() instead! move to lib
	 */
	public function alphaFilterSymbols($type = null) {
		$arr = array();
		for ($i = 97; $i < 123; $i++) {
			if ($type == 'up') {
				$arr[] = chr($i - 32);
			} else {
				$arr[] = chr($i);
			}
		}
		return $arr;
	}

	/**
	 * returns the current server GMT offset (+/- 1..12)
	 * TODO: move to DateLib etc
	 * @static
	 * 2009-12-26 ms
	 */
	public static function gmtOffset() {
		$gmt = mktime(gmdate("H"), gmdate("i"), gmdate("s"), gmdate("m"), gmdate("d"), gmdate("Y"));
		$gmtOffset = mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"));
		//pr ($gmt); pr ($gmtOffset);
		$timeOffset = ($gmtOffset - $gmt) / 3600;
		return $timeOffset;
	}

	/**
	 * TODO: move to DateLib etc
	 */
	public function timeStuff() {
		$timeOffset = $this->gmtOffset();
		Configure::write('Localization.server_time_offset', $timeOffset);
		Configure::write('Localization.daylight_savings', date('I'));

		$userOffset = Configure::read('Localization.user_time_offset');
		$sessionOffset = $this->Session->read('Localization.user_time_offset');
		if ($sessionOffset != null) {
			$this->userOffset($sessionOffset);
		}
	}

	/**
	 * TODO: move to DateLib etc
	 * @static
	 * 2009-12-26 ms
	 */
	public static function userOffset($timeOffset) {
		Configure::write('Localization.user_time_offset', $timeOffset);
	}


	/**
	 * //TODO: move somewhere else
	 * Assign Array to Char Array
	 *
	 * @var content array
	 * @var char array
	 * @return array: chars with content
	 * @static
	 * PROTECTED NAMES (content cannot contain those): undefined
	 * 2009-12-26 ms
	 */
	public function assignToChar($content_array, $char_array = null) {
		$res = array();
		$res['undefined'] = array();

		if (empty($char_array)) {
			$char_array = $this->alphaFilterSymbols();
		}

		foreach ($content_array as $content) {
			$done = false;

			# loop them trough
			foreach ($char_array as $char) {
				if (empty($res[$char])) { // throws warnings otherwise
					$res[$char] = array();
				}
				if (!empty($content) && strtolower(substr($content, 0, 1)) == $char) {
					$res[$char][] = $content;
					$done = true;
				}
			}

			# no match?
			if (!empty($content) && !$done) {
				$res['undefined'][] = $content;
			}

		}

		/*
		//this way does not work:

		foreach ($char_array as $char) {
		$res[$char]=array();
		$done = false;

		foreach ($content_array as $content) {
		if (!empty($content) && strtolower(substr($content,0,1)) == $char) {
		$res[$char][]=$content;
		$done = true;
		}
		}

		# no match?
		if (!empty($content) && !$done) {
		echo $content;
		$res['undefined'][]=$content;
		}

		}
		*/
		return $res;
	}




	/**
	 * @deprecated
	 * use splitEmail instead
	 */
	public function extractEmail($email) {
		trigger_error('deprecated - use splitEmail');
		if (($pos = mb_strpos($email, '<')) !== false) {
			$email = substr($email, $pos+1);
		}
		if (($pos = mb_strrpos($email, '>')) !== false) {
			$email = substr($email, 0, $pos);
		}
		$email = trim($email);
		return $email;

		//CommonComponent::splitEmail($email);
	}

	/**
	 * expects email to be valid!
	 * TODO: move to Lib
	 * @return array $email - pattern: array('email'=>,'name'=>)
	 * 2010-04-20 ms
	 */
	public function splitEmail($email, $abortOnError = false) {
		$array = array('email'=>'', 'name'=>'');
		if (($pos = mb_strpos($email, '<')) !== false) {
			$name = substr($email, 0, $pos);
			$email = substr($email, $pos+1);
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
		return $name.' <'.$email['email'].'>';
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
	 * @returns string or false on failure
	 * 2010-01-10 ms
	 */
	public function extractEmailInfo($email, $type = null) {
		//$checkpos = strrpos($email, '@');
		$nameParts = explode('@', $email);
		if (count($nameParts) !== 2) {
			return false;
		}

		if ($type == 'username') {
			return $nameParts[0];
		} elseif ($type == 'hostname') {
			return $nameParts[1];
		}

		$checkpos = strrpos($nameParts[1], '.');
		$tld = trim(mb_substr($nameParts[1], $checkpos + 1));

		if ($type == 'tld') {
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

		if ($type == 'domain') {
			return $domain;
		}
		if ($type == 'subdomain') {
			return $subdomain;
		}

		//$hostParts = explode();
		//$check = trim(mb_substr($email, $checkpos));
		return '';
	}

	/**
	 * TODO: move to SearchLib etc
	 * Returns searchArray (options['wildcard'] TRUE/FALSE)
	 *
	 * @return ARRAY cleaned array('keyword'=>'searchphrase') or array('keyword LIKE'=>'searchphrase')
	 * @access public
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
	 * returns auto-generated password
	 * @param string $type: user, ...
	 * @param int $length (if no type is submitted)
	 * @return pwd on success, empty string otherwise
	 * @static
	 * @deprecated - use RandomLib
	 * 2009-12-26 ms
	 */
	public static function pwd($type = null, $length = null) {
		App::uses('RandomLib', 'Tools.Lib');
		if (!empty($type) && $type == 'user') {
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
	 * @return true if at least one @ is in the string, false otherwise
	 * @static
	 * 2009-12-26 ms
	 */
	public function containsAtSign($string = null) {
		if (!empty($string) && strpos($string, '@') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * @deprecated - use IpLip instead!
	 * IPv4/6 to slugged ip
	 * 192.111.111.111 => 192-111-111-111
	 * 4C00:0207:01E6:3152 => 4C00+0207+01E6+3152
	 * @return string sluggedIp
	 * 2010-06-19 ms
	 */
	public function slugIp($ip) {
		//$ip = Inflector::slug($ip);
		$ip = str_replace(array(':', '.'), array('+', '-'), $ip);
		return $ip;
	}

	/**
	 * @deprecated - use IpLip instead!
	 * @return string ip on success, FALSE on failure
	 * 2010-06-19 ms
	 */
	public function unslugIp($ip) {
		//$format = self::ipFormat($ip);
		$ip = str_replace(array('+', '-'), array(':', '.'), $ip);
		return $ip;
	}

	/**
	 * @deprecated - use IpLip instead!
	 * @return string v4/v6 or FALSE on failure
	 */
	public function ipFormat($ip) {
		if (Validation::ip($ip, 'ipv4')) {
			return 'ipv4';
		}
		if (Validation::ip($ip, 'ipv6')) {
			return 'ipv6';
		}
		return false;
	}


	/**
	 * Get the Corresponding Message to an HTTP Error Code
	 * @param int $code: 100...505
	 * @return array $codes if code is NULL, otherwise string $code (empty string on failure)
	 * 2009-07-21 ms
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
		# RFC 2616 states that all unknown HTTP codes must be treated the same as the
		# base code in their class.
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
	 * @param int $code: 4xx...5xx
	 * 2010-06-08 ms
	 */
	public function smtpResponseCodes($code = null, $autoTranslate = false) {
		# 550 5.1.1 User is unknown
		# 552 5.2.2 Storage Exceeded
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


	/**
	 * isnt this covered by core Set stuff anyway?)
	 *
	 * tryout: sorting multidim. array by field [0]..[x]; z.b. $array['Model']['name'] DESC etc.
	 */
	public function sortArray($array, $obj, $direction = null) {
		if (empty($direction) || empty($array) || empty($obj)) {
			return array();
		}
		if ($direction == 'up') {
			usort($products, array($obj, 'sortUp'));
		}
		if ($direction == 'down') {
			usort($products, array($obj, 'sortDown'));
		}
		return array();
	}

	public function sortUp($x, $y) {
		if ($x[1] == $y[1]) {
			return 0;
		} elseif ($x[1] < $y[1]) {
			return 1;
		}
		return - 1;
	}

	public function sortDown($x, $y) {
		if ($x[1] == $y[1]) {
			return 0;
		} elseif ($x[1] < $y[1]) {
			return - 1;
		}
		return 1;
	}


}

