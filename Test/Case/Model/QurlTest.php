<?php

App::uses('Qurl', 'Tools.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('Router', 'Routing');

class QurlTest extends MyCakeTestCase {

	public $Qurl = null;

	public $fixtures = array('plugin.tools.qurl');

	public function setUp() {
		parent::setUp();

		$this->Qurl = ClassRegistry::init('Tools.Qurl');
	}

	public function testQurlInstance() {
		$this->assertInstanceOf('Qurl', $this->Qurl);
	}

	public function testGenerate() {
		$url = Router::url(array('admin' => false, 'plugin' => 'tools', 'controller' => 'qurls', 'action' => 'go'), true);

		$res = $this->Qurl->url(array('controller' => 'test', 'action' => 'foo', 'bar'), array('note' => 'x'));
		$this->assertTrue(is_string($res) && !empty($res));
		$this->assertTrue(strpos($res, $url) === 0);

		$res = $this->Qurl->url('/test/foo/bar');
		$this->assertTrue(is_string($res) && !empty($res));
	}

	public function testUse() {
		$key = $this->Qurl->generate(array('controller' => 'test', 'action' => 'foo', 'bar'), array('note' => 'x'));
		$res = $this->Qurl->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));
		$this->assertSame('x', $res['Qurl']['note']);
		$this->assertTrue(is_array($res['Qurl']['content']));

		$key = $this->Qurl->generate('/test/foo/bar');
		$res = $this->Qurl->translate($key);
		$this->assertTrue(is_array($res) && !empty($res));

		$res = $this->Qurl->translate('foobar');
		$this->assertFalse($res);
	}

	//TODO

}
