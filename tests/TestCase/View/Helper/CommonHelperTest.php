<?php

namespace Tools\TestCase\View\Helper;

use Cake\Routing\Router;
use Cake\View\View;
use Tools\TestSuite\TestCase;
use Tools\View\Helper\CommonHelper;

/**
 * CommonHelper tests
 */
class CommonHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\CommonHelper
	 */
	public $Common;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Router::reload();
		$View = new View(null);
		$this->Common = new CommonHelper($View);
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
		$result = $this->Common->metaName('foo', [1, 2, 3]);
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
		$expected = '<meta lang="deu" name="description" content="foo"/>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * CommonHelperTest::testMetaKeywords()
	 *
	 * @return void
	 */
	public function testMetaKeywords() {
		$result = $this->Common->metaKeywords('foo bar', 'deu');
		$expected = '<meta lang="deu" name="keywords" content="foo bar"/>';
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
	 * @return void
	 */
	public function testMetaCanonical() {
		$is = $this->Common->metaCanonical('/some/url/param1');
		$this->assertEquals('<link href="' . $this->Common->Url->build('/some/url/param1') . '" rel="canonical"/>', trim($is));

		$is = $this->Common->metaCanonical('/some/url/param1', true);
		$this->assertEquals('<link href="' . $this->Common->Url->build('/some/url/param1', true) . '" rel="canonical"/>', trim($is));
	}

	/**
	 * @return void
	 */
	public function testMetaAlternate() {
		$is = $this->Common->metaAlternate('/some/url/param1', 'de-de', true);
		$this->assertEquals('<link href="' . $this->Common->Url->build('/some/url/param1', true) . '" rel="alternate" hreflang="de-de"/>', trim($is));

		$is = $this->Common->metaAlternate(['controller' => 'some', 'action' => 'url'], 'de', true);
		$this->assertEquals('<link href="' . $this->Common->Url->build('/some/url', true) . '" rel="alternate" hreflang="de"/>', trim($is));

		$is = $this->Common->metaAlternate(['controller' => 'some', 'action' => 'url'], ['de', 'de-ch'], true);
		$this->assertEquals('<link href="' . $this->Common->Url->build('/some/url', true) . '" rel="alternate" hreflang="de"/>' . PHP_EOL . '<link href="' . $this->Common->Url->build('/some/url', true) . '" rel="alternate" hreflang="de-ch"/>', trim($is));

		$is = $this->Common->metaAlternate(['controller' => 'some', 'action' => 'url'], ['de' => ['ch', 'at'], 'en' => ['gb', 'us']], true);
		$this->assertEquals('<link href="' . $this->Common->Url->build('/some/url', true) . '" rel="alternate" hreflang="de-ch"/>' . PHP_EOL .
			'<link href="' . $this->Common->Url->build('/some/url', true) . '" rel="alternate" hreflang="de-at"/>' . PHP_EOL .
			'<link href="' . $this->Common->Url->build('/some/url', true) . '" rel="alternate" hreflang="en-gb"/>' . PHP_EOL .
			'<link href="' . $this->Common->Url->build('/some/url', true) . '" rel="alternate" hreflang="en-us"/>', trim($is));
	}

	/**
	 * CommonHelperTest::testAsp()
	 *
	 * @return void
	 */
	public function testAsp() {
		$res = $this->Common->asp('House', 2, true);
		$expected = __d('tools', 'Houses');
		$this->assertEquals($expected, $res);

		$res = $this->Common->asp('House', 1, true);
		$expected = __d('tools', 'House');
		$this->assertEquals($expected, $res);
	}

	/**
	 * CommonHelperTest::testSp()
	 *
	 * @return void
	 */
	public function testSp() {
		$res = $this->Common->sp('House', 'Houses', 0, true);
		$expected = __d('tools', 'Houses');
		$this->assertEquals($expected, $res);

		$res = $this->Common->sp('House', 'Houses', 2, true);
		$this->assertEquals($expected, $res);

		$res = $this->Common->sp('House', 'Houses', 1, true);
		$expected = __d('tools', 'House');
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
