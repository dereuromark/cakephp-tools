<?php

namespace Tools\Test\Model\Table;

use Cake\ORM\TableRegistry;
use Tools\TestSuite\TestCase;

class TokensTableTest extends TestCase {

	/**
	 * @var \Tools\Model\Table\TokensTable;
	 */
	public $Tokens;

	/**
	 * @var array
	 */
	public $fixtures = ['plugin.Tools.Tokens'];

	public function setUp() {
		parent::setUp();

		$this->Tokens = TableRegistry::get('Tools.Tokens');
	}

	public function testTokenInstance() {
		$this->assertInstanceOf('Tools\Model\Table\TokensTable', $this->Tokens);
	}

	public function testGenerateKey() {
		$key = $this->Tokens->generateKey(4);
		//pr($key);
		$this->assertTrue(!empty($key) && strlen($key) === 4);
	}

	public function testNewKeySpendKey() {
		$key = $this->Tokens->newKey('test', null, null, 'xyz');
		$this->assertTrue(!empty($key));

		$res = $this->Tokens->useKey('test', $key);
		//pr($res);
		$this->assertTrue(!empty($res));

		$res = $this->Tokens->useKey('test', $key);
		//pr($res);
		$this->assertTrue(!empty($res) && !empty($res['used']));

		$res = $this->Tokens->useKey('test', $key . 'x');
		$this->assertFalse($res);

		$res = $this->Tokens->useKey('testx', $key);
		$this->assertFalse($res);
	}

	public function testGarbageCollector() {
		$data = [
			'created' => date(FORMAT_DB_DATETIME, time() - 3 * MONTH),
			'type' => 'y',
			'key' => 'x'
		];
		$entity = $this->Tokens->newEntity($data, ['validate' => false]);
		$this->Tokens->save($entity);
		$count = $this->Tokens->find('count');
		$this->Tokens->garbageCollector();
		$count2 = $this->Tokens->find('count');
		$this->assertTrue($count > $count2);
	}

}
