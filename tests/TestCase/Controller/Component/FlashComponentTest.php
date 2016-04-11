<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Tools\TestSuite\TestCase;

/**
 */
class FlashComponentTest extends TestCase {

	//public $fixtures = array('core.sessions', 'plugin.tools.tools_users', 'plugin.tools.roles');

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Controller = new FlashComponentTestController();
		$this->Controller->startupProcess();

		$this->Controller->request->session()->delete('FlashMessage');
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Controller->Flash);
		unset($this->Controller);
	}

	/**
	 * FlashComponentTest::testTransientMessage()
	 *
	 * @return void
	 */
	public function testTransientMessage() {
		$is = $this->Controller->Flash->transientMessage('xyz', 'success');

		$res = Configure::read('FlashMessage');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['success'][0]) && $res['success'][0] === 'xyz');
	}

	/**
	 * FlashComponentTest::testMessage()
	 *
	 * @return void
	 */
	public function testMessage() {
		$is = $this->Controller->Flash->message('efg');

		$res = $this->Controller->request->session()->read('FlashMessage');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]));
		$this->assertSame('efg', $res['info'][0]);
	}

	/**
	 * FlashComponentTest::testMagic()
	 *
	 * @return void
	 */
	public function testMagic() {
		$is = $this->Controller->Flash->error('Some Error Message');

		$res = $this->Controller->request->session()->read('FlashMessage');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['error'][0]));
		$this->assertSame('Some Error Message', $res['error'][0]);
	}

	/**
	 * FlashComponentTest::testCoreHook()
	 *
	 * @return void
	 */
	public function testCoreHook() {
		$is = $this->Controller->Flash->set('Some Message');

		$res = $this->Controller->request->session()->read('FlashMessage');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]));
		$this->assertSame('Some Message', $res['info'][0]);
	}

	/**
	 * FlashComponentTest::testAjax()
	 *
	 * @return void
	 */
	public function testAjax() {
		$this->Controller->request = $this->getMock('Cake\Network\Request', ['is']);
		$this->Controller->Flash->success('yeah');
		$this->Controller->Flash->transientMessage('xyz', 'warning');

		$this->Controller->request->expects($this->once())
			->method('is')
			->with('ajax')
			->will($this->returnValue(true));

		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->Flash->beforeRender($event);

		$result = $this->Controller->response->header();
		$expected = ['X-Flash' => '{"success":["yeah"],"warning":["xyz"]}'];
		$this->assertSame($expected, $result);
	}

}

/**
 * Use Controller instead of AppController to avoid conflicts
 */
class FlashComponentTestController extends Controller {

	/**
	 * @var array
	 */
	public $components = ['Tools.Flash'];

	/**
	 * @var bool
	 */
	public $failed = false;

	/**
	 * @var array
	 */
	public $testHeaders = [];

	public function fail() {
		$this->failed = true;
	}

	public function redirect($url, $status = null, $exit = true) {
		return $status;
	}

	public function header($status) {
		$this->testHeaders[] = $status;
	}

}
