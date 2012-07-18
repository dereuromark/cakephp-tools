<?php
if (!defined('USER_ROLE_KEY')) {
	define('USER_ROLE_KEY', 'Role');
}
if (!defined('USER_RIGHT_KEY')) {
	define('USER_RIGHT_KEY', 'Right');
}

App::uses('CakeSession', 'Model/Datasource');

/**
 * Convinience wrapper to access Auth data and check on rights/roles.
 * Expects the Role session infos to be either
 * 	`Auth.User.role_id` (single) or
 * 	`Auth.User.Role` (multi)
 * and can be adjusted via defined().
 * Same for Right.
 *
 * @author Mark Scherer
 * @license MIT
 * @php 5
 * @cakephp 2
 * 2012-04-07 ms
 */
class Auth {

	/**
	 * get the user id of the current session or return empty/null
	 *
	 * @return mixed $userId
	 */
	public static function id() {
		return CakeSession::read('Auth.User.id');
	}

	/**
	 * get the role(s) of the current session or return empty/null
	 *
	 * @return mixed $roles
	 */
	public static function roles() {
		return CakeSession::read('Auth.User.' . USER_ROLE_KEY);
	}

	/**
	 * get the user data of the current session or return empty/null
	 *
	 * @param string $key (dot syntax)
	 * @return mixed $data
	 */
	public static function user($key = null) {
		if ($key) {
			$key = '.' . $key;
		}
		return CakeSession::read('Auth.User' . $key);
	}

	/**
	 * check if the current session has this right
	 *
	 * @param mixed $role
	 * @param mixed $existingRolesToCheckAgainst
	 * @return bool $success
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
	 * check if the current session has this role
	 *
	 * @param mixed $role
	 * @param mixed $existingRolesToCheckAgainst
	 * @return bool $success
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
	 * check if the current session has oen of these roles
	 *
	 * @param mixed $roles
	 * @param bool $oneRoleIsEnough (if all $roles have to match instead of just one)
	 * @param mixed $existingRolesToCheckAgainst
	 * @return bool $success
	 */
	public static function hasRoles($ownRoles, $oneRoleIsEnough = true, $providedRoles = null) {
		if ($providedRoles !== null) {
			$roles = $providedRoles;
		} else {
			$roles = self::roles();
		}
		if (is_array($ownRoles)) {
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

			if ($count == count($ownRoles)) {
				return true;
			}
			return false;
		} else {
			return self::hasRole($ownRoles, $roles);
		}
	}

}


