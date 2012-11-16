<?php

//App::uses('ContactForm', 'Tools.Model');

class ContactFormTest extends CakeTestCase {
	public $ContactForm = null;
	//public $fixtures = array('app.code_key');

	public function setUp() {
		$this->ContactForm = ClassRegistry::init('Tools.ContactForm');
	}

	public function testContactInstance() {
		$this->assertTrue(is_a($this->ContactForm, 'ContactForm'));
	}


	//TODO


}

