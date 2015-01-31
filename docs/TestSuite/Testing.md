# Useful TestSuite additions

Let's you test even faster.

## ToolsTestTrait

This trait adds the following methods to your test suite:

### osFix()

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

Tip: This verbose debug feature is best used in combination with `--filter testMethodToTest`, as
otherwise there might be too much output on the screen. So better filter down to the actual method
you are currently working on or debugging:
```
php phpunit.phar --filter testFooBar /path/to/SomeTest.php -vv
```

### isDebug()
With this method you can apply additional testing code if a `--debug` flag has been set.

As an example you can by default mock out some API call, but with the debug flat set use
the real API connection (for local testing). This way you can quickly confirm that the API
connection is not only still working in simulated (mocking) the way it used to, but also
that it's still the real deal.
```php
$this->Api = new ApiClass();

if (!$this->isDebug()) {
	$this->Api = $this->getMock('ApiClass');
	$this->Api->expects(...)->...;
}
```


## IntegrationTestCase

See the above trait features.

For details on this see [Shim plugin](https://github.com/dereuromark/cakephp-shim).

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
