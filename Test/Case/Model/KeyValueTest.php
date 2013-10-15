<?php
App::uses('KeyValue', 'Tools.Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * KeyValue Test Case
 *
 */
class KeyValueTest extends MyCakeTestCase {

	public $KeyValue;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array('plugin.tools.key_value');

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->KeyValue = ClassRegistry::init('Tools.KeyValue');
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->KeyValue);

		parent::tearDown();
	}

	public function testInstance() {
		$this->assertInstanceOf('KeyValue', $this->KeyValue);
	}

}
