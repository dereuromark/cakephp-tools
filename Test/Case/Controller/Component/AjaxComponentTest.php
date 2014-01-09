<?php

App::uses('AjaxComponent', 'Tools.Controller/Component');
App::uses('Component', 'Controller');
App::uses('Controller', 'Controller');
App::uses('AppModel', 'Model');

/**
 */
class AjaxComponentTest extends CakeTestCase {

	public $fixtures = array('core.cake_session', 'plugin.tools.tools_user', 'plugin.tools.role');

	public function setUp() {
		parent::setUp();
		Configure::delete('Ajax');

		$this->Controller = new AjaxComponentTestController(new CakeRequest, new CakeResponse);
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
		$expected = array(
			'message' => 'A message',
			'element' => 'custom',
			'params' => array()
		);
		$this->assertEquals($expected, $session);

		$this->Controller->Components->Ajax->beforeRender($this->Controller);
		$this->assertEqual('Tools.Ajax', $this->Controller->viewClass);

		$this->assertEquals($expected, $this->Controller->viewVars['_message']);

		$session = $this->Controller->Session->read('Message.flash');
		$this->assertNull($session);

		$this->Controller->redirect('/');
		$this->assertSame(array(), $this->Controller->response->header());

		$expected = array(
			'url' => Router::url('/', true),
			'status' => null,
			'exit' => true
		);
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
		$this->Controller->Components->load('Tools.Ajax', array('autoDetect' => false));

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

		$this->Controller->Common->flashMessage('A message', 'success');
		$session = $this->Controller->Session->read('messages');
		$expected = array(
			'success' => array('A message')
		);
		$this->assertEquals($expected, $session);

		$this->Controller->Components->Ajax->beforeRender($this->Controller);
		$this->assertEquals('Tools.Ajax', $this->Controller->viewClass);

		$this->assertEquals($expected, $this->Controller->viewVars['_message']);

		$session = $this->Controller->Session->read('messages');
		$this->assertNull($session);
	}

}

// Use Controller instead of AppController to avoid conflicts
class AjaxComponentTestController extends Controller {

	public $components = array('Session', 'Tools.Ajax', 'Tools.Common');

}
