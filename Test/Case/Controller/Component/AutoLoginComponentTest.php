<?php

App::uses('AutoLoginComponent', 'Tools.Controller/Component');
App::uses('Controller', 'Controller');

/**
 * Short description for class.
 *
 */
class AutoLoginComponentTest extends CakeTestCase {

	public $fixtures = array('core.cake_session', 'core.user');

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('AutoLogin.active', 1);
		Configure::write('AutoLogin.cookieName', 'autoLogin');

		$this->Controller = new AutoLoginTestController(new CakeRequest, new CakeResponse);
		$this->Controller->AutoLogin = new AutoLoginComponent(new ComponentCollection());
	}

	/**
	 * Tear-down method. Resets environment state.
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Controller->AutoLogin);
		unset($this->Controller);
	}

	/**
	 * Test if suhosin isn't messing up srand() and mt_srand()
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
	 * Test merge of configs
	 */
	public function testConfigs() {
		$this->Controller->AutoLogin->initialize($this->Controller);
		$settings = $this->Controller->AutoLogin->settings;
		$this->assertTextStartsWith('autoLogin', $settings['cookieName']);
	}

	/**
	 * Test cookie name
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
 */
class AutoLoginTestController extends Controller {

	/**
	 * Components property
	 *
	 * @var array
	 */
	public $components = array('Tools.AutoLogin');

	/**
	 * Failed property
	 *
	 * @var boolean
	 */
	public $failed = false;

	/**
	 * Used for keeping track of headers in test
	 *
	 * @var array
	 */
	public $testHeaders = array();

	/**
	 * Fail method
	 *
	 * @return void
	 */
	public function fail() {
		$this->failed = true;
	}

	/**
	 * Redirect method
	 *
	 * @param mixed $option
	 * @param mixed $code
	 * @param mixed $exit
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
	 */
	public function header($status) {
		$this->testHeaders[] = $status;
	}
}
