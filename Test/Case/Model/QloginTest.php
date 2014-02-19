<?php

App::uses('Qlogin', 'Tools.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('Router', 'Routing');

class QloginTest extends MyCakeTestCase {

	public $Qlogin = null;

	public $fixtures = array('plugin.tools.code_key', 'plugin.tools.token');

	public function setUp() {
		parent::setUp();

		$this->Qlogin = ClassRegistry::init('Tools.Qlogin');
	}

	public function testQloginInstance() {
		$this->assertInstanceOf('Qlogin', $this->Qlogin);
	}

	public function testGenerateDeprecated() {
		$this->Qlogin->generator = 'CodeKey';

		$this->CodeKey = ClassRegistry::init('Tools.CodeKey');
		$count = $this->CodeKey->find('count');

		$url = Router::url(array('admin' => false, 'plugin' => 'tools', 'controller' => 'qlogin', 'action' => 'go'), true) . '/';
		$this->assertTrue(!empty($url));

		$res = $this->Qlogin->url(array('controller' => 'test', 'action' => 'foo', 'bar'), 1);
		$this->assertTrue(is_string($res) && !empty($res));
		$this->assertTrue(strpos($res, $url) === 0);

		$res = $this->Qlogin->url('/test/foo/bar', 2);
		$this->assertTrue(is_string($res) && !empty($res));

		$count2 = $this->CodeKey->find('count');
		$this->assertTrue($count2 > $count);
	}

	public function testUseDeprecated() {
		$this->Qlogin->generator = 'CodeKey';

		$key = $this->Qlogin->generate(array('controller' => 'test', 'action' => 'foo', 'bar'), 1);
		$res = $this->Qlogin->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$key = $this->Qlogin->generate('/test/foo/bar', 2);
		$res = $this->Qlogin->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$res = $this->Qlogin->translate('foobar');
		$this->assertFalse($res);
	}

	public function testGenerate() {
		$url = Router::url(array('admin' => false, 'plugin' => 'tools', 'controller' => 'qlogin', 'action' => 'go'), true) . '/';
		//debug($url);
		$this->assertTrue(!empty($url));

		$res = $this->Qlogin->url(array('controller' => 'test', 'action' => 'foo', 'bar'), 1);
		//debug($res);
		$this->assertTrue(is_string($res) && !empty($res));
		$this->assertTrue(strpos($res, $url) === 0);

		$res = $this->Qlogin->url('/test/foo/bar', 2);
		//debug($res);
		$this->assertTrue(is_string($res) && !empty($res));
	}

	public function testUse() {
		$key = $this->Qlogin->generate(array('controller' => 'test', 'action' => 'foo', 'bar'), 1);
		$res = $this->Qlogin->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$key = $this->Qlogin->generate('/test/foo/bar', 2);
		$res = $this->Qlogin->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$res = $this->Qlogin->translate('foobar');
		$this->assertFalse($res);
	}

}
