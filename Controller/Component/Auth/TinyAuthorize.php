<?php
App::uses('Inflector', 'Utility');
App::uses('Hash', 'Utility');
App::uses('BaseAuthorize', 'Controller/Component/Auth');

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User'); // override if you have it in a plugin: PluginName.User etc
}
if (!defined('AUTH_CACHE')) {
	define('AUTH_CACHE', '_cake_core_'); // use the most persistent cache by default
}
if (!defined('ACL_FILE')) {
	define('ACL_FILE', 'acl.ini'); // stored in /app/Config/
}

/**
 * Probably the most simple and fastest Acl out there.
 * Only one config file `acl.ini` necessary
 * Doesn't even need a Role Model / roles table
 * Uses most persistent _cake_core_ cache by default
 * @link http://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2
 *
 * Usage:
 * Include it in your beforeFilter() method of the AppController
 * $this->Auth->authorize = array('Tools.Tiny');
 *
 * Or with admin prefix protection only
 * $this->Auth->authorize = array('Tools.Tiny'=>array('allowUser'=>true));
 *
 * @version 1.2 - now allows other parent model relations besides Role/role_id
 * @author Mark Scherer
 * @cakephp 2.x
 * @license MIT
 */
class TinyAuthorize extends BaseAuthorize {

	protected $_acl = null;

	protected $_defaults = array(
		'superadminRole' => null, // quick way to allow access to every action
		'allowUser' => false, // quick way to allow user access to non prefixed urls
		'allowAdmin' => false, // quick way to allow admin access to admin prefixed urls
		'adminPrefix' => 'admin_',
		'adminRole' => null, // needed together with adminPrefix if allowAdmin is enabled
		'cache' => AUTH_CACHE,
		'cacheKey' => 'tiny_auth_acl',
		'autoClearCache' => false, // usually done by Cache automatically in debug mode,
		'aclModel' => 'Role', // only for multiple roles per user (HABTM)
		'aclKey' => 'role_id', // only for single roles per user (BT)
	);

	public function __construct(ComponentCollection $Collection, $settings = array()) {
		$settings = array_merge($this->_defaults, $settings);
		parent::__construct($Collection, $settings);

		if (Cache::config($settings['cache']) === false) {
			throw new CakeException(__d('dev', 'TinyAuth could not find `%s` cache - expects at least a `default` cache', $settings['cache']));
		}
	}

	/**
	 * Authorize a user using the AclComponent.
	 * allows single or multi role based authorization
	 *
	 * Examples:
	 * - User HABTM Roles (Role array in User array)
	 * - User belongsTo Roles (role_id in User array)
	 *
	 * @param array $user The user to authorize
	 * @param CakeRequest $request The request needing authorization.
	 * @return boolean Success
	 */
	public function authorize($user, CakeRequest $request) {
		if (isset($user[$this->settings['aclModel']])) {
			if (isset($user[$this->settings['aclModel']][0]['id'])) {
				$roles = Hash::extract($user[$this->settings['aclModel']], '{n}.id');
			} elseif (isset($user[$this->settings['aclModel']]['id'])) {
				$roles = array($user[$this->settings['aclModel']]['id']);
			} else {
				$roles = (array)$user[$this->settings['aclModel']];
			}
		} elseif (isset($user[$this->settings['aclKey']])) {
			$roles = array($user[$this->settings['aclKey']]);
		} else {
			$acl = $this->settings['aclModel'] . '/' . $this->settings['aclKey'];
			trigger_error(__d('dev', 'Missing acl information (%s) in user session', $acl));
			$roles = array();
		}
		return $this->validate($roles, $request->params['plugin'], $request->params['controller'], $request->params['action']);
	}

	/**
	 * Validate the url to the role(s)
	 * allows single or multi role based authorization
	 *
	 * @return boolean Success
	 */
	public function validate($roles, $plugin, $controller, $action) {
		$action = Inflector::underscore($action);
		$controller = Inflector::underscore($controller);
		$plugin = Inflector::underscore($plugin);

		if (!empty($this->settings['allowUser'])) {
			// all user actions are accessable for logged in users
			if (mb_strpos($action, $this->settings['adminPrefix']) !== 0) {
				return true;
			}
		}
		if (!empty($this->settings['allowAdmin']) && !empty($this->settings['adminRole'])) {
			// all admin actions are accessable for logged in admins
			if (mb_strpos($action, $this->settings['adminPrefix']) === 0) {
				if (in_array((string)$this->settings['adminRole'], $roles)) {
					return true;
				}
			}
		}

		if ($this->_acl === null) {
			$this->_acl = $this->_getAcl();
		}

		// allow_all check
		if (!empty($this->settings['superadminRole'])) {
			foreach ($roles as $role) {
				if ($role == $this->settings['superadminRole']) {
					return true;
				}
			}
		}

		// controller wildcard
		if (isset($this->_acl[$controller]['*'])) {
			$matchArray = $this->_acl[$controller]['*'];
			if (in_array('-1', $matchArray)) {
				return true;
			}
			foreach ($roles as $role) {
				if (in_array((string)$role, $matchArray)) {
					return true;
				}
			}
		}

		// specific controller/action
		if (!empty($controller) && !empty($action)) {
			if (array_key_exists($controller, $this->_acl) && !empty($this->_acl[$controller][$action])) {
				$matchArray = $this->_acl[$controller][$action];

				// direct access? (even if he has no roles = GUEST)
				if (in_array('-1', $matchArray)) {
					return true;
				}

				// normal access (rolebased)
				foreach ($roles as $role) {
					if (in_array((string)$role, $matchArray)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @return object The User model
	 */
	public function getModel() {
		return ClassRegistry::init(CLASS_USER);
	}

	/**
	 * Parse ini file and returns the allowed roles per action
	 * - uses cache for maximum performance
	 * improved speed by several actions before caching:
	 * - resolves role slugs to their primary key / identifier
	 * - resolves wildcards to their verbose translation
	 *
	 * @return array Roles
	 */
	protected function _getAcl($path = null) {
		if ($path === null) {
			$path = APP . 'Config' . DS;
		}

		$res = array();
		if ($this->settings['autoClearCache'] && Configure::read('debug') > 0) {
			Cache::delete($this->settings['cacheKey'], $this->settings['cache']);
		}
		if (($roles = Cache::read($this->settings['cacheKey'], $this->settings['cache'])) !== false) {
			return $roles;
		}
		if (!file_exists($path . ACL_FILE)) {
			touch($path . ACL_FILE);
		}
		$iniArray = parse_ini_file($path . ACL_FILE, true);

		$availableRoles = Configure::read($this->settings['aclModel']);
		if (!is_array($availableRoles)) {
			$Model = $this->getModel();
			if (!isset($Model->{$this->settings['aclModel']})) {
				throw new CakeException('Missing relationship between User and Role.');
			}
			$availableRoles = $Model->{$this->settings['aclModel']}->find('list', array('fields' => array('alias', 'id')));
			Configure::write($this->settings['aclModel'], $availableRoles);
		}
		if (!is_array($availableRoles) || !is_array($iniArray)) {
			trigger_error(__d('dev', 'Invalid Role Setup for TinyAuthorize (no roles found)'));
			return false;
		}

		foreach ($iniArray as $key => $array) {
			list($plugin, $controllerName) = pluginSplit($key);
			$controllerName = Inflector::underscore($controllerName);

			foreach ($array as $actions => $roles) {
				$actions = explode(',', $actions);
				$roles = explode(',', $roles);

				foreach ($roles as $key => $role) {
					if (!($role = trim($role))) {
						continue;
					}
					if ($role === '*') {
						unset($roles[$key]);
						$roles = array_merge($roles, array_keys(Configure::read($this->settings['aclModel'])));
					}
				}

				foreach ($actions as $action) {
					if (!($action = trim($action))) {
						continue;
					}
					$actionName = Inflector::underscore($action);

					foreach ($roles as $role) {
						if (!($role = trim($role)) || $role === '*') {
							continue;
						}
						$newRole = Configure::read($this->settings['aclModel'] . '.' . strtolower($role));
						if (!empty($res[$controllerName][$actionName]) && in_array((string)$newRole, $res[$controllerName][$actionName])) {
							continue;
						}
						$res[$controllerName][$actionName][] = $newRole;
					}
				}
			}
		}
		Cache::write($this->settings['cacheKey'], $res, $this->settings['cache']);
		return $res;
	}

}
