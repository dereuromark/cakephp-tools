<?php

App::uses('HcardHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('CakeText', 'Utility');
App::uses('View', 'View');

/**
 * A wrapper for HCard
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class HcardHelperTest extends MyCakeTestCase {

	public $Hcard;

	public function setUp() {
		parent::setUp();

		$this->Hcard = new HcardHelper(new View(null));
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('HcardHelper', $this->Hcard);
	}

	public function testAddress() {
		$res = $this->Hcard->address();
		$this->assertTrue(!empty($res));
	}

	public function testAddressFormatHtml() {
		$res = $this->Hcard->addressFormatHtml();
		$this->assertTrue(!empty($res));
	}

}
