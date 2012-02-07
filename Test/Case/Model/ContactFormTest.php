<?php

//App::import('Model', 'Tools.ContactForm');

class ContactFormTest extends CakeTestCase {
	public $ContactForm = null;
	//public $fixtures = array('app.code_key');

	public function startTest() {
		$this->ContactForm = ClassRegistry::init('Tools.ContactForm');
	}

	public function testContactInstance() {
		$this->assertTrue(is_a($this->ContactForm, 'ContactForm'));
	}


	//TODO


}

