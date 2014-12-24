<?php
namespace Tools\TestCase\TestSuite;

use Tools\TestSuite\TestCase;

class ToolsTestTraitTest extends TestCase {

	public $TestCase;

	public function setUp() {
		parent::setUp();

		$this->serverArgBackup = !empty($_SERVER['argv']) ? $_SERVER['argv'] : null;
		$_SERVER['argv'] = array();
	}

	public function tearDown() {
		parent::tearDown();

		$_SERVER['argv'] = $this->serverArgBackup;
	}

	/**
	 * MimeTest::testOsFix()
	 *
	 * @return void
	 */
	public function testOsFix() {
		$string = "Foo\r\nbar";
		$result = $this->osFix($string);
		$expected = "Foo\nbar";
		$this->assertSame($expected, $result);
	}

	/**
	 * ToolsTestTraitTest::testIsDebug()
	 *
	 * @return void
	 */
	public function testIsDebug() {
		$result = $this->isDebug();
		$this->assertFalse($result);

		$_SERVER['argv'] = array('--debug');
		$result = $this->isDebug();
		$this->assertTrue($result);
	}

	/**
	 * ToolsTestTraitTest::testIsVerbose()
	 *
	 * @return void
	 */
	public function testIsVerbose() {
		$_SERVER['argv'] = array('--debug');
		$result = $this->isVerbose();
		$this->assertFalse($result);

		$_SERVER['argv'] = array('-v');
		$result = $this->isVerbose();
		$this->assertTrue($result);

		$_SERVER['argv'] = array('-vv');
		$result = $this->isVerbose();
		$this->assertTrue($result);

		$_SERVER['argv'] = array('-v', '-vv');
		$result = $this->isVerbose();
		$this->assertTrue($result);

		$_SERVER['argv'] = array('-v');
		$result = $this->isVerbose(true);
		$this->assertFalse($result);

		$_SERVER['argv'] = array('-vv');
		$result = $this->isVerbose(true);
		$this->assertTrue($result);

		$_SERVER['argv'] = array('-v', '-vv');
		$result = $this->isVerbose(true);
		$this->assertTrue($result);
	}

}
