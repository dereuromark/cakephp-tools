<?php

App::uses('AutoLoginComponent', 'Tools.Controller/Component');
App::uses('Controller', 'Controller');

/**
 * Short description for class.
 *
 * @package cake.tests
 * @subpackage cake.tests.cases.libs.controller.components
 */
class AutoLoginComponentTest extends CakeTestCase {

	public $fixtures = array('core.cake_session', 'plugin.tools.user');

	/**
	 * setUp method
	 *
	 * @access public
	 * @return void
	 */
	public function setUp() {
		Configure::write('AutoLogin.active', 1);
		Configure::write('AutoLogin.cookieName', 'autoLogin');

		$this->Controller = new AutoLoginTestController(new CakeRequest, new CakeResponse);
		$this->Controller->AutoLogin = new AutoLoginComponent(new ComponentCollection());
	}

	/**
	 * Tear-down method. Resets environment state.
	 *
	 * @access public
	 * @return void
	 */
	public function tearDown() {
		unset($this->Controller->AutoLogin);
		unset($this->Controller);
	}

	/**
	 * test if suhosin isn't messing up srand() and mt_srand()
	 * run this on every the environment you want AutoLogin to work!
	 * It this test fails add `suhosin.srand.ignore = Off`
	 * in your `/etc/php5/apache2/php.ini`
	 * And don't forget to restart apache or at least `/etc/init.d/apache2 force-reload`
	 */
	public function testIfRandWillWork() {
		srand('1234567890');
		$rand1 = rand(0, 255);

		srand('1234567890');
		$rand2 = rand(0, 255);

		$this->assertSame($rand1, $rand2, 'You have the Suhosin BUG! Add `suhosin.srand.ignore = Off` to your php.ini!');
	}

	/**
	 * test merge of configs
	 */
	public function testConfigs() {
		$this->Controller->AutoLogin->initialize($this->Controller);
		$settings = $this->Controller->AutoLogin->settings;
		$this->assertTextStartsWith('autoLogin', $settings['cookieName']);
	}

	/**
	 * test cookie name
	 */
	public function testConfigsWithCustomCookieName() {
		Configure::write('AutoLogin.cookieName', 'myAutoLogin');
		$this->Controller->AutoLogin = new AutoLoginComponent(new ComponentCollection());
		$this->Controller->AutoLogin->initialize($this->Controller);
		$settings = $this->Controller->AutoLogin->settings;
		$this->assertTextStartsWith('myAutoLogin', $settings['cookieName']);

		Configure::write('AutoLogin.cookieName', 'myOtherAutoLogin');
		$this->Controller->AutoLogin = new AutoLoginComponent(new ComponentCollection());
		$this->Controller->AutoLogin->initialize($this->Controller);
		$settings = $this->Controller->AutoLogin->settings;
		$this->assertTextStartsWith('myOtherAutoLogin', $settings['cookieName']);
	}

	public function testLogin() {
		$this->Controller->AutoLogin = new AutoLoginComponent(new ComponentCollection());
		$this->Controller->AutoLogin->initialize($this->Controller);
		$settings = $this->Controller->AutoLogin->settings;
		//die(returns($settings));
		//TODO
	}

}


/**
 * Short description for class.
 *
 * @package cake.tests
 * @subpackage cake.tests.cases.libs.controller.components
 */
class AutoLoginTestController extends Controller {
	/**
	 * name property
	 *
	 * @var string 'SecurityTest'
	 * @access public
	 */

	/**
	 * components property
	 *
	 * @var array
	 * @access public
	 */
	public $components = array('Tools.AutoLogin');
	/**
	 * failed property
	 *
	 * @var bool false
	 * @access public
	 */
	public $failed = false;
	/**
	 * Used for keeping track of headers in test
	 *
	 * @var array
	 * @access public
	 */
	public $testHeaders = array();
	/**
	 * fail method
	 *
	 * @access public
	 * @return void
	 */
	public function fail() {
		$this->failed = true;
	}
	/**
	 * redirect method
	 *
	 * @param mixed $option
	 * @param mixed $code
	 * @param mixed $exit
	 * @access public
	 * @return void
	 */
	public function redirect($url, $status = null, $exit = true) {
		return $status;
	}
	/**
	 * Conveinence method for header()
	 *
	 * @param string $status
	 * @return void
	 * @access public
	 */
	public function header($status) {
		$this->testHeaders[] = $status;
	}
}
