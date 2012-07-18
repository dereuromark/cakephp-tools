<?php

App::uses('Qlogin', 'Tools.Model');
App::uses('MyCakeTestCase', 'Tools.Lib');
App::uses('Router', 'Routing');

class QloginTest extends MyCakeTestCase {

	public $Qlogin = null;

	public $fixtures = array('plugin.tools.code_key');

	public function startTest() {
		$this->Qlogin = ClassRegistry::init('Tools.Qlogin');
	}

	public function testQloginInstance() {
		$this->assertTrue(is_a($this->Qlogin, 'Qlogin'));
	}

	public function testGenerate() {
		$url = Router::url(array('admin'=>'', 'plugin'=>'tools', 'controller'=>'qlogin', 'action'=>'go'), true).'/';
		pr($url);
		$this->assertIsNotEmpty($url);

		$res = $this->Qlogin->url(array('controller'=>'test', 'action'=>'foo', 'bar'), 1);
		pr($res);
		$this->assertTrue(is_string($res) && !empty($res));
		$this->assertTrue(strpos($res, $url) === 0);

		$res = $this->Qlogin->url('/test/foo/bar', 2);
		pr($res);
		$this->assertTrue(is_string($res) && !empty($res));
	}

	public function testUse() {
		$key = $this->Qlogin->generate(array('controller'=>'test', 'action'=>'foo', 'bar'), 1);
		$res = $this->Qlogin->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$key = $this->Qlogin->generate('/test/foo/bar', 2);
		$res = $this->Qlogin->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$res = $this->Qlogin->translate('foobar');
		$this->assertFalse($res);
	}

	//TODO


}
