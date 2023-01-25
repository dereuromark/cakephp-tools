<?php

namespace Tools\Auth;

use Cake\Core\InstanceConfigTrait;

/**
 * Abstract password hashing class
 */
abstract class AbstractPasswordHasher {

	use InstanceConfigTrait;

	/**
	 * Default config
	 *
	 * These are merged with user-provided config when the object is used.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [];

	/**
	 * Constructor
	 *
	 * @param array<string, mixed> $config Array of config.
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);
	}

	/**
	 * Generates password hash.
	 *
	 * @param string $password Plain text password to hash.
	 * @return string|false Either the password hash string or false
	 */
	abstract public function hash(string $password);

	/**
	 * Check hash. Generate hash from user provided password string or data array
	 * and check against existing hash.
	 *
	 * @param string $password Plain text password to hash.
	 * @param string $hashedPassword Existing hashed password.
	 * @return bool True if hashes match else false.
	 */
	abstract public function check(string $password, string $hashedPassword): bool;

	/**
	 * Returns true if the password need to be rehashed, due to the password being
	 * created with anything else than the passwords generated by this class.
	 *
	 * Returns true by default since the only implementation users should rely
	 * on is the one provided by default in php 5.5+ or any compatible library
	 *
	 * @param string $password The password to verify
	 * @return bool
	 */
	public function needsRehash(string $password): bool {
		return password_needs_rehash($password, PASSWORD_DEFAULT);
	}

}
