<?php

App::uses('MyHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class MyHelperTest extends MyCakeTestCase {

	public $MyHelper;

	public function setUp() {
		parent::setUp();

		$this->MyHelper = new MyHelper(new View(null));
		$this->Html = new MyHtmlHelper(new View(null));
	}

	/**
	 * MyHelperTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->MyHelper));
		$this->assertInstanceOf('MyHelper', $this->MyHelper);
	}

	/**
	 * MyHelperTest::testLoadHelpers()
	 *
	 * @return void
	 */
	public function testLoadHelpers() {
		$this->skipIf(class_exists('QrCodeHelper'), 'Already loaded');

		$this->assertTrue(!class_exists('QrCodeHelper'));

		$this->MyHelper->loadHelpers(array('Tools.QrCode'));

		$this->assertTrue(class_exists('QrCodeHelper'));
	}

	/**
	 * MyHelperTest::testTime()
	 *
	 * @return void
	 */
	public function testTime() {
		$time = time();
		$is = $this->MyHelper->time($time);

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

class MyHtmlHelper extends MyHelper {

	protected $_tags = array(
		'image' => '<img src="%s" %s/>',
	);

}