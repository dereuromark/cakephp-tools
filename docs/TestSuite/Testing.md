# Useful TestSuite additions

Let's you test even faster.

## ToolsTestTrait

This trait adds the following methods to your test suite:

### _osFix()

In case you need to format certain os specific files to "\n" before comparing
them as strings.

### debug()

This is very useful when debugging certain tests when writing them.

```php
$result = $this->get($id);
$this->debug($result);

$this->assertSomething(...);
```
Here the debug statement is harmless by default. Only when you run phpunit with `-v` or `-vv`,
additional debug output is printed to the screen.

By default this trait is attached to IntegrationTestCase, TestCase and ConsoleOutput.

## IntegrationTestCase

You can directly pass an array as URL now:
```php
$this->post(array('controller' => 'ControllerName', ...), $data);
```

Also see the above trait features.

## TestCase
`assertNotWithinMargin()` as the opposite of `assertWithinMargin()` is available.

Also see the above trait features.

## ConsoleOutput
By default, this class captures the output to stderr or stdout internally in an array.

```php
namespace App\Test\TestCase\Shell;

use App\Shell\FooBarShell;
use Cake\Console\ConsoleIo;
use Tools\TestSuite\ConsoleOutput;
use Tools\TestSuite\TestCase;

class FooBarShellTest extends TestCase {

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->Shell = $this->getMock(
			'App\Shell\FooBarShell',
			['in', '_stop'],
			[$io]
		);
	}
```
Note that we mock the `in` and `_stop` methods, though, to allow handling those by mocking them out in the test cases.

You can afterwards check on the output:
```php
$this->Shell->runCommand(['baz']);

$output = $this->err->output();
$this->assertEmpty($output);

$output = $this->out->output();
$expected = 'FooBars';
$this->assertContains($expected, $output);
```

Also see the above trait features. By using `-v` or `-vv` you can directly see any stderr or stdout output on the screen.
Otherwise they will not be displayed automatically.
