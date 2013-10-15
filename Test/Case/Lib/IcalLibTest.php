<?php

App::uses('IcalLib', 'Tools.Lib');

class IcalLibTest extends CakeTestCase {

	public $file;

	public function setUp() {
		parent::setUp();

		$this->Ical = new IcalLib();

		$this->file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'ics' . DS . 'basic.ics';
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Ical);
	}

	public function testObject() {
		$this->assertTrue(is_object($this->Ical));
	}

/** building **/
	// see IcalHelper() for this

/** parsing **/

	public function testParse() {

		$is = $this->Ical->parse($this->file);
		$this->assertTrue(!empty($is));
	}

	public function testCalendarInfos() {

		$is = $this->Ical->parse($this->file);
		$is = $this->Ical->getCalendarInfos();
		//pr($is);
		$this->assertTrue(!empty($is));
	}

	public function testEvents() {

		$is = $this->Ical->parse($this->file);
		$is = $this->Ical->getEvents();
		//pr($is);
		$this->assertTrue(!empty($is));
	}

	public function testTodos() {

		$is = $this->Ical->parse($this->file);
		$is = $this->Ical->getTodos();
		//debug($is).BR;
		$this->assertEmpty($is);
	}

	public function testEventsAsList() {

		$is = $this->Ical->parse($this->file);
		$is = $this->Ical->getEventsAsList();
		foreach ($is as $i => $val) {
			//echo date(FORMAT_NICE_YMD, $i).': '.h($val).BR;
		}
		$this->assertTrue(!empty($is));
	}

}
