<?php

App::uses('CommonHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 */
class CommonHelperTest extends MyCakeTestCase {

	public $Common;

	public function setUp() {
		parent::setUp();

		$this->Common = new CommonHelper(new View(null));
	}

	/**
	 */
	public function testMetaCanonical() {
		$is = $this->Common->metaCanonical('/some/url/param1');
		$this->out(h($is));
		$this->assertEquals('<link href="'.$this->Common->url('/some/url/param1', true).'" rel="canonical" />', trim($is));
	}

	/**
	 */
	public function testMetaAlternate() {
		$is = $this->Common->metaAlternate('/some/url/param1', 'de-de');
		$this->out(h($is));
		$this->assertEquals('<link href="' . FULL_BASE_URL.$this->Common->url('/some/url/param1').'" rel="alternate" hreflang="de-de" />', trim($is));

		$is = $this->Common->metaAlternate(array('controller'=>'some', 'action'=>'url'), 'de', true);
		$this->out(h($is));
		$this->assertEquals('<link href="' . FULL_BASE_URL.$this->Common->url('/some/url').'" rel="alternate" hreflang="de" />', trim($is));

		$is = $this->Common->metaAlternate(array('controller'=>'some', 'action'=>'url'), array('de', 'de-ch'), true);
		$this->out(h($is));
		$this->assertEquals('<link href="' . FULL_BASE_URL.$this->Common->url('/some/url').'" rel="alternate" hreflang="de" />'.PHP_EOL.'<link href="' . FULL_BASE_URL.$this->Common->url('/some/url').'" rel="alternate" hreflang="de-ch" />', trim($is));

		$is = $this->Common->metaAlternate(array('controller'=>'some', 'action'=>'url'), array('de' => array('ch', 'at'), 'en'=>array('gb', 'us')), true);
		$this->out(h($is));
		$this->assertEquals('<link href="' . FULL_BASE_URL.$this->Common->url('/some/url').'" rel="alternate" hreflang="de-ch" />'.PHP_EOL.
			'<link href="' . FULL_BASE_URL.$this->Common->url('/some/url').'" rel="alternate" hreflang="de-at" />'.PHP_EOL.
			'<link href="' . FULL_BASE_URL.$this->Common->url('/some/url').'" rel="alternate" hreflang="en-gb" />'.PHP_EOL.
			'<link href="' . FULL_BASE_URL.$this->Common->url('/some/url').'" rel="alternate" hreflang="en-us" />', trim($is));
	}

	/**
	 */
	public function testEsc() {
		$is = $this->Common->esc('Some Cool Text with <b>Html</b>');
		$this->assertEquals($is, 'Some Cool Text with &lt;b&gt;Html&lt;/b&gt;');

		$is = $this->Common->esc('Some Cool Text'.PHP_EOL.'with <b>Html</b>');
		$this->assertEquals($is, 'Some Cool Text<br />'.PHP_EOL.'with &lt;b&gt;Html&lt;/b&gt;');

		$is = $this->Common->esc('Some Cool'.PHP_EOL.'  2 indends and'.PHP_EOL.'     5 indends'.PHP_EOL.'YEAH');
		$this->assertEquals($is, 'Some Cool<br />'.PHP_EOL.'&nbsp;&nbsp;2 indends and<br />'.PHP_EOL.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;5 indends<br />'.PHP_EOL.'YEAH');

		$options = array('tabsToSpaces'=>2);
		$is = $this->Common->esc('Some Cool'.PHP_EOL.TB.'1 tab and'.PHP_EOL.TB.TB.'2 tabs'.PHP_EOL.'YEAH', $options);
		$this->assertEquals($is, 'Some Cool<br />'.PHP_EOL.'&nbsp;&nbsp;1 tab and<br />'.PHP_EOL.'&nbsp;&nbsp;&nbsp;&nbsp;2 tabs<br />'.PHP_EOL.'YEAH');

	}

	public function testAsp() {
		$res = $this->Common->asp('House', 2, true);
		$expected = __('Houses');
		$this->assertEquals($expected, $res);

		$res = $this->Common->asp('House', 1, true);
		$expected = __('House');
		$this->assertEquals($expected, $res);
	}

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
