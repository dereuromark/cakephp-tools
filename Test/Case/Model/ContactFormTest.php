<?php

//App::uses('ContactForm', 'Tools.Model');

class ContactFormTest extends CakeTestCase {

	public $ContactForm = null;
	//public $fixtures = array('app.code_key');

	public function setUp() {
		parent::setUp();

		$this->ContactForm = ClassRegistry::init('Tools.ContactForm');
	}

	public function testContactInstance() {
		$this->assertInstanceOf('ContactForm', $this->ContactForm);
	}

	//TODO

}
