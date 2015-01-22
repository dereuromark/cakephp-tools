<?php

App::uses('AjaxComponent', 'Tools.Controller/Component');
App::uses('Controller', 'Controller');
App::uses('AppModel', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 */
class AjaxComponentTest extends CakeTestCase {

	public $fixtures = ['core.cake_session', 'plugin.tools.tools_user', 'plugin.tools.role'];

	public function setUp() {
		parent::setUp();
		Configure::delete('Ajax');

		$this->Controller = new AjaxComponentTestController(new CakeRequest(), new CakeResponse());
		$this->Controller->constructClasses();
	}

	/**
	 * AjaxComponentTest::testNonAjax()
	 *
	 * @return void
	 */
	public function testNonAjax() {
		$this->Controller->startupProcess();
		$this->assertFalse($this->Controller->Components->Ajax->respondAsAjax);
	}

	/**
	 * AjaxComponentTest::testDefaults()
	 *
	 * @return void
	 */
	public function testDefaults() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$this->Controller->startupProcess();
		$this->assertTrue($this->Controller->Components->Ajax->respondAsAjax);

		$this->Controller->Session->setFlash('A message', 'custom');
		$session = $this->Controller->Session->read('Message.flash');
		$expected = [
			'message' => 'A message',
			'element' => 'custom',
			'params' => []
		];
		$this->assertEquals($expected, $session);

		$this->Controller->Components->Ajax->beforeRender($this->Controller);

		$this->assertEquals('Tools.Ajax', $this->Controller->viewClass);
		$this->assertEquals($expected, $this->Controller->viewVars['_message']);

		$session = $this->Controller->Session->read('Message.flash');
		$this->assertNull($session);

		$this->Controller->redirect('/');
		$this->assertSame([], $this->Controller->response->header());

		$expected = [
			'url' => Router::url('/', true),
			'status' => null,
			'exit' => true
		];
		$this->assertEquals($expected, $this->Controller->viewVars['_redirect']);
	}

	/**
	 * AjaxComponentTest::testAutoDetectOnFalse()
	 *
	 * @return void
	 */
	public function testAutoDetectOnFalse() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$this->Controller->Components->unload('Ajax');
		$this->Controller->Components->load('Tools.Ajax', ['autoDetect' => false]);

		$this->Controller->startupProcess();
		$this->assertFalse($this->Controller->Components->Ajax->respondAsAjax);
	}

	/**
	 * AjaxComponentTest::testAutoDetectOnFalseViaConfig()
	 *
	 * @return void
	 */
	public function testAutoDetectOnFalseViaConfig() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		Configure::write('Ajax.autoDetect', false);

		$this->Controller->Components->unload('Ajax');
		$this->Controller->Components->load('Tools.Ajax');

		$this->Controller->startupProcess();
		$this->assertFalse($this->Controller->Components->Ajax->respondAsAjax);
	}

	/**
	 * AjaxComponentTest::testToolsMultiMessages()
	 *
	 * @return void
	 */
	public function testToolsMultiMessages() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		Configure::write('Ajax.flashKey', 'messages');

		$this->Controller->Components->unload('Ajax');
		$this->Controller->Components->load('Tools.Ajax');

		$this->Controller->startupProcess();
		$this->assertTrue($this->Controller->Components->Ajax->respondAsAjax);

		$this->Controller->Flash->message('A message', 'success');
		$session = $this->Controller->Session->read('messages');
		$expected = [
			'success' => ['A message']
		];
		$this->assertEquals($expected, $session);

		$this->Controller->Components->Ajax->beforeRender($this->Controller);
		$this->assertEquals('Tools.Ajax', $this->Controller->viewClass);

		$this->assertEquals($expected, $this->Controller->viewVars['_message']);

		$session = $this->Controller->Session->read('messages');
		$this->assertNull($session);
	}

	/**
	 * AjaxComponentTest::testSetVars()
	 *
	 * @return void
	 */
	public function testSetVars() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$this->Controller->Components->unload('Ajax');

		$content = ['id' => 1, 'title' => 'title'];
		$this->Controller->set(compact('content'));
		$this->Controller->set('_serialize', ['content']);

		$this->Controller->Components->load('Tools.Ajax');
		$this->assertNotEmpty($this->Controller->viewVars);
		$this->assertNotEmpty($this->Controller->viewVars['_serialize']);
		$this->assertEquals('content', $this->Controller->viewVars['_serialize'][0]);
	}

	/**
	 * AjaxComponentTest::testSetVarsWithRedirect()
	 *
	 * @return void
	 */
	public function testSetVarsWithRedirect() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->Controller->startupProcess();

		$content = ['id' => 1, 'title' => 'title'];
		$this->Controller->set(compact('content'));
		$this->Controller->set('_serialize', ['content']);

		$this->Controller->redirect('/');
		$this->assertSame([], $this->Controller->response->header());

		$expected = [
			'url' => Router::url('/', true),
			'status' => null,
			'exit' => true
		];
		$this->assertEquals($expected, $this->Controller->viewVars['_redirect']);

		$this->Controller->set(['_message' => 'test']);
		$this->Controller->redirect('/');
		$this->assertArrayHasKey('_message', $this->Controller->viewVars);

		$this->assertNotEmpty($this->Controller->viewVars);
		$this->assertNotEmpty($this->Controller->viewVars['_serialize']);
		$this->assertTrue(in_array('content', $this->Controller->viewVars['_serialize']));
	}
}

// Use Controller instead of AppController to avoid conflicts
class AjaxComponentTestController extends Controller {

	public $components = ['Session', 'Tools.Ajax', 'Tools.Common', 'Tools.Flash'];

}
