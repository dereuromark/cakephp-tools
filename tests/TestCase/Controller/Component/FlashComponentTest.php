<?php
namespace Tools\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component;
use Cake\Controller\Component\FlashComponent;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Routing\DispatcherFactory;
use Cake\TestSuite\TestCase;

/**
 */
class FlashComponentTest extends TestCase {

	//public $fixtures = array('core.sessions', 'plugin.tools.tools_users', 'plugin.tools.roles');

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Controller = new FlashComponentTestController();
		$this->Controller->startupProcess();
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
		//$this->assertTrue($is);

		$res = Configure::read('messages');
		//debug($res);
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['success'][0]) && $res['success'][0] === 'xyz');
	}

	/**
	 * FlashComponentTest::testMessage()
	 *
	 * @return void
	 */
	public function testMessage() {
		$this->Controller->request->session()->delete('messages');
		$is = $this->Controller->Flash->message('efg');

		$res = $this->Controller->request->session()->read('messages');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]) && $res['info'][0] === 'efg');
	}

}

/**
 * Use Controller instead of AppController to avoid conflicts
 */
class FlashComponentTestController extends Controller {

	public $components = array('Tools.Flash');

	public $failed = false;

	public $testHeaders = array();

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
