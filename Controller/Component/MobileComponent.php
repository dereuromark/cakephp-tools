<?php
App::uses('Component', 'Controller');

/**
 * Uses Session: User.mobile and User.nomobile
 * - mobile is the auto-detection (true/false)
 * - nomobile can be set by the user and overrides the default behavior/detection
 *   (1=true/0=false or -1=null which will remove the override)
 *
 * TODO: differentaite between "isMobile" and "has/wants mobile"
 * @author Mark Scherer
 * @license MIT
 * 2011-12-28 ms
 */
class MobileComponent extends Component {

	public $components = array('Session');

	public $isMobile = null;

	public $setMobile = null;

	public $Controller = null;

	protected $_defaults = array(
		'engine' => 'cake',
	);

	public function __construct(ComponentCollection $collection, $settings = array()) {
		$settings = am($this->_defaults, $settings);
		parent::__construct($collection, $settings);
	}

	public function initialize(Controller $Controller) {
		parent::initialize($Controller);
		$this->Controller = $Controller;

		if (isset($this->Controller->request->params['named']['mobile'])) {
			if ($this->Controller->request->params['named']['mobile'] == '-1') {
				$noMobile = null;
			} else {
				$wantsMobile = (bool) $this->Controller->request->params['named']['mobile'];
				$noMobile = (int) (!$wantsMobile);
			}
			$this->Session->write('User.nomobile', $noMobile);

		}
		$this->setMobile();

		$urlParams = Router::getParams(true);
		if (!isset($urlParams['named'])) {
			$urlParams['named'] = array();
		}
		if (!isset($urlParams['pass'])) {
			$urlParams['pass'] = array();
		}
		$urlParams = am($urlParams, $urlParams['named'], $urlParams['pass']);
		unset($urlParams['named']);
		unset($urlParams['pass']);
		if (isset($urlParams['prefix'])) {
			unset($urlParams['prefix']);
		}

		if ($this->setMobile) {
			$url = Router::url(am($urlParams, array('mobile'=>0)));
			$this->Controller->set('desktopUrl', $url);

		} else {
			$url = Router::url(am($urlParams, array('mobile'=>1)));
			$this->Controller->set('mobileUrl', $url);
		}

		Configure::write('User.mobile', $this->isMobile);
		Configure::write('User.setMobile', $this->setMobile);
	}

	public function setMobile() {
		if ($this->isMobile === null) {
			$mobile = $this->isMobile();
			$this->isMobile = $mobile;
		}
		$noMobile = $this->Session->read('User.nomobile');
		if (!$this->isMobile && $noMobile === null || $noMobile) {
			$this->setMobile = false;
			return;
		}
		$this->setMobile = true;
		$this->Controller->viewClass = 'Theme';
		$this->Controller->theme = 'Mobile';
		//$this->Controller->layoutPath = 'mobile';
	}

	/**
	 *
	 * @return bool $success
	 */
	public function isMobile() {
		$isMobile = $this->Session->read('User.mobile');
		if ($isMobile !== null) {
			return $isMobile;
		}
		if ($this->settings['engine'] !== 'cake') {
 			throw new CakeException(__('Engine %s not available', $this->settings['engine']));
			//TODO
		}
		$isMobile = (int)$this->detect();
		$this->Session->write('User.mobile', $isMobile);
		return $isMobile;
	}

	public function detect() {
		$this->Controller->request->addDetector('mobile', array('options' => array('OMNIA7')));
		return $this->Controller->request->is('mobile');
	}



	public function detectByTools() {
		$isMobile = $this->Session->read('Session.mobile');
		if ($isMobile !== null) {
			return $isMobile;
		}
		App::uses('UserAgentLib', 'Tools.Lib');
		$UserAgentLib = new UserAgentLib();
		$mobile = (int)$UserAgentLib->isMobile();
		$this->Session->write('Session.mobile', $mobile);
		return $mobile;
	}

	public function detectByWurfl() {
		App::import('Vendor', 'WURFL', array('file' => 'WURFLManagerProvider.php'));
		$wurflConfigFile = APP . 'Config' . DS . 'wurfl ' . DS . 'config.xml';
		$wurflManager = WURFL_WURFLManagerProvider::getWURFLManager($wurflConfigFile);

		$requestingDevice = $wurflManager->getDeviceForHttpRequest($_SERVER);
		if ($requestingDevice->getCapability('is_wireless_device') == 'true') {
			return true;
		}
		return false;
	}
}
