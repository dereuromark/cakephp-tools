<?php

App::uses('HtmlExtHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class HtmlExtHelperTest extends MyCakeTestCase {

	public $Html;

	public function setUp() {
		parent::setUp();

		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();
		$this->Html = new HtmlExtHelper(new View(null));
	}

	public function testObject() {
		$this->assertTrue(is_object($this->Html));
		$this->assertInstanceOf('HtmlExtHelper', $this->Html);
	}

	/**
	 * HtmlExtHelperTest::testTime()
	 *
	 * @return void
	 */
	public function testTime() {
		$time = time();
		$is = $this->Html->time($time);

		$time = CakeTime::i18nFormat($time, '%Y-%m-%d %T');
		$expected = '<time datetime="' . $time . '">' . $time . '</time>';
		$this->assertEquals($expected, $is);
	}

	/**
	 * HtmlExtHelperTest::testLinkShim()
	 *
	 * @return void
	 */
	public function testLinkShim() {
		$result = $this->Html->link('foo', '/bar', array('confirm' => 'Confirm me'));
		$expected = '<a href="/bar" onclick="if (confirm(&quot;Confirm me&quot;)) { return true; } return false;">foo</a>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * HtmlExtHelperTest::testImageFromBlob()
	 *
	 * @return void
	 */
	public function testImageFromBlob() {
		$folder = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS;
		$content = file_get_contents($folder . 'hotel.png');
		$is = $this->Html->imageFromBlob($content);
		$this->assertTrue(!empty($is));
	}

	/**
	 * HtmlExtHelperTest::testDefaultUrl()
	 *
	 * @return void
	 */
	public function testDefaultUrl() {
		$result = $this->Html->defaultUrl(array('controller' => 'foo'));
		$this->debug($result);
		$expected = '/foo';
		$this->assertEquals($expected, $result);
	}

	/**
	 * HtmlExtHelperTest::testDefaultLink()
	 *
	 * @return void
	 */
	public function testDefaultLink() {
		$result = $this->Html->defaultLink('Title', array('controller' => 'foo'));
		$this->debug($result);
		$expected = '<a href="/foo">Title</a>';
		$this->assertEquals($expected, $result);

		$result = $this->Html->defaultLink('Title', array('admin' => true, 'controller' => 'foo'));
		$this->debug($result);
		$expected = '<a href="/admin/foo" rel="nofollow">Title</a>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * HtmlExtHelperTest::testCompleteUrl()
	 *
	 * @return void
	 */
	public function testCompleteUrl() {
		$result = $this->Html->completeUrl(array('controller' => 'foo'));
		$expected = '/foo';
		$this->assertEquals($expected, $result);

		$this->Html->request->query = array('x' => 'y');
		$result = $this->Html->completeUrl(array('controller' => 'foo'));
		$expected = '/foo?x=y';
		$this->assertEquals($expected, $result);
	}

	/**
	 * HtmlExtHelperTest::testCompleteLink()
	 *
	 * @return void
	 */
	public function testCompleteLink() {
		$result = $this->Html->completeLink('Title', array('controller' => 'foo'));
		$expected = '<a href="/foo">Title</a>';
		$this->assertEquals($expected, $result);

		$this->Html->request->query = array('x' => 'y');
		$result = $this->Html->completeLink('Title', array('controller' => 'foo'));
		$expected = '<a href="/foo?x=y">Title</a>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * HtmlExtHelperTest::testResetCrumbs()
	 *
	 * @return void
	 */
	public function testResetCrumbs() {
		$this->Html->addCrumb('foo', '/bar');

		$result = $this->Html->getCrumbList();
		$expected = '<ul><li class="first"><a href="/bar">foo</a></li></ul>';
		$this->assertEquals($expected, $result);

		$this->Html->resetCrumbs();

		$result = $this->Html->getCrumbList();
		$this->assertNull($result);
	}

}
