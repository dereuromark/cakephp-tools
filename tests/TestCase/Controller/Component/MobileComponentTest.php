<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Detection\MobileDetect;
use Tools\Controller\Controller;
use Tools\TestSuite\TestCase;

/**
 * Test MobileComponent
 */
class MobileComponentTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = ['core.sessions'];

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Request::addDetector('mobile', function ($request) {
			$detector = new MobileDetect();
			return $detector->isMobile();
		});
		Request::addDetector('tablet', function ($request) {
			$detector = new MobileDetect();
			return $detector->isTablet();
		});

		$this->event = new Event('Controller.beforeFilter');
		$this->Controller = new MobileComponentTestController(new Request());
		//$this->Controller->constructClasses();

		$this->Controller->request->session()->delete('User');
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

		$this->Controller->request->env('HTTP_ACCEPT', 'text/vnd.wap.wml,text/html,text/plain,image/png,*/*');
		$is = $this->Controller->Mobile->detect();
		$this->assertTrue($is);
	}

	public function testMobileNotMobile() {
		$this->Controller->Mobile->config('on', 'initialize');
		$this->Controller->Mobile->initialize([]);
		$this->assertFalse($this->Controller->Mobile->isMobile);
	}

	public function testMobileForceActivated() {
		$this->Controller->request->query['mobile'] = 1;

		$this->Controller->Mobile->beforeFilter($this->event);
		$session = $this->Controller->request->session()->read('User');
		$this->assertSame(['mobile' => 1], $session);

		$this->Controller->Mobile->setMobile();
		$this->assertTrue($this->Controller->Mobile->setMobile);

		$configure = Configure::read('User');
		$this->assertSame(['isMobile' => 0, 'setMobile' => 1], $configure);
		$this->assertEquals(['desktopUrl' => '/?mobile=0'], $this->Controller->viewVars);
	}

	public function testMobileForceDeactivated() {
		$this->Controller->request->query['mobile'] = 0;

		$this->Controller->Mobile->beforeFilter($this->event);
		$session = $this->Controller->request->session()->read('User');
		$this->assertSame(['mobile' => 0], $session);

		$this->Controller->Mobile->setMobile();
		$configure = Configure::read('User');
		$this->assertSame(['isMobile' => 0, 'setMobile' => 0], $configure);
		$this->assertEquals(['mobileUrl' => '/?mobile=1'], $this->Controller->viewVars);
	}

	public function testMobileFakeMobile() {
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->beforeFilter($this->event);
		$this->assertTrue($this->Controller->Mobile->isMobile);

		$this->Controller->Mobile->setMobile();
		$configure = Configure::read('User');
		$this->assertSame(['isMobile' => 1, 'setMobile' => 1], $configure);
	}

	public function testMobileFakeMobileForceDeactivated() {
		$this->Controller->request->query['mobile'] = 0;
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->beforeFilter($this->event);
		$session = $this->Controller->request->session()->read('User');
		$this->assertSame(['mobile' => 0], $session);

		$this->assertTrue($this->Controller->Mobile->isMobile);

		$this->Controller->Mobile->setMobile();
		$this->assertFalse($this->Controller->Mobile->setMobile);

		$configure = Configure::read('User');
		$this->assertSame(['isMobile' => 1, 'setMobile' => 0], $configure);
	}

	public function testMobileFakeMobileAuto() {
		$this->Controller->Mobile->config('auto', true);
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->beforeFilter($this->event);
		$this->assertTrue($this->Controller->Mobile->isMobile);

		$configure = Configure::read('User');
		$this->assertSame(['isMobile' => 1, 'setMobile' => 1], $configure);
		$this->assertTrue($this->Controller->Mobile->setMobile);
	}

	public function testMobileVendorEngineCake() {
		$this->Controller->Mobile->config('engine', '');
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->beforeFilter($this->event);
		$session = $this->Controller->request->session()->read('User');
		$this->assertTrue($this->Controller->Mobile->isMobile);
	}

	public function testMobileCustomMobileInvalid() {
		$_SERVER['HTTP_USER_AGENT'] = 'Some Foo device';

		$this->Controller->Mobile->beforeFilter($this->event);
		$this->assertFalse($this->Controller->Mobile->isMobile);
	}

	public function testMobileCustomMobile() {
		$_SERVER['HTTP_USER_AGENT'] = 'Some Android device';

		$this->Controller->Mobile->beforeFilter($this->event);
		$this->assertTrue($this->Controller->Mobile->isMobile);
	}

	public function testMobileCustomMobileTablet() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A403 Safari/8536.25';

		$this->Controller->Mobile->beforeFilter($this->event);
		$this->assertTrue($this->Controller->Mobile->isMobile);
	}

	public function testMobileEngineClosure() {
		$closure = function() {
			return $_SERVER['HTTP_USER_AGENT'] === 'Foo';
		};
		$this->Controller->Mobile->config('engine', $closure);
		$_SERVER['HTTP_USER_AGENT'] = 'Foo';

		$this->Controller->Mobile->beforeFilter($this->event);
		$this->assertTrue($this->Controller->Mobile->isMobile);
	}

}

class MobileComponentTestController extends Controller {

	/**
	 * Components property
	 *
	 * @var array
	 */
	public $components = ['RequestHandler', 'Tools.Mobile'];

}
