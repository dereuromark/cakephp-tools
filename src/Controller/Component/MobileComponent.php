<?php

namespace Tools\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Routing\Router;
use RuntimeException;

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
	public $isMobile;

	/**
	 * Stores the final detection result including user preference.
	 *
	 * @var bool|null
	 */
	public $setMobile;

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
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		if ($this->_config['on'] !== 'initialize') {
			return;
		}
		$this->_init();
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event) {
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
		$controller = $this->getController();
		$mobileOverwrite = $controller->getRequest()->getQuery('mobile');

		if ($mobileOverwrite !== null) {
			if ($mobileOverwrite === '-1') {
				$controller->getRequest()->getSession()->delete('User.mobile');
			} else {
				$wantsMobile = (bool)$mobileOverwrite;
				$controller->getRequest()->getSession()->write('User.mobile', (int)$wantsMobile);
			}
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
		$forceMobile = $this->getController()->getRequest()->getSession()->read('User.mobile');

		if ($forceMobile !== null && !$forceMobile) {
			$this->setMobile = false;
		} elseif ($forceMobile !== null && $forceMobile || $this->isMobile()) {
			$this->setMobile = true;
		} else {
			$this->setMobile = false;
		}

		$urlParams = $this->getController()->getRequest()->getAttribute('params');
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
			$this->getController()->set('desktopUrl', $url);
		} else {
			$urlParams['?']['mobile'] = 1;
			$url = Router::url($urlParams);
			$this->getController()->set('mobileUrl', $url);
		}

		Configure::write('User.setMobile', (int)$this->setMobile);

		if (!$this->setMobile) {
			return;
		}

		$this->getController()->viewBuilder()->setClassName('Theme');
		$this->getController()->viewBuilder()->setTheme('Mobile');
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
	 * @throws \RuntimeException
	 */
	public function detect() {
		// Deprecated - the vendor libs are far more accurate and up to date
		if (!$this->_config['engine']) {
			if (isset($this->getController()->RequestHandler)) {
				return $this->getController()->getRequest()->is('mobile') || $this->getController()->RequestHandler->accepts('wap');
			}
			return $this->getController()->getRequest()->is('mobile');
		}
		if (is_callable($this->_config['engine'])) {
			return call_user_func($this->_config['engine']);
		}
		throw new RuntimeException(sprintf('Engine %s not available', $this->_config['engine']));
	}

}
