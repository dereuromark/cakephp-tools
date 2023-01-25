<?php

namespace Tools\Auth;

use Cake\Core\App;
use RuntimeException;

/**
 * Builds password hashing objects
 */
class PasswordHasherFactory {

	/**
	 * Returns password hasher object out of a hasher name or a configuration array
	 *
	 * @param array<string, mixed>|string $passwordHasher Name of the password hasher or an array with
	 * at least the key `className` set to the name of the class to use
	 * @throws \RuntimeException If password hasher class not found or
	 *   it does not extend {@link \Tools\Auth\AbstractPasswordHasher}
	 * @return \Tools\Auth\AbstractPasswordHasher Password hasher instance
	 */
	public static function build($passwordHasher): AbstractPasswordHasher {
		$config = [];
		if (is_string($passwordHasher)) {
			$class = $passwordHasher;
		} else {
			$class = $passwordHasher['className'];
			$config = $passwordHasher;
			unset($config['className']);
		}

		$className = App::className('Tools.' . $class, 'Auth', 'PasswordHasher');
		if ($className === null) {
			throw new RuntimeException(sprintf('Password hasher class "%s" was not found.', $class));
		}

		$hasher = new $className($config);
		if (!($hasher instanceof AbstractPasswordHasher)) {
			throw new RuntimeException('Password hasher must extend AbstractPasswordHasher class.');
		}

		return $hasher;
	}

}
