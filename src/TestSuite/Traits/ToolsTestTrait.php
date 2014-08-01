<?php
namespace Dereuromark\Tools\TestSuite\Traits;

use Cake\Controller\Controller;
use Cake\Network\Response;
use Cake\Routing\Router;

/**
 * Utility methods for easier testing in CakePHP & PHPUnit
 */
trait ToolsTestTrait {

/**
 * Assert a redirect happened
 *
 * `$actual` can be a string, Controller or Response instance
 *
 * @param  string $expected
 * @param  mixed  $actual
 * @return void
 */
	public function assertRedirect($expected, $actual = null) {
		if ($actual === null) {
			$actual = $this->controller;
		}

		if ($actual instanceof Controller) {
			$actual = $actual->response->location();
		}

		if ($actual instanceof Response) {
			$actual = $actual->location();
		}

		if (empty($actual)) {
			throw new \Exception('assertRedirect: Expected "actual" to be a non-empty string');
		}

		if (is_array($expected)) {
			$expected = Router::url($expected);
		}
		$this->assertEquals($expected, $actual,	'Was not redirected to ' . $expected);
	}

}
