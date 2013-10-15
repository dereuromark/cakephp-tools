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

	public function testBadge() {
		$res = $this->Flattr->badge($this->uid, array());
		//echo $res;
		$this->assertTrue(!empty($res));
	}

	public function testBadgeWithOptions() {
		$options = array('dsc' => 'Eine Beschreibung', 'lng' => 'de_DE', 'tags' => array('Spende', 'Geld', 'Hilfe'));

		$res = $this->Flattr->badge($this->uid, $options);
		//echo $res;
		$this->assertTrue(!empty($res));
	}
}
