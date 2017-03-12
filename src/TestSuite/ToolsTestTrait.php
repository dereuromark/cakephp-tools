<?php

namespace Tools\TestSuite;

use ReflectionClass;

/**
 * Utility methods for easier testing in CakePHP & PHPUnit
 */
trait ToolsTestTrait {

	/**
	 * OsFix method
	 *
	 * @param string $string
	 * @return string
	 */
	protected static function osFix($string) {
		return str_replace(["\r\n", "\r"], "\n", $string);
	}

	/**
	 * Checks if debug flag is set.
	 *
	 * Flag is set via `--debug`.
	 * Allows additional stuff like non-mocking when enabling debug.
	 *
	 * @return bool Success
	 */
	protected static function isDebug() {
		return !empty($_SERVER['argv']) && in_array('--debug', $_SERVER['argv'], true);
	}

	/**
	 * Checks if verbose flag is set.
	 *
	 * Flags are `-v` and `-vv`.
	 * Allows additional stuff like non-mocking when enabling debug.
	 *
	 * @param bool $onlyVeryVerbose If only -vv should be counted.
	 * @return bool Success
	 */
	protected static function isVerbose($onlyVeryVerbose = false) {
		if (empty($_SERVER['argv'])) {
			return false;
		}
		if (!$onlyVeryVerbose && in_array('-v', $_SERVER['argv'], true)) {
			return true;
		}
		if (in_array('-vv', $_SERVER['argv'], true)) {
			return true;
		}
		return false;
	}

	/**
	 * Outputs debug information during a test run.
	 * This is a convenience output handler since debug() itself is not desired
	 * for tests in general.
	 *
	 * Forces flushing the output if -v or -vv is set.
	 *
	 * @param mixed $data
	 * @return void
	 */
	protected static function debug($data) {
		if (!static::isVerbose()) {
			return;
		}
		$showFrom = static::isVerbose(true);

		debug($data, null, $showFrom);
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * So
	 *   $this->invokeMethod($user, 'cryptPassword', array('passwordToCrypt'));
	 * is equal to
	 *   $user->cryptPassword('passwordToCrypt');
	 * (assuming the method was directly publicly accessible
	 *
	 * @param object &$object Instantiated object that we will run method on.
	 * @param string $methodName Method name to call.
	 * @param array $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 */
	protected function invokeMethod(&$object, $methodName, array $parameters = []) {
		$reflection = new ReflectionClass(get_class($object));
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}

	/**
	 * Gets protected/private property of a class.
	 *
	 * So
	 *   $this->invokeProperty($object, '_foo');
	 * is equal to
	 *   $object->_foo
	 * (assuming the property was directly publicly accessible)
	 *
	 * @param object &$object Instantiated object that we want the property off.
	 * @param string $name Property name to fetch.
	 *
	 * @return mixed Property value.
	 */
	protected function invokeProperty(&$object, $name) {
		$reflection = new ReflectionClass(get_class($object));
		$property = $reflection->getProperty($name);
		$property->setAccessible(true);

		return $property->getValue($object);
	}

}
