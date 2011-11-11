<?php

App::import('Helper', 'Tools.Captcha');
App::import('Helper', 'Html');
App::uses('View', 'View');

/**
 * 2010-06-24 ms
 */
class CaptchaHelperTest extends CakeTestCase {

/**
 * setUp method
 */
	public function setUp() {
		$this->Captcha = new CaptchaHelper(new View(null));
		$this->Captcha->Html = new HtmlHelper(new View(null));
	}

	/** TODO **/

	public function testXXX() {
		//TODO
	}


/**
 * tearDown method
 */
	public function tearDown() {
		unset($this->Captcha);
	}
}

