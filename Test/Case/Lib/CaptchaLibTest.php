<?php

App::uses('CaptchaLib', 'Tools.Lib');
App::uses('File', 'Utility');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * 2010-09-10 ms
 */
class CaptchaLibTest extends MyCakeTestCase {

	public function setUp() {
		$this->Brita = new CaptchaLib();
	}

	public function tearDown() {
		unset($this->Brita);
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
		pr($res);
		$this->assertTrue(strlen($res) == 40);
	}



}
