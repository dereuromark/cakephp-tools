<?php

if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);

}

define('QR_TEST_STRING', 'Some Text to Translate');
define('QR_TEST_STRING_UTF', 'Some äöü Test String with $ and @ etc');

App::uses('HtmlHelper', 'View/Helper');
App::uses('QrCodeHelper', 'Tools.View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * QrCode Test Case
 *
 * @package cake.tests
 * @subpackage cake.tests.cases.libs.view.helpers
 */
class QrCodeHelperTest extends MyCakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	public function setUp() {
		$this->QrCode = new QrCodeHelper(new View(null));
		$this->QrCode->Html = new HtmlHelper(new View(null));
	}


	/**
	 * @access public
	 * @return void
	 * 2009-07-30 ms
	 */
	public function testSetSize() {
		$is = $this->QrCode->setSize(1000);
		pr($this->QrCode->debug());
		$this->assertFalse($is);

		$is = $this->QrCode->setSize(300);
		pr($this->QrCode->debug());
		$this->assertTrue($is);



	}


	/**
	 * @access public
	 * @return void
	 * 2009-07-30 ms
	 */
	public function testImages() {
		$this->QrCode->reset();

		$is = $this->QrCode->image(QR_TEST_STRING);
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->QrCode->image(QR_TEST_STRING_UTF);
		echo $is;
		$this->assertTrue(!empty($is));

		$is = $this->QrCode->image('');
		echo $is;
		$this->assertTrue(!empty($is));

	}


	/**
	 * @access public
	 * @return void
	 * 2009-07-30 ms
	 */
	public function testImagesModified() {
		$this->QrCode->reset();
		$this->QrCode->setLevel('H');
		$is = $this->QrCode->image(QR_TEST_STRING);
		echo $is;
		$this->assertTrue(!empty($is));

		$this->QrCode->reset();
		$this->QrCode->setLevel('H', 20);
		$is = $this->QrCode->image(QR_TEST_STRING_UTF);
		echo $is;
		$this->assertTrue(!empty($is));


		$this->QrCode->reset();
		$this->QrCode->setSize(300);
		$this->QrCode->setLevel('L', 1);
		$is = $this->QrCode->image(QR_TEST_STRING);
		echo $is;
		pr($this->QrCode->debug());
		$this->assertTrue(!empty($is));

		$this->QrCode->reset();
		$this->QrCode->setSize(300);
		$this->QrCode->setLevel('H', 1);
		$is = $this->QrCode->image(QR_TEST_STRING);
		echo $is;
		pr($this->QrCode->debug());
		$this->assertTrue(!empty($is));
	}

	public function testSpecialImages() {
		$this->QrCode->reset();
		$this->QrCode->setSize(300);
		$this->QrCode->setLevel('H');
		echo 'CARD'.BR;
		$string = $this->QrCode->formatCard(array(
			'name' => 'Maier,Susanne',
			'tel' => array('0111222123', '012224344'),
			'nickname' => 'sssnick',
			'birthday' => '1999-01-03',
			'address' => 'Bluetenweg 11, 85375, Neufahrn, Deutschland',
			'email' => 'test@test.de',
			'note' => 'someNote;someOtherNote :)',
			'url' => 'http://www.some_url.de'
		));
		$is = $this->QrCode->image($string);
		echo $is;
		$this->assertTrue(!empty($is));
	}

 	/**
 	 * 2011-07-19 ms
 	 */
	public function testBitcoin() {
		$this->QrCode->reset();
		$this->QrCode->setSize(100);
		$this->QrCode->setLevel('H');
		echo 'CARD'.BR;
		$string = $this->QrCode->format('bitcoin', '18pnDgDYFMAKsHTA3ZqyAi6t8q9ztaWWXt');
		$is = $this->QrCode->image($string);
		echo $is;
		$this->assertTrue(!empty($is));

	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	public function tearDown() {
		unset($this->QrCode);
	}
}

