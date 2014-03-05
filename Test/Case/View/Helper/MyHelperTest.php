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
	 * MyHelperTest::testBeforeRender()
	 *
	 * @return void
	 */
	public function testBeforeRender() {
		$this->MyHelper->beforeRender('');
	}

	/**
	 * MyHelperTest::testAfterLayout()
	 *
	 * @return void
	 */
	public function testAfterLayout() {
		$this->MyHelper->afterLayout('');
	}

	/**
	 * MyHelperTest::testUrl()
	 *
	 * @return void
	 */
	public function testUrl() {
		$result = $this->MyHelper->url();
		$this->assertEquals('/', $result);

		$result = $this->MyHelper->url(null, true);
		$this->assertEquals(Configure::read('App.fullBaseUrl') . '/', $result);
	}

	/**
	 * MyHelperTest::testAssetUrl()
	 *
	 * @return void
	 */
	public function testAssetUrl() {
		$result = $this->MyHelper->assetUrl('/some/string');
		$this->assertEquals('/some/string', $result);

		Configure::write('App.assetBaseUrl', 'http://cdn.domain.com');
		$result = $this->MyHelper->assetUrl('/some/string');
		$this->assertEquals(Configure::read('App.assetBaseUrl') . '/some/string', $result);

		$result = $this->MyHelper->assetUrl('/some/string', array('ext' => 'json'));
		$this->assertEquals(Configure::read('App.assetBaseUrl') . '/some/string.json', $result);

		$result = $this->MyHelper->assetUrl('some/string', array('pathPrefix' => 'foo/'));
		$this->assertEquals(Configure::read('App.assetBaseUrl') . '/foo/some/string', $result);
	}

}

class MyHtmlHelper extends MyHelper {

	protected $_tags = array(
		'image' => '<img src="%s" %s/>',
	);

}