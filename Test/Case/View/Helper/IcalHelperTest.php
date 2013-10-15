<?php

App::uses('IcalHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('String', 'Utility');
App::uses('View', 'View');

/**
 * A wrapper for the Ical/Ics calendar lib
 * @uses Tools.IcalLib
 * @see http://www.dereuromark.de/2011/11/21/serving-views-as-files-in-cake2 for details
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.x
 */
class IcalHelperTest extends MyCakeTestCase {

	public $Ical;

	public function setUp() {
		parent::setUp();

		$this->Ical = new IcalHelper(new View(null));
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('IcalHelper', $this->Ical);
	}

	public function testAdd() {
		$data = array(
			'url' => 'http://www.spiegel.de',
			'start' => '2010-10-09 22:23:34',
			'end' => '2010-10-09 23:23:34',
			'summary' => 'xyz',
			'description' => 'xyz hjdhfj dhfäöüp e',
			'organizer' => 'CEO',
			'class' => 'public',
		);
		$res = $this->Ical->add($data);
		$this->assertTrue($res);
	}

	public function testGenerate() {
		$data = array(
			'url' => 'http://www.spiegel.de',
			'start' => '2010-10-09 22:23:34',
			'end' => '2010-10-09 23:23:34',
			'summary' => 'xyz',
			'description' => 'xyz hjdhfj dhfäöüp e',
			'organizer' => 'CEO',
			'class' => 'public',
			'timestamp' => '2010-10-08 22:23:34',
			'id' => String::uuid(),
			'location' => 'München'
		);
		$this->Ical->add($data);
		$this->Ical->add($data);
		$res = $this->Ical->generate();
		//pr($res);
	}

}
