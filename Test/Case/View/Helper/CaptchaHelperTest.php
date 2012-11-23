<?php

App::uses('CaptchaHelper', 'Tools.View/Helper');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');
App::uses('Controller', 'Controller');

/**
 * 2010-06-24 ms
 */
class CaptchaHelperTest extends CakeTestCase {

	public function setUp() {
		$this->Captcha = new CaptchaHelper(new View(new Controller(new CakeRequest, new CakeResponse)));
		$this->Captcha->Html = new HtmlHelper(new View(null));
	}

	public function tearDown() {
		unset($this->Captcha);
	}


	/**
	 * 2011-11-15 ms
	 */
	public function testFields() {
		$is = $this->Captcha->active();
		pr(h($is));

		$is = $this->Captcha->passive();
		pr(h($is));



		$is = $this->Captcha->captcha('SomeModelName');
		pr(h($is));
	}

	public function testDataInsideHelper() {
		debug($this->Captcha->webroot);
		debug($this->Captcha->request->webroot);

		debug($this->Captcha->data);
		debug($this->Captcha->request->data);
	}


}
