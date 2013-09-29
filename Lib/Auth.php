<?php
if (!defined('USER_ROLE_KEY')) {
	define('USER_ROLE_KEY', 'Role');
}
if (!defined('USER_RIGHT_KEY')) {
	define('USER_RIGHT_KEY', 'Right');
}

App::uses('CakeSession', 'Model/Datasource');

/**
 * Convenience wrapper to access Auth data and check on rights/roles.
 * Expects the Role session infos to be either
 * 	`Auth.User.role_id` (single) or
 * 	`Auth.User.Role` (multi - flat array of roles, or array role data)
 * and can be adjusted via defined().
 * Same for Right.
 *
 * @author Mark Scherer
 * @license MIT
 * @php 5
 * @cakephp 2
 */
class Auth {

	/**
	 * Get the user id of the current session.
	 *
	 * This can be used anywhere to check if a user is logged in.
	 *
	 * @return mixed User id if existent, null otherwise.
	 */
	public static function id() {
		return CakeSession::read('Auth.User.id');
	}

	/**
	 * Get the role(s) of the current session.
	 *
	 * It will return the single role for single role setup, and a flat
	 * list of roles for multi role setup.
	 *
	 * @return mixed String or array of roles or null if inexistent
	 */
	public static function roles() {
		$roles = CakeSession::read('Auth.User.' . USER_ROLE_KEY);
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
	 * @param string $key (dot syntax)
	 * @return mixed Data
	 */
	public static function user($key = null) {
		if ($key) {
			$key = '.' . $key;
		}
		return CakeSession::read('Auth.User' . $key);
	}

	/**
	 * Check if the current session has this right.
	 *
	 * @param mixed $role
	 * @param mixed $providedRights
	 * @return boolean Success
	 */
	public static function hasRight($ownRight, $providedRights = null) {
		if ($providedRights !== null) {
			$rights = $providedRights;
		} else {
			$rights = CakeSession::read('Auth.User.' . USER_RIGHT_KEY);
		}
		$rights = (array)$rights;
		if (array_key_exists($ownRight, $rights) && !empty($rights[$ownRight])) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the current session has this role.
	 *
	 * @param mixed $role
	 * @param mixed $providedRoles
	 * @return boolean Success
	 */
	public static function hasRole($ownRole, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = $providedRoles;
		} else {
			$roles = self::roles();
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
	 * Check if the current session has oen of these roles.
	 *
	 * @param mixed $roles
	 * @param boolean $oneRoleIsEnough (if all $roles have to match instead of just one)
	 * @param mixed $providedRoles
	 * @return boolean Success
	 */
	public static function hasRoles($ownRoles, $oneRoleIsEnough = true, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = $providedRoles;
		} else {
			$roles = self::roles();
		}
		$ownRoles = (array)$ownRoles;
		if (empty($ownRoles)) {
			return false;
		}
		$count = 0;
		foreach ($ownRoles as $role) {
			if (self::hasRole($role, $roles)) {
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

}
