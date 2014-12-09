<?php
namespace Tools\Test\TestCase\Shell;

use Tools\Shell\InflectShell;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class TestCompletionStringOutput
 *
 */
class TestInflectOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 */
class InflectShellTest extends TestCase {

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new TestInflectOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Tools\Shell\InflectShell',
			['in', 'err', '_stop'],
			[$io]
		);
	}

	/**
	 * tearDown
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Shell);
	}

	/**
	 * test that the startup method supresses the shell header
	 *
	 * @return void
	 */
	public function testMain() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('FooBar'));

		$this->Shell->runCommand(['pluralize']);
		$output = $this->out->output;
		$expected = 'FooBars';
		$this->assertContains($expected, $output);

		$this->Shell->runCommand(['underscore']);
		$output = $this->out->output;
		$expected = 'foo_bar';
		$this->assertContains($expected, $output);

		$this->Shell->runCommand(['dasherize']);
		$output = $this->out->output;
		$expected = 'foo-bar';
		$this->assertContains($expected, $output);

		$this->Shell->runCommand(['slug']);
		$output = $this->out->output;
		$expected = 'foo-bar';
		$this->assertContains($expected, $output);

		$this->Shell->runCommand(['tableize']);
		$output = $this->out->output;
		$expected = 'foo_bar';
		$this->assertContains($expected, $output);
	}

}
