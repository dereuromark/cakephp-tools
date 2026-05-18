<?php

namespace TestApp\Auth;

use Tools\Auth\AbstractPasswordHasher;

/**
 * App-level custom hasher used to verify that PasswordHasherFactory can resolve
 * hashers outside the Tools plugin.
 */
class CustomPasswordHasher extends AbstractPasswordHasher {

	/**
	 * @param string $password Plain text password to hash.
	 * @return string The password hash
	 */
	public function hash(string $password): string {
		return strrev($password);
	}

	/**
	 * @param string $password Plain text password to hash.
	 * @param string $hashedPassword Existing hashed password.
	 * @return bool True if hashes match else false.
	 */
	public function check(string $password, string $hashedPassword): bool {
		return $this->hash($password) === $hashedPassword;
	}

}
