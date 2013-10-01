<?php

App::uses('Component', 'Controller');
App::uses('Router', 'Routing');

/**
 * Uses Session: User.mobile and User.nomobile
 * - mobile is the auto-detection (true/false)
 * - nomobile can be set by the user and overrides the default behavior/detection
 *   (1=true/0=false or -1=null which will remove the override)
 *
 * TODO: differentaite between "isMobile" and "has/wants mobile"
 * @author Mark Scherer
 * @license MIT
 */
class MobileComponent extends Component {

	public $components = array('Session');

	public $isMobile = null;

	public $setMobile = null;

	public $Controller = null;

	protected $_defaults = array(
		'engine' => 'cake',
		'auto' => false, // auto set mobile views
	);

	/**
	 * If false uses subfolders: /View/.../mobile/
	 */
	public $themed = true;

	public function __construct(ComponentCollection $collection, $settings = array()) {
		$settings = array_merge($this->_defaults, $settings);
		parent::__construct($collection, $settings);
	}

	public function initialize(Controller $Controller) {
		parent::initialize($Controller);
		$this->Controller = $Controller;

		if (isset($this->Controller->request->params['named']['mobile'])) {
			if ($this->Controller->request->params['named']['mobile'] === '-1') {
				$noMobile = null;
			} else {
				$wantsMobile = (bool)$this->Controller->request->params['named']['mobile'];
				$noMobile = (int)(!$wantsMobile);
			}
			$this->Session->write('User.nomobile', $noMobile);

		}
		$this->isMobile();
	}

	/**
	 * Serve mobile views if available
	 *
	 * can be called from beforeFilter() to automatically serve an alternative mobile view
	 * if the file exists. If it doesn't exist in `/View/[ViewPath]/mobile/` the normal one
	 * will be used.
	 *
	 * @deprecated in favor of themed solution?
	 * @return void
	 */
	public function serveMobileIfAvailable() {
		$viewDir = App::path('View');
		// returns an array
		/*
		* array(
		*      (int) 0 => '/var/www/maps-cakephp2/app/View/'
		* )
		*/
		$mobileViewFile = $viewDir[0] . $this->viewPath . DS . 'Mobile' . DS . $this->params['action'] . '.ctp';

		//Debugger::log($this->viewPath);
		// use this to log the output to
		// app/tmp/logs/debug.log

		if (file_exists($mobileViewFile)) {
			// if device is mobile, change layout to mobile
			// but only if a view exists for it.
			$this->layout = 'mobile';
			// and if a mobile view file has been
			// created for the action, serve it instead
			// of the default view file
			$this->viewPath = $this->viewPath . '/Mobile/';
		}
	}

	/**
	 * Set mobile views as `Mobile` theme
	 *
	 * @return void
	 */
	public function setMobile() {
		if ($this->isMobile === null) {
			$this->isMobile();
		}
		$noMobile = $this->Session->read('User.nomobile');
		if (!$this->isMobile && $noMobile === null || $noMobile) {
			$this->setMobile = false;
		} else {
			$this->setMobile = true;
		}

		$urlParams = Router::getParams(true);
		if (!isset($urlParams['named'])) {
			$urlParams['named'] = array();
		}
		if (!isset($urlParams['pass'])) {
			$urlParams['pass'] = array();
		}
		$urlParams = array_merge($urlParams, $urlParams['named'], $urlParams['pass']);
		unset($urlParams['named']);
		unset($urlParams['pass']);
		if (isset($urlParams['prefix'])) {
			unset($urlParams['prefix']);
		}

		if ($this->setMobile) {
			$url = Router::url(array_merge($urlParams, array('mobile' => 0)));
			$this->Controller->set('desktopUrl', $url);

		} else {
			$url = Router::url(array_merge($urlParams, array('mobile' => 1)));
			$this->Controller->set('mobileUrl', $url);
		}

		Configure::write('User.mobile', $this->isMobile);
		Configure::write('User.setMobile', $this->setMobile);

		if (!$this->isMobile) {
			return;
		}

		if (!$this->themed) {
			$this->serveMobileIfAvailable();
			return;
		}

		$this->Controller->viewClass = 'Theme';
		$this->Controller->theme = 'Mobile';
	}

	/**
	 * Determine if we need to so serve mobile views based on session preference and browser headers.
	 *
	 * @return boolean Success
	 */
	public function isMobile() {
		if ($this->isMobile !== null) {
			return $this->isMobile;
		}

		$wantsMobile = null;
		if (isset($this->Controller->request->params['named']['mobile'])) {
			if ($this->Controller->request->params['named']['mobile'] === '-1') {
				$this->Session->delete('User.mobile');
			} else {
				$wantsMobile = (bool)$this->Controller->request->params['named']['mobile'];
			}
		}
		if ($wantsMobile) {
			$this->isMobile = $wantsMobile;
			return $this->isMobile;
		}

		$this->isMobile = $this->Session->read('User.mobile');
		if ($this->isMobile !== null) {
			return $this->isMobile;
		}
		$this->isMobile = (int)$this->detect();
		$this->Session->write('User.mobile', $this->isMobile);
		return $this->isMobile;
	}

	/**
	 * Detect if the current request is from a mobile device
	 *
	 * @return boolean Success
	 */
	public function detect() {
		if ($this->settings['engine'] !== 'cake') {
			throw new CakeException(__('Engine %s not available', $this->settings['engine']));
			//TODO
			// $this->detectByTools()
			// $this->detectByWurfl()
		}
		$this->Controller->request->addDetector('mobile', array('options' => array('OMNIA7')));
		return $this->Controller->request->is('mobile');
	}

	/**
	 * @return boolean Success
	 */
	public function detectByTools() {
		$isMobile = $this->Session->read('Session.mobile');
		if ($isMobile !== null) {
			return $isMobile;
		}
		App::uses('UserAgentLib', 'Tools.Lib');
		$UserAgentLib = new UserAgentLib();
		return (bool)$UserAgentLib->isMobile();
	}

	/**
	 * @return boolean Success
	 */
	public function detectByWurfl() {
		App::import('Vendor', 'WURFL', array('file' => 'WURFLManagerProvider.php'));
		$wurflConfigFile = APP . 'Config' . DS . 'wurfl ' . DS . 'config.xml';
		$wurflManager = WURFL_WURFLManagerProvider::getWURFLManager($wurflConfigFile);

		$requestingDevice = $wurflManager->getDeviceForHttpRequest($_SERVER);
		if ($requestingDevice->getCapability('is_wireless_device') === 'true') {
			return true;
		}
		return false;
	}
}
