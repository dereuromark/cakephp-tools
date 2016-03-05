<?php

namespace Tools\Controller\Component;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Routing\Router;
use Shim\Controller\Component\Component;

/**
 * A component to easily store mobile in session and serve mobile views to users.
 * It allows good default values while not being restrictive as you can always
 * overwrite the auto-detection manually to force desktop or mobile version.
 *
 * Uses object attributes as well as Configure to store the results for later use.
 *
 * Don't foget to set up your mobile detectors in your bootstrap.
 *
 * Uses Configure to cache lookups in request: User.isMobile and User.setMobile
 * - isMobile is the auto-detection (true/false)
 * - setMobile can be set by the user and overrides the default behavior/detection
 *   (1=true/0=false or -1=null which will remove the override)
 *
 * The overwrite of a user is stored in the session: User.mobile.
 * It overwrites the Configure value.
 *
 * It also pushes switch urls to the view.
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class MobileComponent extends Component {

	/**
	 * Stores the result of the auto-detection.
	 *
	 * @var bool|null
	 */
	public $isMobile = null;

	/**
	 * Stores the final detection result including user preference.
	 *
	 * @var bool|null
	 */
	public $setMobile = null;

	/**
	 * Default values. Can also be set using Configure.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'on' => 'beforeFilter', // initialize (prior to controller's beforeRender) or startup
		'engine' => null, // CakePHP internal if null
		'themed' => false, // If false uses subfolders instead of themes: /View/.../mobile/
		'auto' => false, // auto set mobile views
	];

	/**
	 * MobileComponent::initialize()
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config) {
		parent::initialize($config);

		if ($this->_config['on'] !== 'initialize') {
			return;
		}
		$this->_init();
	}

	/**
	 * MobileComponent::startup()
	 *
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		if ($this->_config['on'] !== 'beforeFilter') {
			return;
		}
		$this->_init();
	}

	/**
	 * Main auto-detection logic including session based storage to avoid
	 * multiple lookups.
	 *
	 * Uses "mobile" query string to overwrite the auto-detection.
	 * -1 clears the fixation
	 * 1 forces mobile
	 * 0 forces no-mobile
	 *
	 * @return void
	 */
	protected function _init() {
		$mobileOverwrite = $this->Controller->request->query('mobile');

		if ($mobileOverwrite !== null) {
			if ($mobileOverwrite === '-1') {
				$noMobile = null;
			} else {
				$wantsMobile = (bool)$mobileOverwrite;
			}
			$this->request->session()->write('User.mobile', (int)$wantsMobile);
		}
		$this->isMobile();

		if (!$this->_config['auto']) {
			return;
		}
		$this->setMobile();
	}

	/**
	 * Sets mobile views as `Mobile` theme.
	 *
	 * Only needs to be called if auto is set to false.
	 * Then you probably want to call this from your AppController::beforeRender().
	 *
	 * @return void
	 */
	public function setMobile() {
		if ($this->isMobile === null) {
			$this->isMobile();
		}
		$forceMobile = $this->request->session()->read('User.mobile');

		if ($forceMobile !== null && !$forceMobile) {
			$this->setMobile = false;
		} elseif ($forceMobile !== null && $forceMobile || $this->isMobile()) {
			$this->setMobile = true;
		} else {
			$this->setMobile = false;
		}

		//$urlParams = Router::getParams(true);
		$urlParams = [];
		if (!isset($urlParams['pass'])) {
			$urlParams['pass'] = [];
		}
		$urlParams = array_merge($urlParams, $urlParams['pass']);
		unset($urlParams['pass']);
		if (isset($urlParams['prefix'])) {
			unset($urlParams['prefix']);
		}

		if ($this->setMobile) {
			$urlParams['?']['mobile'] = 0;
			$url = Router::url($urlParams);
			$this->Controller->set('desktopUrl', $url);
		} else {
			$urlParams['?']['mobile'] = 1;
			$url = Router::url($urlParams);
			$this->Controller->set('mobileUrl', $url);
		}

		Configure::write('User.setMobile', (int)$this->setMobile);

		if (!$this->setMobile) {
			return;
		}

		$this->Controller->viewBuilder()->className('Theme');
		$this->Controller->viewBuilder()->theme('Mobile');
	}

	/**
	 * Determines if we need to so serve mobile views based on session preference
	 * and browser headers.
	 *
	 * @return bool Success
	 */
	public function isMobile() {
		if ($this->isMobile !== null) {
			return $this->isMobile;
		}

		$this->isMobile = Configure::read('User.isMobile');
		if ($this->isMobile !== null) {
			return $this->isMobile;
		}
		$this->isMobile = (bool)$this->detect();

		Configure::write('User.isMobile', (int)$this->isMobile);
		return $this->isMobile;
	}

	/**
	 * Detects if the current request is from a mobile device.
	 *
	 * Note that the cake internal way might soon be deprecated:
	 * https://github.com/cakephp/cakephp/issues/2546
	 *
	 * @return bool Success
	 */
	public function detect() {
		// Deprecated - the vendor libs are far more accurate and up to date
		if (!$this->_config['engine']) {
			if (isset($this->Controller->RequestHandler)) {
				return $this->Controller->RequestHandler->isMobile();
			}
			return $this->Controller->request->is('mobile');
		}
		if (is_callable($this->_config['engine'])) {
			return call_user_func($this->_config['engine']);
		}
		throw new CakeException(sprintf('Engine %s not available', $this->_config['engine']));
	}

}
