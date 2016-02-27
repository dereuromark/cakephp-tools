<?php

namespace Tools\TestCase\TestSuite;

use Tools\TestSuite\TestCase;

class ToolsTestTraitTest extends TestCase {

	public function setUp() {
		parent::setUp();

		$this->serverArgBackup = !empty($_SERVER['argv']) ? $_SERVER['argv'] : null;
		$_SERVER['argv'] = [];
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

		$_SERVER['argv'] = ['--debug'];
		$result = $this->isDebug();
		$this->assertTrue($result);
	}

	/**
	 * ToolsTestTraitTest::testIsVerbose()
	 *
	 * @return void
	 */
	public function testIsVerbose() {
		$_SERVER['argv'] = ['--debug'];
		$result = $this->isVerbose();
		$this->assertFalse($result);

		$_SERVER['argv'] = ['-v'];
		$result = $this->isVerbose();
		$this->assertTrue($result);

		$_SERVER['argv'] = ['-vv'];
		$result = $this->isVerbose();
		$this->assertTrue($result);

		$_SERVER['argv'] = ['-v', '-vv'];
		$result = $this->isVerbose();
		$this->assertTrue($result);

		$_SERVER['argv'] = ['-v'];
		$result = $this->isVerbose(true);
		$this->assertFalse($result);

		$_SERVER['argv'] = ['-vv'];
		$result = $this->isVerbose(true);
		$this->assertTrue($result);

		$_SERVER['argv'] = ['-v', '-vv'];
		$result = $this->isVerbose(true);
		$this->assertTrue($result);
	}

}
