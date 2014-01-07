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

}

class MyHtmlHelper extends MyHelper {

	protected $_tags = array(
		'image' => '<img src="%s" %s/>',
	);

}