<?php

App::uses('CommonHelper', 'Tools.View/Helper');
App::uses('HtmlHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * CommonHelper tests
 */
class CommonHelperTest extends MyCakeTestCase {

	public $fixtures = array('core.cake_session');

	public $Common;

	public function setUp() {
		parent::setUp();

		Router::reload();
		$View = new View(null);
		$this->Common = new CommonHelper($View);
		$this->Html = new CommonHelper($View);
	}

	/**
	 * CommonHelperTest::testFlashMessage()
	 *
	 * @return void
	 */
	public function testFlashMessage() {
		$result = $this->Common->flashMessage(h('Foo & bar'), 'success');
		$expected = '<div class="flash-messages flashMessages"><div class="message success">Foo &amp;amp; bar</div></div>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * CommonHelperTest::testAlternate()
	 *
	 * @return void
	 */
	public function testAlternate() {
		$result = $this->Common->alternate('one', 'two');
		$this->assertEquals('one', $result);
		$result = $this->Common->alternate('one', 'two');
		$this->assertEquals('two', $result);
		$result = $this->Common->alternate('one', 'two');
		$this->assertEquals('one', $result);
	}

	/**
	 * CommonHelperTest::testMetaRobots()
	 *
	 * @return void
	 */
	public function testMetaRobots() {
		$result = $this->Common->metaRobots();
		$this->assertContains('<meta name="robots" content="', $result);
	}

	/**
	 * CommonHelperTest::testMetaName()
	 *
	 * @return void
	 */
	public function testMetaName() {
		$result = $this->Common->metaName('foo', array(1, 2, 3));
		$expected = '<meta name="foo" content="1, 2, 3" />';
		$this->assertEquals($expected, $result);
	}

	/**
	 * CommonHelperTest::testMetaDescription()
	 *
	 * @return void
	 */
	public function testMetaDescription() {
		$result = $this->Common->metaDescription('foo', 'deu');
		$expected = '<meta lang="deu" name="description" content="foo" />';
		$this->assertEquals($expected, $result);
	}

	/**
	 * CommonHelperTest::testMetaKeywords()
	 *
	 * @return void
	 */
	public function testMetaKeywords() {
		$result = $this->Common->metaKeywords('foo bar', 'deu');
		$expected = '<meta lang="deu" name="keywords" content="foo bar" />';
		$this->assertEquals($expected, $result);
	}

	/**
	 * CommonHelperTest::testMetaRss()
	 *
	 * @return void
	 */
	public function testMetaRss() {
		$result = $this->Common->metaRss('/some/url', 'some title');
		$expected = '<link rel="alternate" type="application/rss+xml" title="some title" href="/some/url" />';
		$this->assertEquals($expected, $result);
	}

	/**
	 * CommonHelperTest::testMetaEquiv()
	 *
	 * @return void
	 */
	public function testMetaEquiv() {
		$result = $this->Common->metaEquiv('type', 'value');
		$expected = '<meta http-equiv="type" content="value" />';
		$this->assertEquals($expected, $result);
	}

	/**
	 * CommonHelperTest::testDisplayErrors()
	 *
	 * @return void
	 */
	public function testDisplayErrors() {
		$result = $this->Common->displayErrors();
		$this->assertEquals('', $result);
	}

	/**
	 * CommonHelperTest::testFlash()
	 *
	 * @return void
	 */
	public function testFlash() {
		$this->Common->addFlashMessage(h('Foo & bar'), 'success');

		$result = $this->Common->flash();
		$expected = '<div class="flash-messages flashMessages"><div class="message success">Foo &amp; bar</div></div>';
		$this->assertEquals($expected, $result);

		$this->Common->addFlashMessage('I am an error', 'error');
		$this->Common->addFlashMessage('I am a warning', 'warning');
		$this->Common->addFlashMessage('I am some info', 'info');
		$this->Common->addFlashMessage('I am also some info');
		$this->Common->addFlashMessage('I am sth custom', 'custom');

		$result = $this->Common->flash();
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
		$this->Common->addFlashMessage('I am an error', 'error');
		$this->Common->addFlashMessage('I am a warning', 'warning');
		$this->Common->addFlashMessage('I am some info', 'info');
		$this->Common->addFlashMessage('I am also some info');
		$this->Common->addFlashMessage('I am sth custom', 'custom');

		$result = $this->Common->flash(array('warning', 'error'));
		$expected = '<div class="flash-messages flashMessages"><div class="message warning">I am a warning</div><div class="message error">I am an error</div></div>';
		$this->assertEquals($expected, $result);

		$result = $this->Common->flash(array('info'));
		$expected = '<div class="flash-messages flashMessages"><div class="message info">I am some info</div><div class="message info">I am also some info</div></div>';
		$this->assertEquals($expected, $result);

		$result = $this->Common->flash();
		$expected = '<div class="flash-messages flashMessages"><div class="message custom">I am sth custom</div></div>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testMetaCanonical() {
		$is = $this->Common->metaCanonical('/some/url/param1');
		$this->assertEquals('<link href="' . $this->Html->url('/some/url/param1') . '" rel="canonical" />', trim($is));

		$is = $this->Common->metaCanonical('/some/url/param1', true);
		$this->assertEquals('<link href="' . $this->Html->url('/some/url/param1', true) . '" rel="canonical" />', trim($is));
	}

	/**
	 * @return void
	 */
	public function testMetaAlternate() {
		$is = $this->Common->metaAlternate('/some/url/param1', 'de-de', true);
		$this->out(h($is));
		$this->assertEquals('<link href="' . $this->Html->url('/some/url/param1', true) . '" rel="alternate" hreflang="de-de" />', trim($is));

		$is = $this->Common->metaAlternate(array('controller' => 'some', 'action' => 'url'), 'de', true);
		$this->out(h($is));
		$this->assertEquals('<link href="' . $this->Html->url('/some/url', true) . '" rel="alternate" hreflang="de" />', trim($is));

		$is = $this->Common->metaAlternate(array('controller' => 'some', 'action' => 'url'), array('de', 'de-ch'), true);
		$this->out(h($is));
		$this->assertEquals('<link href="' . $this->Html->url('/some/url', true) . '" rel="alternate" hreflang="de" />' . PHP_EOL . '<link href="' . $this->Html->url('/some/url', true) . '" rel="alternate" hreflang="de-ch" />', trim($is));

		$is = $this->Common->metaAlternate(array('controller' => 'some', 'action' => 'url'), array('de' => array('ch', 'at'), 'en' => array('gb', 'us')), true);
		$this->out(h($is));
		$this->assertEquals('<link href="' . $this->Html->url('/some/url', true) . '" rel="alternate" hreflang="de-ch" />' . PHP_EOL .
			'<link href="' . $this->Html->url('/some/url', true) . '" rel="alternate" hreflang="de-at" />' . PHP_EOL .
			'<link href="' . $this->Html->url('/some/url', true) . '" rel="alternate" hreflang="en-gb" />' . PHP_EOL .
			'<link href="' . $this->Html->url('/some/url', true) . '" rel="alternate" hreflang="en-us" />', trim($is));
	}

	/**
	 * @return void
	 */
	public function testEsc() {
		$is = $this->Common->esc('Some Cool Text with <b>Html</b>');
		$this->assertEquals($is, 'Some Cool Text with &lt;b&gt;Html&lt;/b&gt;');

		$is = $this->Common->esc('Some Cool Text' . PHP_EOL . 'with <b>Html</b>');
		$this->assertEquals($is, 'Some Cool Text<br />' . PHP_EOL . 'with &lt;b&gt;Html&lt;/b&gt;');

		$is = $this->Common->esc('Some Cool' . PHP_EOL . '  2 indends and' . PHP_EOL . '     5 indends' . PHP_EOL . 'YEAH');
		$this->assertEquals($is, 'Some Cool<br />' . PHP_EOL . '&nbsp;&nbsp;2 indends and<br />' . PHP_EOL . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;5 indends<br />' . PHP_EOL . 'YEAH');

		$options = array('tabsToSpaces' => 2);
		$is = $this->Common->esc('Some Cool' . PHP_EOL . TB . '1 tab and' . PHP_EOL . TB . TB . '2 tabs' . PHP_EOL . 'YEAH', $options);
		$this->assertEquals($is, 'Some Cool<br />' . PHP_EOL . '&nbsp;&nbsp;1 tab and<br />' . PHP_EOL . '&nbsp;&nbsp;&nbsp;&nbsp;2 tabs<br />' . PHP_EOL . 'YEAH');
	}

	/**
	 * CommonHelperTest::testAsp()
	 *
	 * @return void
	 */
	public function testAsp() {
		$res = $this->Common->asp('House', 2, true);
		$expected = __('Houses');
		$this->assertEquals($expected, $res);

		$res = $this->Common->asp('House', 1, true);
		$expected = __('House');
		$this->assertEquals($expected, $res);
	}

	/**
	 * CommonHelperTest::testSp()
	 *
	 * @return void
	 */
	public function testSp() {
		$res = $this->Common->sp('House', 'Houses', 0, true);
		$expected = __('Houses');
		$this->assertEquals($expected, $res);

		$res = $this->Common->sp('House', 'Houses', 2, true);
		$this->assertEquals($expected, $res);

		$res = $this->Common->sp('House', 'Houses', 1, true);
		$expected = __('House');
		$this->assertEquals($expected, $res);

		$res = $this->Common->sp('House', 'Houses', 1);
		$expected = 'House';
		$this->assertEquals($expected, $res);
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Common);
	}

}
