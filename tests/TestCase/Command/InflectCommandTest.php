<?php
declare(strict_types = 1);

namespace Tools\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Tools\Command\InflectCommand;

/**
 * Tools\Command\InflectCommand Test Case
 */
#[UsesClass(InflectCommand::class)]
class InflectCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->loadPlugins(['Tools']);
		//$this->useCommandRunner();
	}

	/**
	 * Test execute method
	 *
	 * @return void
	 */
	public function testExecute(): void {
		$this->exec('inflect FooBar all');

		$output = $this->_out->messages();
		$expected = [
			0 => 'FooBar',
			1 => 'Pluralized form            : FooBars',
			2 => 'Singular form              : FooBar',
			3 => 'CamelCase form             : FooBar',
			4 => 'under_scored_form          : foo_bar',
			5 => 'Human Readable Group form  : FooBar',
			6 => 'table_names form           : foo_bars',
			7 => 'Cake Model Class form      : FooBar',
			8 => 'variableForm               : fooBar',
			9 => 'Dasherized-form            : foo-bar',
		];
		$this->assertSame($expected, $output);
	}

}
