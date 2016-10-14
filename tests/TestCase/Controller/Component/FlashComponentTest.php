<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use TestApp\Controller\FlashComponentTestController;
use Tools\TestSuite\TestCase;

/**
 */
class FlashComponentTest extends TestCase {

	/**
	 * @var \TestApp\Controller\FlashComponentTestController
	 */
	public $Controller;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Controller = new FlashComponentTestController();
		$this->Controller->startupProcess();

		$this->Controller->request->session()->delete('FlashMessage');
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Controller->Flash);
		unset($this->Controller);
	}

	/**
	 * @return void
	 */
	public function testTransientMessage() {
		$this->Controller->Flash->transientMessage('xyz', 'success');

		$res = Configure::read('FlashMessage');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['success'][0]) && $res['success'][0] === 'xyz');
	}

	/**
	 * @return void
	 */
	public function testMessage() {
		$this->Controller->Flash->message('efg');

		$res = $this->Controller->request->session()->read('FlashMessage');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]));
		$this->assertSame('efg', $res['info'][0]);
	}

	/**
	 * @return void
	 */
	public function testMagic() {
		$this->Controller->Flash->error('Some Error Message');

		$res = $this->Controller->request->session()->read('FlashMessage');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['error'][0]));
		$this->assertSame('Some Error Message', $res['error'][0]);
	}

	/**
	 * @return void
	 */
	public function testCoreHook() {
		$this->Controller->Flash->set('Some Message');

		$res = $this->Controller->request->session()->read('FlashMessage');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]));
		$this->assertSame('Some Message', $res['info'][0]);
	}

	/**
	 * @return void
	 */
	public function testAjax() {
		$this->Controller->request = $this->getMockBuilder(Request::class)->setMethods(['is'])->getMock();
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
