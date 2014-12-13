<?php

App::uses('FlashHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * FlashHelper tests
 */
class FlashHelperTest extends MyCakeTestCase {

	public $fixtures = array('core.cake_session');

	public $Flash;

	public function setUp() {
		parent::setUp();

		Router::reload();
		$View = new View(null);
		$this->Flash = new FlashHelper($View);
	}

	/**
	 * FlashHelperTest::testFlashMessage()
	 *
	 * @return void
	 */
	public function testMessage() {
		$result = $this->Flash->message(h('Foo & bar'), 'success');
		$expected = '<div class="flash-messages flashMessages"><div class="message success">Foo &amp;amp; bar</div></div>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * FlashHelperTest::testFlash()
	 *
	 * @return void
	 */
	public function testFlash() {
		$this->Flash->addTransientMessage(h('Foo & bar'), 'success');

		$result = $this->Flash->flash();
		$expected = '<div class="flash-messages flashMessages"><div class="message success">Foo &amp; bar</div></div>';
		$this->assertEquals($expected, $result);

		$this->Flash->addTransientMessage('I am an error', 'error');
		$this->Flash->addTransientMessage('I am a warning', 'warning');
		$this->Flash->addTransientMessage('I am some info', 'info');
		$this->Flash->addTransientMessage('I am also some info');
		$this->Flash->addTransientMessage('I am sth custom', 'custom');

		$result = $this->Flash->flash();
		$this->assertTextContains('message error', $result);
		$this->assertTextContains('message warning', $result);
		$this->assertTextContains('message info', $result);
		$this->assertTextContains('message custom', $result);

		$result = substr_count($result, 'message info');
		$this->assertSame(2, $result);
	}

	/**
	 * Test that you can define your own order or just output a subpart of
	 * the types.
	 *
	 * @return void
	 */
	public function testFlashWithTypes() {
		$this->Flash->addTransientMessage('I am an error', 'error');
		$this->Flash->addTransientMessage('I am a warning', 'warning');
		$this->Flash->addTransientMessage('I am some info', 'info');
		$this->Flash->addTransientMessage('I am also some info');
		$this->Flash->addTransientMessage('I am sth custom', 'custom');

		$result = $this->Flash->flash(array('warning', 'error'));
		$expected = '<div class="flash-messages flashMessages"><div class="message warning">I am a warning</div><div class="message error">I am an error</div></div>';
		$this->assertEquals($expected, $result);

		$result = $this->Flash->flash(array('info'));
		$expected = '<div class="flash-messages flashMessages"><div class="message info">I am some info</div><div class="message info">I am also some info</div></div>';
		$this->assertEquals($expected, $result);

		$result = $this->Flash->flash();
		$expected = '<div class="flash-messages flashMessages"><div class="message custom">I am sth custom</div></div>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Flash);
	}

}
