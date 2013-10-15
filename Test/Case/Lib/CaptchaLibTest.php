<?php

App::uses('CaptchaLib', 'Tools.Lib');
App::uses('File', 'Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 */
class CaptchaLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->Captcha = new CaptchaLib();
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Captcha);
	}

	public function testBuildHash() {
		$data = array(
			'captcha_time' => time(),
			'captcha' => '2'
		);
		$options = array(
			'salt' => 'xyz',
			'checkIp' => true,
			'checkSession' => true
		);
		$res = CaptchaLib::buildHash($data, $options);
		//pr($res);
		$this->assertTrue(strlen($res) === 40);
	}

}
