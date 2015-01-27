<?php

/**
 * Builds password hashing objects
 *
 * Backported from 3.x
 */
class PasswordHasherFactory {

	/**
	 * Returns password hasher object out of a hasher name or a configuration array
	 *
	 * @param string|array $passwordHasher Name of the password hasher or an array with
	 * at least the key `className` set to the name of the class to use
	 * @return \Cake\Auth\AbstractPasswordHasher Password hasher instance
	 * @throws \RuntimeException If password hasher class not found or
	 *   it does not extend Cake\Auth\AbstractPasswordHasher
	 */
	public static function build($passwordHasher) {
		$config = [];
		if (is_string($passwordHasher)) {
			$class = $passwordHasher;
		} else {
			$class = $passwordHasher['className'];
			$config = $passwordHasher;
			unset($config['className']);
		}

		list($plugin, $class) = pluginSplit($class, true);
		$className = $class . 'PasswordHasher';
		App::uses($className, $plugin . 'Controller/Component/Auth');
		if (!class_exists($className)) {
			throw new CakeException(sprintf('Password hasher class "%s" was not found.', $class));
		}
		if (!is_subclass_of($className, 'AbstractPasswordHasher')) {
			throw new CakeException('Password hasher must extend AbstractPasswordHasher class.');
		}
		return new $className($config);
	}
}
