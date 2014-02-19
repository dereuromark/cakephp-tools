<?php
App::uses('Component', 'Controller');

/**
 * AutoLoginComponent
 *
 * A CakePHP Component that will automatically login the Auth session for a duration if the user requested to (saves data to cookies).
 *
 * @author		Miles Johnson - http://milesj.me
 * @copyright	Copyright 2006-2011, Miles Johnson, Inc.
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link		http://milesj.me/code/cakephp/auto-login
 *
 * @modified 	Mark Scherer - 2012-01-08 ms
 * - now works with Controller::beforeFilter() modifications to allow username/email login switch
 * - can be disabled dynamically and will skip on CakeError view
 */
class AutoLoginComponent extends Component {

	/**
	 * Current version.
	 *
	 * @var string
	 */
	public $version = '3.5';

	/**
	 * Components.
	 *
	 * @var array
	 */
	public $components = array('Auth', 'Cookie');

	/**
	 * Settings.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	protected $_defaults = array(
		'active' => true,
		'model' => 'User',
		'username' => 'username',
		'password' => 'password',
		'plugin' => '',
		'controller' => 'users',
		'loginAction' => 'login',
		'logoutAction' => 'logout',
		'cookieName' => 'autoLogin',
		'expires' => '+2 weeks', # Cookie length (strtotime() format)
		'redirect' => true,
		'requirePrompt' => true, # Displayed checkbox determines if cookie is created
		'debug' => null # Auto-Select based on debug mode or ip range
	);

	/**
	 * Determines whether to trigger startup() logic.
	 *
	 * @var boolean
	 */
	protected $_isValidRequest = false;

	/**
	 * Initialize settings and debug.
	 *
	 * @param ComponentCollection $collection
	 * @param array $settings
	 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$defaultSettings = array_merge($this->_defaults, (array)Configure::read('AutoLogin'));
		$settings = array_merge($defaultSettings, $settings);

		// make sure an upgrade does reset all cookies stored to avoid conflicts
		$settings['cookieName'] = $settings['cookieName'] . str_replace('.', '', $this->version);
		$this->settings = $settings;
		parent::__construct($collection, $settings);
	}

	/**
	 * Detect debug info.
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function initialize(Controller $controller) {
		if ($controller->name === 'CakeError' || !$this->settings['active']) {
			return;
		}

		// Validate the cookie
		$cookie = $this->_readCookie();
		$user = $this->Auth->user();

		if (!empty($user) || !$cookie || !$controller->request->is('get')) {
			return;
		}

		// Is debug enabled
		if ($this->settings['debug'] === null) {
			$this->settings['debug'] = Configure::read('debug') > 0 || !empty($this->settings['ips']) && in_array(env('REMOTE_ADDR'), (array)$this->settings['ips']);
		}

		if (empty($cookie['hash']) || $cookie['hash'] != $this->Auth->password($cookie['username'] . $cookie['time'])) {
			$this->debug('hashFail', $cookie, $user);
			$this->delete();
			return;
		}

		// Set the data to identify with
		$controller->request->data[$this->settings['model']][$this->settings['username']] = $cookie['username'];
		$controller->request->data[$this->settings['model']][$this->settings['password']] = $cookie['password'];

		// Request is valid, stop startup()
		$this->_isValidRequest = true;
	}

	/**
	 * Automatically login existent Auth session; called after controllers beforeFilter() so that Auth is initialized.
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function startup(Controller $controller) {
		if (!$this->_isValidRequest) {
			return;
		}

		if ($this->Auth->login()) {
			$this->debug('login', $this->Cookie, $this->Auth->user());

			if (in_array('_autoLogin', get_class_methods($controller))) {
				call_user_func_array(array($controller, '_autoLogin'), array(
					$this->Auth->user()
				));
			}
			if ($this->settings['redirect']) {
				$controller->redirect(array(), 301);
			}

		} else {
			$this->debug('loginFail', $this->Cookie, $this->Auth->user());

			if (in_array('_autoLoginError', get_class_methods($controller))) {
				call_user_func_array(array($controller, '_autoLoginError'), array(
					$this->_readCookie()
				));
			}
		}
	}

	/**
	 * Automatically process logic when hitting login/logout actions.
	 *
	 * @param Controller $controller
	 * @return void
	 */
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {
		if (!$this->settings['active']) {
			return;
		}
		$model = $this->settings['model'];

		if (is_array($this->Auth->loginAction)) {
			if (!empty($this->Auth->loginAction['controller'])) {
				$this->settings['controller'] = $this->Auth->loginAction['controller'];
			}

			if (!empty($this->Auth->loginAction['action'])) {
				$this->settings['loginAction'] = $this->Auth->loginAction['action'];
			}

			if (!empty($this->Auth->loginAction['plugin'])) {
				$this->settings['plugin'] = $this->Auth->loginAction['plugin'];
			}
		}

		if (empty($this->settings['controller'])) {
			$this->settings['controller'] = Inflector::pluralize($model);
		}

		// Is called after user login/logout validates, but before auth redirects
		if ($controller->plugin == Inflector::camelize($this->settings['plugin']) && $controller->name == Inflector::camelize($this->settings['controller'])) {
			$data = $controller->request->data;
			$action = isset($controller->request->params['action']) ? $controller->request->params['action'] : 'login';

			switch ($action) {
				case $this->settings['loginAction']:
					if (isset($data[$model])) {
						$username = $data[$model][$this->settings['username']];
						$password = $data[$model][$this->settings['password']];
						$autoLogin = isset($data[$model]['auto_login']) ? $data[$model]['auto_login'] : !$this->settings['requirePrompt'];

						if (!empty($username) && !empty($password) && $autoLogin) {
							$this->_writeCookie($username, $password);

						} elseif (!$autoLogin) {
							$this->delete();
						}
					}
					break;

				case $this->settings['logoutAction']:
					$this->debug('logout', $this->Cookie, $this->Auth->user());
					$this->delete();
					break;
			}
		}
	}

	/**
	 * Delete the cookie.
	 *
	 * @return void
	 */
	public function delete() {
		$this->Cookie->delete($this->settings['cookieName']);
	}

	/**
	 * Debug the current auth and cookies.
	 *
	 * @param string $key
	 * @param array $cookie
	 * @param array $user
	 * @return void
	 */
	public function debug($key, $cookie = array(), $user = array()) {
		$scopes = array(
			'login'				=> 'Login Successful',
			'loginFail'			=> 'Login Failure',
			'loginCallback'		=> 'Login Callback',
			'logout'			=> 'Logout',
			'logoutCallback'	=> 'Logout Callback',
			'cookieSet'			=> 'Cookie Set',
			'cookieFail'		=> 'Cookie Mismatch',
			'hashFail'			=> 'Hash Mismatch',
			'custom'			=> 'Custom Callback'
		);

		if ($this->settings['debug'] && isset($scopes[$key])) {
			$debug = (array)Configure::read('AutoLogin');
			$content = '';

			if (!empty($cookie) || !empty($user)) {
				if (!empty($cookie)) {
					$content .= "Cookie information: \n\n" . print_r($cookie, true) . "\n\n\n";
				}

				if (!empty($user)) {
					$content .= "User information: \n\n" . print_r($user, true);
				}
			} else {
				$content = 'No debug information.';
			}

			if (empty($debug['scope']) || in_array($key, (array)$debug['scope'])) {
				if (!empty($debug['email'])) {
					mail($debug['email'], '[AutoLogin] ' . $scopes[$key], $content, 'From: ' . $debug['email']);
				} else {
					$this->log($scopes[$key] . ': ' . $content, 'autologin');
				}
			}
		}
	}

	/**
	 * Remember the user information and store it in a cookie (encrypted).
	 *
	 * @param string $username
	 * @param string $password
	 * @return void
	 */
	protected function _writeCookie($username, $password) {
		$time = time();

		$cookie = array();
		$cookie['username'] = base64_encode($username);
		$cookie['password'] = base64_encode($password);
		$cookie['hash'] = $this->Auth->password($username . $time);
		$cookie['time'] = $time;

		if (env('REMOTE_ADDR') === '127.0.0.1' || env('HTTP_HOST') === 'localhost') {
			$this->Cookie->domain = false;
		}

		$this->Cookie->write($this->settings['cookieName'], $cookie, true, $this->settings['expires']);
		$this->debug('cookieSet', $cookie, $this->Auth->user());
	}

	/**
	 * Read cookie and decode it
	 *
	 * @return mixed array $cookieData or false on failure
	 */
	protected function _readCookie() {
		$cookie = $this->Cookie->read($this->settings['cookieName']);
		if (empty($cookie) || !is_array($cookie)) {
			return false;
		}
		if (isset($cookie['username'])) {
			$cookie['username'] = base64_decode($cookie['username']);
		}
		if (isset($cookie['password'])) {
			$cookie['password'] = base64_decode($cookie['password']);
		}
		return $cookie;
	}

}
