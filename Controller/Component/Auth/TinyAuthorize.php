<?php
App::uses('Inflector', 'Utility');

if (!defined('AUTH_CACHE')) {
	define('AUTH_CACHE', CACHE.'persistent'.DS);
}
if (!defined('ROLE_CONFIGS')) {
	define('ROLE_CONFIGS', 'roles.ini');
}

/**
 * Probably the most simple and fastest Acl out there.
 * Only one config file `roles.ini` necessary
 * Doesnt even need a Role Model/table
 * @link http://www.dereuromark.de/2011/12/18/tinyauth-the-fastest-and-easiest-authorization-for-cake2
 * 
 * Usage:
 * Include it in your beforeFilter() method of the AppController
 * $this->Auth->authorize = array('Tools.Tiny');
 * 
 * Or with admin prefix protection only
 * $this->Auth->authorize = array('Tools.Tiny'=>array('allowUser'=>true));
 * 
 * @author Mark Scherer
 * @cakephp 2.0
 * @license MIT
 * 2011-12-17 ms
 */
class TinyAuthorize extends BaseAuthorize {

	protected $_matchArray = array();

	protected $_defaults = array(
		'allowUser' => false, # quick way to allow user access to non prefixed urls
		'adminPrefix' => 'admin_'
	);

	public function __construct(ComponentCollection $Collection, $settings = array()) {
		$settings = am($this->_defaults, $settings);
		parent::__construct($Collection, $settings);

		$this->_matchArray = $this->_getRoles();
	}
	
	/**
	 * Authorize a user using the AclComponent.
	 * allows single or multi role based authorization 
	 *
	 * @param array $user The user to authorize
	 * @param CakeRequest $request The request needing authorization.
	 * @return boolean
	 */
	public function authorize($user, CakeRequest $request) {
		if (isset($user['Role'])) {
			$roles = (array)$user['Role'];
		} else {
			$roles = array($user['role_id']);
		}
		return $this->validate($roles, $request->params['plugin'], $request->params['controller'], $request->params['action']);
	}

	/**
	 * validate the url to the role(s)
	 * allows single or multi role based authorization
	 * @return bool $success
	 */
	public function validate($roles, $plugin, $controller, $action) {
		$action = Inflector::underscore($action);
		$controller = Inflector::underscore($controller);
		$plugin = Inflector::underscore($plugin);
		
		if (!empty($this->settings['allowUser'])) {
			# all user actions are accessable for logged in users
			if (mb_strpos($action, $this->settings['adminPrefix']) !== 0) {
				return true;
			}
		}
		
		if (isset($this->_matchArray[$controller]['*'])) {
			$matchArray = $this->_matchArray[$controller]['*'];
			if (in_array(-1, $matchArray)) {
				return true;
			}
			foreach ($roles as $role) {
				if (in_array($role, $matchArray)) {
					return true;
				}
			}
		}
		if (!empty($controller) && !empty($action)) {
			if (array_key_exists($controller, $this->_matchArray) && !empty($this->_matchArray[$controller][$action])) {
				$matchArray = $this->_matchArray[$controller][$action];

				# direct access? (even if he has no roles = GUEST)
				if (in_array(-1, $matchArray)) {
					return true;
				}

				# normal access (rolebased)
				foreach ($roles as $role) {
					if (in_array($role, $matchArray)) {
						return true;
					}
				}
			}
		}
		return false;
	}
	
	public function getModel() {
		return ClassRegistry::init(CLASS_USER); 
	}

	/**
	 * parse ini files
	 */	
	protected function _getRoles() {
		$res = array();
		if (file_exists(AUTH_CACHE.'roles.tmp') && Configure::read('debug') > 0) {
			unlink(AUTH_CACHE.'roles.tmp');
		}
		if (file_exists(AUTH_CACHE.'roles.tmp')) {
			include(AUTH_CACHE.'roles.tmp');
			return $roles;
		}
		if (!file_exists(APP . 'Config' . DS . ROLE_CONFIGS)) {
			touch(APP . 'Config' . DS . ROLE_CONFIGS);
		}
		$iniArray = parse_ini_file(APP . 'Config' . DS . ROLE_CONFIGS, true);
		
		$availableRoles = Configure::read('Role');
		if (!is_array($availableRoles)) {
			$Model = $this->getModel();
			$availableRoles = $Model->Role->find('list', array('fields'=>array('alias', 'id')));
			Configure::write('Role', $availableRoles);
		}
		if (!is_array($availableRoles) || !is_array($iniArray)) {
			trigger_error('Invalid Role Setup for TinyAuthorize (no roles found)');
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
					if ($role == '*') {
						unset($roles[$key]);
						$roles = array_merge($roles, array_keys(Configure::read('Role')));
					}
				}
				
				foreach ($actions as $action) {
					if (!($action = trim($action))) {
						continue;
					}
					$actionName = Inflector::underscore($action);
					
					foreach ($roles as $role) {
						if (!($role = trim($role)) || $role == '*') {
							continue;
						}
						$newRole = Configure::read('Role.'.strtolower($role));
						if (!empty($res[$controllerName][$actionName]) && in_array($newRole, $res[$controllerName][$actionName])) {
							continue;
						}
						$res[$controllerName][$actionName][] = $newRole;
					}
				}
			}
		}
		
		file_put_contents(AUTH_CACHE.'roles.tmp', '<?php $roles = ' . var_export($res, true). ';');
		return $res;
	}
		
}