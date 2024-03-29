<?php
declare(strict_types=1);

namespace Tools\Auth;

/**
 * Default password hashing class.
 */
class DefaultPasswordHasher extends AbstractPasswordHasher {

	/**
	 * Default config for this object.
	 *
	 * ### Options
	 *
	 * - `hashType` - Hashing algo to use. Valid values are those supported by `$algo`
	 *   argument of `password_hash()`. Defaults to `PASSWORD_DEFAULT`
	 * - `hashOptions` - Associative array of options. Check the PHP manual for
	 *   supported options for each hash type. Defaults to empty array.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'hashType' => PASSWORD_DEFAULT,
		'hashOptions' => [],
	];

	/**
	 * Generates password hash.
	 *
	 * @psalm-suppress InvalidNullableReturnType
	 * @link https://book.cakephp.org/4/en/controllers/components/authentication.html#hashing-passwords
	 * @param string $password Plain text password to hash.
	 * @return string Password hash or false on failure
	 */
	public function hash(string $password): string {
		return password_hash(
			$password,
			$this->_config['hashType'],
			$this->_config['hashOptions'],
		);
	}

	/**
	 * Check hash. Generate hash for user provided password and check against existing hash.
	 *
	 * @param string $password Plain text password to hash.
	 * @param string $hashedPassword Existing hashed password.
	 * @return bool True if hashes match else false.
	 */
	public function check(string $password, string $hashedPassword): bool {
		return password_verify($password, $hashedPassword);
	}

	/**
	 * Returns true if the password need to be rehashed, due to the password being
	 * created with anything else than the passwords generated by this class.
	 *
	 * @param string $password The password to verify
	 * @return bool
	 */
	public function needsRehash(string $password): bool {
		return password_needs_rehash($password, $this->_config['hashType'], $this->_config['hashOptions']);
	}

}
