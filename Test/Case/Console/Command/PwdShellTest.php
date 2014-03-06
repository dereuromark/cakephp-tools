<?php

App::uses('PwdShell', 'Tools.Console/Command');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('TestConsoleOutput', 'Tools.TestSuite');

class PwdShellTest extends MyCakeTestCase {

	public $PwdShell;

	/**
	 * PwdShellTest::setUp()
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$output = new TestConsoleOutput();
		$error = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$input = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->PwdShell = new PwdShell($output, $error, $input);
	}

	/**
	 * PwdShellTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->PwdShell));
		$this->assertInstanceOf('PwdShell', $this->PwdShell);
	}

	/**
	 * PwdShellTest::testHash()
	 *
	 * @return void
	 */
	public function testHash() {
		$this->PwdShell->stdin->expects($this->at(0))
			->method('read')
			->will($this->returnValue('123'));

		$this->PwdShell->startup();
		$this->PwdShell->hash();

		$output = $this->PwdShell->stdout->output();
		$this->assertNotEmpty($output);
	}

}
