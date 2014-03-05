<?php

App::uses('Component', 'Controller');
App::uses('Router', 'Routing');

/**
 * A component to easily serve mobile views to users.
 * It allows good default values while not being restrictive as you can always
 * overwrite the auto-detection manually to force desktop or mobile version.
 *
 * Uses Session to remember lookups: User.mobile and User.nomobile
 * - mobile is the auto-detection (true/false)
 * - nomobile can be set by the user and overrides the default behavior/detection
 *   (1=true/0=false or -1=null which will remove the override)
 * Uses object attributes as well as Configure to store the results for later use.
 * It also pushes switch urls to the view.
 *
 * New:
 * - Support for named params has been dropped in favor of query strings.
 * - Support for different engines (vendor should be used as this is up to date).
 * - Allows Configure setup and auto-start for easy default cases.
 * - Accept closures to easily use any custom detection engine.
 * - Cleanup and tests
 *
 * @author Mark Scherer
 * @license MIT
 */
class MobileComponent extends Component {

	public $components = array('Session');

	public $Controller = null;

	/**
	 * Stores the result of the auto-detection.
	 *
	 * @var boolean
	 */
	public $isMobile = null;

	/**
	 * Stores the final detection result including user preference.
	 *
	 * @var boolean
	 */
	public $setMobile = null;

	/**
	 * Default values. Can also be set using Configure.
	 *
	 * @param array
	 */
	protected $_defaults = array(
		'on' => 'startup', // initialize (prior to controller's beforeRender) or startup
		'engine' => 'vendor', // cake internal (deprecated), tools (deprecated) or vendor
		'themed' => false, // If false uses subfolders instead of themes: /View/.../mobile/
		'mobile' => array('mobile', 'tablet'), // what is mobile? tablets as well? only for vendor
		'auto' => false, // auto set mobile views
	);

	/**
	 * MobileComponent::__construct()
	 *
	 * @param ComponentCollection $collection
	 * @param array $settings
	 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$settings = array_merge($this->_defaults, (array)Configure::read('Mobile'), $settings);
		parent::__construct($collection, $settings);
	}

	/**
	 * MobileComponent::initialize()
	 *
	 * @param Controller $Controller
	 * @return void
	 */
	public function initialize(Controller $Controller) {
		parent::initialize($Controller);
		$this->Controller = $Controller;

		if ($this->settings['on'] !== 'initialize') {
			return;
		}
		$this->_init();
	}

	/**
	 * MobileComponent::startup()
	 *
	 * @param Controller $Controller
	 * @return void
	 */
	public function startup(Controller $Controller) {
		parent::startup($Controller);

		if ($this->settings['on'] !== 'startup') {
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
				$noMobile = (int)(!$wantsMobile);
			}
			$this->Session->write('User.nomobile', $noMobile);
		}
		$this->isMobile();

		if (!$this->settings['auto']) {
			return;
		}
		$this->setMobile();
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
			$urlParams['?']['mobile'] = 0;
			$url = Router::url($urlParams);
			$this->Controller->set('desktopUrl', $url);

		} else {
			$urlParams['?']['mobile'] = 1;
			$url = Router::url($urlParams);
			$this->Controller->set('mobileUrl', $url);
		}

		Configure::write('User.isMobile', (int)$this->isMobile);
		Configure::write('User.setMobile', (int)$this->setMobile);

		if (!$this->setMobile) {
			return;
		}

		if (!$this->settings['themed']) {
			$this->serveMobileIfAvailable();
			return;
		}

		$this->Controller->viewClass = 'Theme';
		$this->Controller->theme = 'Mobile';
	}

	/**
	 * Determines if we need to so serve mobile views based on session preference
	 * and browser headers.
	 *
	 * @return boolean Success
	 */
	public function isMobile() {
		if ($this->isMobile !== null) {
			return $this->isMobile;
		}

		$this->isMobile = $this->Session->read('User.mobile');
		if ($this->isMobile !== null) {
			return $this->isMobile;
		}
		$this->isMobile = (bool)$this->detect();
		$this->Session->write('User.mobile', (int)$this->isMobile);
		return $this->isMobile;
	}

	/**
	 * Detects if the current request is from a mobile device.
	 *
	 * Note that the cake internal way might soon be deprecated:
	 * https://github.com/cakephp/cakephp/issues/2546
	 *
	 * @return boolean Success
	 */
	public function detect() {
		// Deprecated - the vendor libs are far more accurate and up to date
		if ($this->settings['engine'] === 'cake') {
			$this->Controller->request->addDetector('mobile', array('options' => array('OMNIA7')));
			return $this->Controller->request->is('mobile');
		}
		if (is_callable($this->settings['engine'])) {
			return call_user_func($this->settings['engine']);
		}
		if (!in_array($this->settings['engine'], array('tools', 'vendor'))) {
			throw new CakeException(__('Engine %s not available', $this->settings['engine']));
		}
		return $this->detectByVendor($this->settings['engine']);
	}

	/**
	 * Simple auto-detection based on Tools plugin or vendor classes.
	 *
	 * @param string $engine
	 * @return boolean Success
	 */
	public function detectByVendor($engine) {
		$isMobile = $this->Session->read('Session.mobile');
		if ($isMobile !== null) {
			return (bool)$isMobile;
		}

		// Deprecated - the vendor libs are far more accurate and up to date
		if ($engine === 'tools') {
			App::uses('UserAgentLib', 'Tools.Lib');
			$UserAgentLib = new UserAgentLib();
			return (bool)$UserAgentLib->isMobile();
		}

		App::import('Vendor', 'Tools.MobileDetect', array('file' => 'MobileDetect/Mobile_Detect.php'));
		$MobileDetect = new Mobile_Detect();

		$result = empty($this->settings['mobile']) ? 0 : 1;
		if (in_array('mobile', $this->settings['mobile'])) {
			$result &= $MobileDetect->isMobile();
		}
		if (in_array('tablet', $this->settings['mobile'])) {
			$result |= $MobileDetect->isTablet();
		} else {
			$result &= !$MobileDetect->isTablet();
		}

		$this->Session->write('Session.mobile', (bool)$result);
		return (bool)$result;
	}

}
