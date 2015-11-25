<?php

App::uses('FlattrHelper', 'Tools.View/Helper');
App::uses('HtmlHelper', 'View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');

class FlattrHelperTest extends MyCakeTestCase {

	public $uid;

	public function setUp() {
		parent::setUp();

		$this->Flattr = new FlattrHelper(new View(null));
		$this->Flattr->Html = new HtmlHelper(new View(null));

		$this->uid = '1234';
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('FlattrHelper', $this->Flattr);
	}

	/**
	 * FlattrHelperTest::testBadge()
	 *
	 * @return void
	 */
	public function testBadge() {
		$res = $this->Flattr->badge($this->uid, []);
		$this->assertTrue(!empty($res));
	}

	/**
	 * FlattrHelperTest::testBadgeWithOptions()
	 *
	 * @return void
	 */
	public function testBadgeWithOptions() {
		$options = ['dsc' => 'Eine Beschreibung', 'lng' => 'de_DE', 'tags' => ['Spende', 'Geld', 'Hilfe']];

		$res = $this->Flattr->badge($this->uid, $options);
		$this->assertTrue(!empty($res));
	}

	/**
	 * FlattrHelperTest::testButton()
	 *
	 * @return void
	 */
	public function testButton() {
		$res = $this->Flattr->button('/some/url');
		$this->assertTrue(!empty($res));
	}

}
