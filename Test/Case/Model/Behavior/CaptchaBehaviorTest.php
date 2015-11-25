<?php

App::uses('CaptchaBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class CaptchaBehaviorTest extends MyCakeTestCase {

	public $fixtures = [
		'core.comment'
	];

	public $Comment;

	public function setUp() {
		parent::setUp();

		$this->Comment = ClassRegistry::init('Comment');
		$this->Comment->Behaviors->load('Tools.Captcha', []);
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Comment);
	}

	/**
	 * Test if nothing has been
	 */
	public function testEmpty() {
		$is = $this->Comment->validates();
		//debug($this->Comment->invalidFields());
		$this->assertFalse($is);
	}

	public function testWrong() {
		$data = ['title' => 'xyz', 'captcha' => 'x', 'captcha_hash' => 'y', 'captcha_time' => '123'];
		$this->Comment->set($data);
		$is = $this->Comment->validates();
		//debug($this->Comment->invalidFields());
		$this->assertFalse($is);

		$data = ['title' => 'xyz', 'captcha' => 'x', 'homepage' => '', 'captcha_hash' => 'y', 'captcha_time' => '123'];
		$this->Comment->set($data);
		$is = $this->Comment->validates();
		//debug($this->Comment->invalidFields());
		$this->assertFalse($is);
	}

	public function testInvalid() {
		App::uses('CaptchaLib', 'Tools.Lib');
		$Captcha = new CaptchaLib();
		$hash = $Captcha->buildHash(['captcha' => 2, 'captcha_time' => time() - DAY, ''], CaptchaLib::$defaults);

		$data = ['title' => 'xyz', 'captcha' => '2', 'homepage' => '', 'captcha_hash' => $hash, 'captcha_time' => time() - DAY];
		$this->Comment->set($data);
		$is = $this->Comment->validates();
		//debug($this->Comment->invalidFields());
		//$this->assertTrue($is);

		$Captcha = new CaptchaLib();
		$hash = $Captcha->buildHash(['captcha' => 2, 'captcha_time' => time() + DAY, ''], CaptchaLib::$defaults);

		$data = ['title' => 'xyz', 'captcha' => '2', 'homepage' => '', 'captcha_hash' => $hash, 'captcha_time' => time() + DAY];
		$this->Comment->set($data);
		$is = $this->Comment->validates();
		//debug($this->Comment->invalidFields());
		//$this->assertTrue($is);
	}

	public function testCorrect() {
		App::uses('CaptchaLib', 'Tools.Lib');
		$Captcha = new CaptchaLib();
		$hash = $Captcha->buildHash(['captcha' => 2, 'captcha_time' => time() - 10, ''], CaptchaLib::$defaults);

		$data = ['title' => 'xyz', 'captcha' => '2', 'homepage' => '', 'captcha_hash' => $hash, 'captcha_time' => time() - 10];
		$this->Comment->set($data);
		$is = $this->Comment->validates();
		//debug($this->Comment->invalidFields());
		$this->assertTrue($is);
	}

	//TODO

}
