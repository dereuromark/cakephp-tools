<?php

App::import('Helper', 'Captcha');
App::import('Helper', 'Html');

/**
 * 2010-06-24 ms
 */
class CaptchaTest extends CakeTestCase {

/**
 * setUp method
 */
	function setUp() {
		$this->Captcha = new CaptchaHelper();
		$this->Captcha->Html = new HtmlHelper();
	}

	/** TODO **/

	function testXXX() {
		//TODO
	}


/**
 * tearDown method
 */
	function tearDown() {
		unset($this->Captcha);
	}
}
?>