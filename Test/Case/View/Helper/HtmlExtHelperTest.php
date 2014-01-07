<?php

App::uses('HtmlExtHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class HtmlExtHelperTest extends MyCakeTestCase {

	public $Html;

	public function setUp() {
		parent::setUp();

		$this->Html = new HtmlExtHelper(new View(null));
	}

	public function testObject() {
		$this->assertTrue(is_object($this->Html));
		$this->assertInstanceOf('HtmlExtHelper', $this->Html);
	}

	/**
	 * MyHelperTest::testTime()
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
	 * MyHelperTest::testImageFromBlob()
	 *
	 * @return void
	 */
	public function testImageFromBlob() {
		$folder = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS;
		$content = file_get_contents($folder . 'hotel.png');
		$is = $this->Html->imageFromBlob($content);
		$this->assertTrue(!empty($is));
	}

}
