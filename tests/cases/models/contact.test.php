<?php

App::import('Model', 'Tools.Contact');

class ContactTestCase extends CakeTestCase {
	var $Contact = null;
	//var $fixtures = array('app.code_key');

	function startTest() {
		$this->Contact =& ClassRegistry::init('Contact');
	}

	function testContactInstance() {
		$this->assertTrue(is_a($this->Contact, 'Contact'));
	}


	//TODO


}
?>