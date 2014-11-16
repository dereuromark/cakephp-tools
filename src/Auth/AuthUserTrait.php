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
 * It can be used anywhere in the application due to static access.
 * So in the view we can use this shortcut to check if a user is logged in:
 *
 *   if (Auth::id()) {
 *     // Display element
 *   }
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
	 * @return mixed User id if existent, null otherwise.
	 */
	public function id() {
		return $this->user('id');
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
	 * Get the user data of the current session.
	 *
	 * @param string $key Key in dot syntax.
	 * @return mixed Data
	 */
	public function user($key = null) {
		return Hash::get($this->_getUser(), $key);
	}

	/**
	 * Check if the current session has this role.
	 *
	 * @param mixed $role
	 * @param mixed $providedRoles
	 * @return bool Success
	 */
	public function hasRole($ownRole, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = $providedRoles;
		} else {
			$roles = $this->roles();
		}
		if (is_array($roles)) {
			if (in_array($ownRole, $roles)) {
				return true;
			}
		} elseif (!empty($roles)) {
			if ($ownRole == $roles) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the current session has one of these roles.
	 *
	 * You can either require one of the roles (default), or you can require all
	 * roles to match.
	 *
	 * @param mixed $roles
	 * @param bool $oneRoleIsEnough (if all $roles have to match instead of just one)
	 * @param mixed $providedRoles
	 * @return bool Success
	 */
	public function hasRoles($ownRoles, $oneRoleIsEnough = true, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = $providedRoles;
		} else {
			$roles = $this->roles();
		}
		$ownRoles = (array)$ownRoles;
		if (empty($ownRoles)) {
			return false;
		}
		$count = 0;
		foreach ($ownRoles as $role) {
			if ($this->hasRole($role, $roles)) {
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

		if ($count === count($ownRoles)) {
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
	public function hasRight($ownRight, $providedRights = null) {
		if ($providedRights !== null) {
			$rights = $providedRights;
		} else {
			$rights = $this->user(USER_RIGHT_KEY);
		}
		$rights = (array)$rights;
		if (array_key_exists($ownRight, $rights) && !empty($rights[$ownRight])) {
			return true;
		}
		return false;
	}

}
