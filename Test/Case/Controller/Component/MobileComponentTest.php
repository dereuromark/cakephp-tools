<?php

App::uses('MobileComponent', 'Tools.Controller/Component');
App::uses('CakeSession', 'Model/Datasource');
App::uses('Component', 'Controller');
App::uses('AppController', 'Controller');

/**
 * Test MobileComponent
 */
class MobileComponentTest extends CakeTestCase {

	public $fixtures = array('core.cake_session');

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Controller = new MobileComponentTestController(new CakeRequest(null, false), new CakeResponse());
		$this->Controller->constructClasses();
		$this->Controller->Mobile->Controller = $this->Controller;

		CakeSession::write('User', '');
		CakeSession::delete('User');
		CakeSession::write('Session', '');
		CakeSession::delete('Session');
		Configure::delete('User');
	}

	/**
	 * Tear-down method. Resets environment state.
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Controller->Mobile);
		unset($this->Controller);
	}

	public function testDetect() {
		$is = $this->Controller->Mobile->detect();
		$this->assertFalse($is);
	}

	public function testMobileNotMobile() {
		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$this->assertFalse($this->Controller->Mobile->isMobile);

		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 0), $session);
	}

	public function testMobileForceActivated() {
		$this->Controller->request->query['mobile'] = 1;
		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('nomobile' => 0, 'mobile' => 0), $session);

		$this->Controller->Mobile->setMobile();
		$configure = Configure::read('User');
		$this->assertSame(array('isMobile' => 0, 'setMobile' => 1), $configure);
		$this->assertEquals(array('desktopUrl' => '/?mobile=0'), $this->Controller->viewVars);
	}

	public function testMobileForceDeactivated() {
		$this->Controller->request->query['mobile'] = 0;
		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('nomobile' => 1, 'mobile' => 0), $session);

		$this->Controller->Mobile->setMobile();
		$configure = Configure::read('User');
		$this->assertSame(array('isMobile' => 0, 'setMobile' => 0), $configure);
		$this->assertEquals(array('mobileUrl' => '/?mobile=1'), $this->Controller->viewVars);
	}

	public function testMobileFakeMobile() {
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 1), $session);

		$this->assertTrue($this->Controller->Mobile->isMobile);

		$this->Controller->Mobile->setMobile();
		$configure = Configure::read('User');
		$this->assertSame(array('isMobile' => 1, 'setMobile' => 1), $configure);
	}

	public function testMobileFakeMobileForceDeactivated() {
		$this->Controller->request->query['mobile'] = 0;
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('nomobile' => 1, 'mobile' => 1), $session);

		$this->assertTrue($this->Controller->Mobile->isMobile);

		$this->Controller->Mobile->setMobile();
		$configure = Configure::read('User');
		$this->assertSame(array('isMobile' => 1, 'setMobile' => 0), $configure);
	}

	public function testMobileFakeMobileAuto() {
		$this->Controller->Mobile->settings['auto'] = true;
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 1), $session);
		$this->assertTrue($this->Controller->Mobile->isMobile);

		$configure = Configure::read('User');
		$this->assertSame(array('isMobile' => 1, 'setMobile' => 1), $configure);
		$this->assertTrue($this->Controller->Mobile->setMobile);
	}

	public function testMobileVendorEngineCake() {
		$this->Controller->Mobile->settings['engine'] = 'cake';
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 1), $session);
		$this->assertTrue($this->Controller->Mobile->isMobile);
	}

	public function testMobileVendorEngineTools() {
		$this->Controller->Mobile->settings['engine'] = 'tools';
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 1), $session);
		$this->assertTrue($this->Controller->Mobile->isMobile);
	}

	public function testMobileCustomMobile() {
		$this->Controller->Mobile->settings['mobile'] = array();
		$_SERVER['HTTP_USER_AGENT'] = 'Some Ipad device';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 0), $session);
	}

	public function testMobileCustomMobileMobile() {
		$this->Controller->Mobile->settings['mobile'] = array('mobile');
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 1), $session);
	}

	public function testMobileCustomMobileTablet() {
		$this->Controller->Mobile->settings['mobile'] = array('tablet');
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A403 Safari/8536.25';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 1), $session);
	}

	public function testMobileCustomMobileTablet2() {
		$this->Controller->Mobile->settings['mobile'] = array('mobile');
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A403 Safari/8536.25';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 0), $session);
	}

	public function testMobileEngineClosure() {
		$closure = function() {
			return $_SERVER['HTTP_USER_AGENT'] === 'Foo';
		};
		$this->Controller->Mobile->settings['engine'] = $closure;
		$_SERVER['HTTP_USER_AGENT'] = 'Foo';

		$this->Controller->Mobile->initialize($this->Controller);
		$this->Controller->Mobile->startup($this->Controller);
		$session = CakeSession::read('User');
		$this->assertSame(array('mobile' => 1), $session);
	}

}

class MobileComponentTestController extends AppController {

	/**
	 * Components property
	 *
	 * @var array
	 */
	public $components = array('Tools.Mobile');

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

}
