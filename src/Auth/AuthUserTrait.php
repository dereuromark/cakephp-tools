<?php

namespace Tools\Auth;

use Cake\Utility\Hash;

if (!defined('USER_ROLE_KEY')) {
	define('USER_ROLE_KEY', 'Roles');
}
if (!defined('USER_RIGHT_KEY')) {
	define('USER_RIGHT_KEY', 'Rights');
}

/**
 * Convenience wrapper to access Auth data and check on rights/roles.
 *
 * Simply add it at the class file:
 *
 *   trait AuthUserTrait;
 *
 * But needs
 *
 *   protected function _getUser() {}
 *
 * to be implemented in the using class.
 *
 * Expects the Role session infos to be either
 * 	- `Auth.User.role_id` (single) or
 * 	- `Auth.User.Role` (multi - flat array of roles, or array role data)
 * and can be adjusted via constants and defined().
 * Same goes for Right data.
 *
 * Note: This uses AuthComponent internally to work with both stateful and stateless auth.
 *
 * @author Mark Scherer
 * @license MIT
 */
trait AuthUserTrait {

	/**
	 * Get the user id of the current session.
	 *
	 * This can be used anywhere to check if a user is logged in.
	 *
	 * @param string $field Field name. Defaults to `id`.
	 * @return mixed User id if existent, null otherwise.
	 */
	public function id($field = 'id') {
		return $this->user($field);
	}

	/**
	 * This check can be used to tell if a record that belongs to some user is the
	 * current logged in user
	 *
	 * @param string|int $userId
	 * @param string $field Field name. Defaults to `id`.
	 * @return boolean
	 */
	public function isMe($userId, $field = 'id') {
		return ($userId && (string)$userId === (string)$this->user($field));
	}

	/**
	 * Get the user data of the current session.
	 *
	 * @param string $key Key in dot syntax.
	 * @return mixed Data
	 */
	public function user($key = null) {
		$user = $this->_getUser();
		if ($key === null) {
			return $user;
		}
		return Hash::get($user, $key);
	}

	/**
	 * Get the role(s) of the current session.
	 *
	 * It will return the single role for single role setup, and a flat
	 * list of roles for multi role setup.
	 *
	 * @return mixed String or array of roles or null if inexistent.
	 */
	public function roles() {
		$roles = $this->user(USER_ROLE_KEY);
		if (!is_array($roles)) {
			return $roles;
		}
		if (isset($roles[0]['id'])) {
			$roles = Hash::extract($roles, '{n}.id');
		}
		return $roles;
	}

	/**
	 * Check if the current session has this role.
	 *
	 * @param mixed $role
	 * @param mixed $providedRoles
	 * @return bool Success
	 */
	public function hasRole($expectedRole, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = (array)$providedRoles;
		} else {
			$roles = (array)$this->roles();
		}
		if (empty($roles)) {
			return false;
		}

		if (in_array($expectedRole, $roles)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the current session has one of these roles.
	 *
	 * You can either require one of the roles (default), or you can require all
	 * roles to match.
	 *
	 * @param mixed $expectedRoles
	 * @param bool $oneRoleIsEnough (if all $roles have to match instead of just one)
	 * @param mixed $providedRoles
	 * @return bool Success
	 */
	public function hasRoles($expectedRoles, $oneRoleIsEnough = true, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = $providedRoles;
		} else {
			$roles = $this->roles();
		}
		$expectedRoles = (array)$expectedRoles;
		if (empty($expectedRoles)) {
			return false;
		}
		$count = 0;
		foreach ($expectedRoles as $expectedRole) {
			if ($this->hasRole($expectedRole, $roles)) {
				if ($oneRoleIsEnough) {
					return true;
				}
				$count++;
			} else {
				if (!$oneRoleIsEnough) {
					return false;
				}
			}
		}

		if ($count === count($expectedRoles)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the current session has this right.
	 *
	 * Rights can be an additional element to give permissions, e.g.
	 * the right to send messages/emails, to friend request other users,...
	 * This can be set via Right model and stored in the Auth array upon login
	 * the same way the roles are.
	 *
	 * @param mixed $role
	 * @param mixed $providedRights
	 * @return bool Success
	 */
	public function hasRight($expectedRight, $providedRights = null) {
		if ($providedRights !== null) {
			$rights = $providedRights;
		} else {
			$rights = $this->user(USER_RIGHT_KEY);
		}
		$rights = (array)$rights;
		if (array_key_exists($expectedRight, $rights) && !empty($rights[$expectedRight])) {
			return true;
		}
		return false;
	}

}
