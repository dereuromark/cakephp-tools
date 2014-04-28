<?php
namespace Tools\Test\TestCase\Console\Command;

use Tools\Console\Command\WhitespaceShell;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class TestCompletionStringOutput
 *
 */
class TestWhitespaceOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 */
class WhitespaceShellTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->out = new TestWhitespaceOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Tools\Console\Command\WhitespaceShell',
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
	public function testClean() {
		$this->Shell->expects($this->any())->method('in')
			->will($this->returnValue('y'));

		$content = PHP_EOL . ' <?php echo $foo;' . PHP_EOL . '?> ' . PHP_EOL . PHP_EOL;
		file_put_contents(TMP . 'Foo.php', $content);
		$this->Shell->runCommand(['clean', TMP]);
		$output = $this->out->output;

		$this->assertTextContains('Found 1 files.', $output);
		$this->assertTextContains('found 1 leading, 1 trailing ws', $output);
		$this->assertTextContains('fixed 1 leading, 1 trailing ws', $output);

		$output = file_get_contents(TMP . 'Foo.php');
		$expected = '<?php echo $foo;' . PHP_EOL . '?>';

		unlink(TMP . 'Foo.php');
		$this->assertEquals($expected, $output);
	}

}
