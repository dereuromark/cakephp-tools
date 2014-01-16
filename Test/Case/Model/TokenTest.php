<?php

App::import('Token', 'Tools.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class TokenTest extends MyCakeTestCase {

	public $Token = null;

	public $fixtures = array('plugin.tools.token');

	public function setUp() {
		parent::setUp();

		$this->Token = ClassRegistry::init('Tools.Token');
	}

	public function testTokenInstance() {
		$this->assertInstanceOf('Token', $this->Token);
	}

	public function testGenerateKey() {
		$key = $this->Token->generateKey(4);
		//pr($key);
		$this->assertTrue(!empty($key) && strlen($key) === 4);
	}

	public function testNewKeySpendKey() {
		$key = $this->Token->newKey('test', null, null, 'xyz');
		$this->assertTrue(!empty($key));

		$res = $this->Token->useKey('test', $key);
		//pr($res);
		$this->assertTrue(!empty($res));

		$res = $this->Token->useKey('test', $key);
		//pr($res);
		$this->assertTrue(!empty($res) && !empty($res['Token']['used']));

		$res = $this->Token->useKey('test', $key . 'x');
		$this->assertFalse($res);

		$res = $this->Token->useKey('testx', $key);
		$this->assertFalse($res);
	}

	public function testGarbageCollector() {
		$data = array(
			'created' => date(FORMAT_DB_DATETIME, time() - 3 * MONTH),
			'type' => 'y',
			'key' => 'x'
		);
		$this->Token->create();
		$this->Token->save($data, false);
		$count = $this->Token->find('count');
		$this->Token->garbageCollector();
		$count2 = $this->Token->find('count');
		$this->assertTrue($count > $count2);
	}

}
