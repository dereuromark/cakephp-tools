<?php

namespace Tools\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Shim\TestSuite\ConsoleOutput;
use Shim\TestSuite\TestCase;
use Tools\Shell\InflectShell;

class InflectShellTest extends TestCase {

	/**
	 * @var \Tools\Shell\InflectShell
	 */
	protected $Shell;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $out;

	/**
	 * @var \Shim\TestSuite\ConsoleOutput
	 */
	protected $err;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Shell = $this->getMockBuilder(InflectShell::class)
			->setMethods(['in', '_stop'])
			->setConstructorArgs([$io])
			->getMock();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset($this->Shell);
	}

	/**
	 * @return void
	 */
	public function testMain() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('FooBar'));

		$this->Shell->runCommand(['pluralize']);
		$output = $this->out->output();
		$expected = 'FooBars';
		$this->assertStringContainsString($expected, $output);

		$this->Shell->runCommand(['underscore']);
		$output = $this->out->output();
		$expected = 'foo_bar';
		$this->assertStringContainsString($expected, $output);

		$this->Shell->runCommand(['dasherize']);
		$output = $this->out->output();
		$expected = 'foo-bar';
		$this->assertStringContainsString($expected, $output);

		$this->Shell->runCommand(['slug']);
		$output = $this->out->output();
		$expected = 'foo-bar';
		$this->assertStringContainsString($expected, $output);

		$this->Shell->runCommand(['tableize']);
		$output = $this->out->output();
		$expected = 'foo_bar';
		$this->assertStringContainsString($expected, $output);
	}

	/**
	 * @return void
	 */
	public function testMainAll() {
		$this->Shell->runCommand(['all', 'Foo Bar']);
		$output = $this->out->output();

		$this->assertStringContainsString('Pluralized form', $output);
		$this->assertStringContainsString('Slugged-form', $output);
	}

}
