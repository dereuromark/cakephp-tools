<?php

App::uses('FlashComponent', 'Tools.Controller/Component');
App::uses('Component', 'Controller');
App::uses('CakeSession', 'Model/Datasource');
App::uses('Controller', 'Controller');
App::uses('AppModel', 'Model');

/**
 */
class FlashComponentTest extends CakeTestCase {

	public $fixtures = array('core.cake_session');

	public function setUp() {
		parent::setUp();

		// BUGFIX for CakePHP2.5 - One has to write to the session before deleting actually works
		CakeSession::write('Auth', '');
		CakeSession::delete('Auth');

		$this->Controller = new FlashComponentTestController(new CakeRequest, new CakeResponse);
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Controller);
	}

	/**
	 * FlashComponentTest::testTransientFlashMessage()
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
	 * FlashComponentTest::testFlashMessage()
	 *
	 * @return void
	 */
	public function testFlashMessage() {
		$this->Controller->Session->delete('messages');
		$is = $this->Controller->Flash->message('efg');

		$res = $this->Controller->Session->read('messages');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]) && $res['info'][0] === 'efg');
	}

}

// Use Controller instead of AppController to avoid conflicts
class FlashComponentTestController extends Controller {

	public $components = array('Session', 'Tools.Flash');

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
