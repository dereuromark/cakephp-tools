<?php

App::uses('FlashComponent', 'Tools.Controller/Component');
App::uses('Component', 'Controller');
App::uses('CakeSession', 'Model/Datasource');
App::uses('Controller', 'Controller');
App::uses('AppModel', 'Model');

/**
 */
class FlashComponentTest extends CakeTestCase {

	public $fixtures = ['core.cake_session'];

	public function setUp() {
		parent::setUp();

		// BUGFIX for CakePHP2.5 - One has to write to the session before deleting actually works
		CakeSession::write('Auth', '');
		CakeSession::delete('Auth');

		$this->Controller = new FlashComponentTestController(new CakeRequest, new CakeResponse);
		$this->Controller->constructClasses();
		$this->Controller->startupProcess();

		$this->Controller->Session->delete('messages');
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
		$this->Controller->Flash->transientMessage('xyz', 'success');

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
		$this->Controller->Flash->message('efg');

		$res = $this->Controller->Session->read('messages');
		$this->assertTrue(!empty($res));
		$this->assertTrue(isset($res['info'][0]) && $res['info'][0] === 'efg');
	}

	/**
	 * FlashComponentTest::testFlashMessage()
	 *
	 * @return void
	 */
	public function testFlashMessageComplex() {
		$this->Controller->Flash->settings['useElements'] = true;
		$this->Controller->Flash->message('efg', ['escape' => true, 'element' => 'PluginName.Baz', 'params' => ['foo' => 'bar']]);

		$res = $this->Controller->Session->read('messages');
		$this->assertTrue(!empty($res));
		$expected = [
			'message' => 'efg',
			'params' => ['foo' => 'bar'],
			'escape' => true,
			'element' => 'PluginName.Flash/Baz'
		];
		$this->assertSame($expected, $res['info'][0]);
	}

	/**
	 * FlashComponentTest::testFlashMessage()
	 *
	 * @return void
	 */
	public function testFlashMessageTypeToElement() {
		$this->Controller->Flash->settings['useElements'] = true;
		$this->Controller->Flash->settings['typeToElement'] = true;
		$this->Controller->Flash->settings['plugin'] = 'Tools';
		$this->Controller->Flash->message('INFO', ['escape' => true, 'params' => ['foo' => 'bar']]);
		$this->Controller->Flash->success('OK', ['escape' => true, 'plugin' => 'BarBaz', 'params' => ['foo' => 'bar']]);
		$this->Controller->Flash->error('NO', ['escape' => true, 'plugin' => null, 'params' => ['foo' => 'bar']]);

		$res = $this->Controller->Session->read('messages');
		$this->assertTrue(!empty($res));
		$expected = 'Tools.Flash/info';
		$this->assertSame($expected, $res['info'][0]['element']);
		$expected = 'BarBaz.Flash/success';
		$this->assertSame($expected, $res['success'][0]['element']);
		$expected = 'Flash/error';
		$this->assertSame($expected, $res['error'][0]['element']);
	}

	/**
	 * FlashComponentTest::testMagicMessage()
	 *
	 * @return void
	 */
	public function testMagicMessage() {
		$this->Controller->Flash->success('s');
		$this->Controller->Flash->error('e');
		$this->Controller->Flash->warning('w');

		$res = $this->Controller->Session->read('messages');
		$expected = [
			'success' => ['s'],
			'error' => ['e'],
			'warning' => ['w']];
		$this->assertSame($expected, $res);
	}

}

// Use Controller instead of AppController to avoid conflicts
class FlashComponentTestController extends Controller {

	public $components = ['Session', 'Tools.Flash'];

	public $failed = false;

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
