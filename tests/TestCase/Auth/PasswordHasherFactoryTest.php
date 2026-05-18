<?php

namespace Tools\Test\TestCase\Auth;

use RuntimeException;
use Shim\TestSuite\TestCase;
use TestApp\Auth\CustomPasswordHasher;
use Tools\Auth\DefaultPasswordHasher;
use Tools\Auth\PasswordHasherFactory;

class PasswordHasherFactoryTest extends TestCase {

	/**
	 * Bare built-in names resolve within the Tools plugin (backwards compatible).
	 *
	 * @return void
	 */
	public function testBuildDefaultResolvesToolsPlugin() {
		$result = PasswordHasherFactory::build('Default');

		$this->assertInstanceOf(DefaultPasswordHasher::class, $result);
	}

	/**
	 * Array config resolves the class and strips the className key.
	 *
	 * @return void
	 */
	public function testBuildWithArrayConfig() {
		$result = PasswordHasherFactory::build([
			'className' => 'Default',
			'hashType' => PASSWORD_BCRYPT,
		]);

		$this->assertInstanceOf(DefaultPasswordHasher::class, $result);
		$this->assertSame(PASSWORD_BCRYPT, $result->getConfig('hashType'));
		$this->assertNull($result->getConfig('className'));
	}

	/**
	 * App-level hashers (not in the Tools plugin) can be resolved by name.
	 *
	 * @return void
	 */
	public function testBuildAppLevelHasher() {
		$result = PasswordHasherFactory::build('Custom');

		$this->assertInstanceOf(CustomPasswordHasher::class, $result);
	}

	/**
	 * @return void
	 */
	public function testBuildInvalidClassThrows() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Password hasher class "DoesNotExist" was not found.');

		PasswordHasherFactory::build('DoesNotExist');
	}

}
