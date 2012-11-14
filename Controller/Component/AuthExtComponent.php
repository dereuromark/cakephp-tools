<?php

if (!defined('USER_ROLE_KEY')) {
	define('USER_ROLE_KEY', 'Role');
}
if (!defined('USER_INFO_KEY')) {
	define('USER_INFO_KEY', 'Info');
}
if (!defined('USER_RIGHT_KEY')) {
	define('USER_RIGHT_KEY', 'Right');
}
if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}

App::uses('AuthComponent', 'Controller/Component');

/**
 * Important:
 * index the ACO on alias, index the Aro on model+id
 *
 * Extends AuthComponent with the following addons:
 * - allows multiple roles per user
 * - auto-raises login counter and sets last_login date
 * - preps the session data according to completeAuth() method (adds parent data etc)
 * - dynamic login scope validation
 *
 * @author Mark Scherer
 * @cakephp 2.x
 * @license MIT
 * 2011-12-18 ms
 */
class AuthExtComponent extends AuthComponent {

	public $intermediateModel = 'RoleUser';

	public $roleModel = 'Role';

	public $fieldKey = 'role_id';

	public $loginAction = array('controller' => 'account', 'action' => 'login', 'admin' => false, 'plugin' => false);

	public $loginRedirect = array('controller' => 'overview', 'action' => 'home', 'admin' => false, 'plugin' => false);

	public $autoRedirect = false;

	public $loginError = null;

	public $settings = array(
		'multi' => null, # null=auto - yes/no multiple roles (HABTM table between users and roles)
		'parentModelAlias' => USER_ROLE_KEY,
		'userModel' => CLASS_USER //TODO: allow plugin syntax
	);

	# field name in DB , if none is specified there will be no floodProtection
	public $floodProtection = null;


	public function __construct(ComponentCollection $Collection, $settings = array()) {
		$settings = array_merge($this->settings, (array)Configure::read('Auth'), (array)$settings);

		parent::__construct($Collection, $settings);
	}

	public function initialize(Controller $Controller) {
		$this->Controller = $Controller;

		parent::initialize($Controller);
	}

	/**
	 * 2.1 fix for allowing * as wildcard (tmp solution)
	 * 2012-01-10 ms
	 */
	public function allow($action = null) {
		if (((array)$action) === array('*')) {
			parent::allow();
			trigger_error('* is deprecated for allow() - use allow() without any argument to allow all actions');
			return;
		}
		$args = func_get_args();
		if (empty($args) || $action === null) {
			parent::allow();
		}
		parent::allow($args);
	}

	public function login($user = null) {
		$Model = $this->getModel();
		$this->_setDefaults();

		if (empty($user)) {
			$user = $this->identify($this->Controller->request, $this->Controller->response);
		} elseif (!is_array($user)) {
			$user = $this->completeAuth($user);
		}
		if (empty($user)) {
			$this->loginError = __('invalidLoginCredentials');
			return false;
		}

		# custom checks
		if (isset($user['active'])) {
			if (empty($user['active'])) {
				$this->loginError = __('Account not active yet');
				return false;
			}
			if (!empty($user['suspended'])) {
				$this->loginError = __('Account wurde vorübergehend gesperrt');
				if (!empty($user['suspended_reason'])) {
					$this->loginError .= BR . BR . 'Grund:' . BR . nl2br(h($user['suspended_reason']));
				}
				return false;
			}
		} else {
			if (isset($user['status']) && empty($user['status'])) {
				$this->loginError = __('Account not active yet');
				return false;
			}
			if (isset($user['status']) && defined('User::STATUS_PENDING') && $user['status'] == User::STATUS_PENDING) {
				$this->loginError = __('Account wurde noch nicht freigeschalten');
				return false;
			}
			if (isset($user['status']) && defined('User::STATUS_SUSPENDED') && $user['status'] == User::STATUS_SUSPENDED) {
				$this->loginError = 'Account wurde vorübergehend gesperrt';
				if (!empty($user['suspended_reason'])) {
					$this->loginError .= BR . BR . 'Grund:' . BR . nl2br(h($user['suspended_reason']));
				}
				return false;
			}
			if (isset($user['status']) && defined('User::STATUS_DEL') && $user['status'] == User::STATUS_DEL) {
				$this->loginError = 'Account wurde gelöscht';
				if (!empty($user['suspended_reason'])) {
					$this->loginError .= BR . BR . 'Grund:' . BR . nl2br(h($user['suspended_reason']));
				}
				return false;
			}
			if (isset($user['status']) && defined('User::STATUS_ACTIVE') && $user['status'] != User::STATUS_ACTIVE) {
				$this->loginError = __('Unknown Error');
				return false;
			}
		}
		if (isset($user['email_confirmed']) && empty($user['email_confirmed'])) {
			$this->loginError = __('Email not active yet');
			return false;
		}

		if ($user) {
			# update login counter
			if (isset($user['logins'])) {
				$user['logins'] = $user['logins'] + 1;
				if (method_exists($Model, 'loginUpdate')) {
					$Model->loginUpdate($user);
				}
			}

			$this->Session->renew();
			$this->Session->write(self::$sessionKey, $user);
			$this->Session->write(self::$sessionKey, $this->completeAuth($user));
		}
		return $this->loggedIn();
	}

	/**
	 * @return array $user or bool false on failure
	 * 2011-11-03 ms
	 */
	public function completeAuth($user) {
		$Model = $this->getModel();
		if (!is_array($user)) {
			$user = $Model->get($user);
			if (!$user) {
				return false;
			}
			$user = array_shift($user);
		}

		if (isset($Model->hasMany[$this->intermediateModel]['className'])) {
			$with = $Model->hasMany[$this->intermediateModel]['className'];
		} elseif (isset($Model->belongsTo[$this->roleModel]['className'])) {
			$with = $Model->belongsTo[$this->roleModel]['className'];
		}
		if (empty($with) && $this->settings['parentModelAlias'] !== false) {
			trigger_error('No relation from user to role found');
			return false;
		}

		$completeAuth = array($this->settings['userModel']=>$user);

		# roles
		if (!empty($with)) {
			list($plugin, $withModel) = pluginSplit($with);
			if (!isset($this->{$withModel})) {
				$this->{$withModel} = ClassRegistry::init($with);
			}
			# only for multi
			if ($this->settings['multi'] || !isset($completeAuth[$this->settings['userModel']]['role_id'])) {
				$parentModelAlias = $this->settings['parentModelAlias'];
				$completeAuth[$this->settings['userModel']][$parentModelAlias] = array(); # default: no roles!
				$roles = $this->{$withModel}->find('list', array('fields' => array($withModel.'.role_id'), 'conditions' => array($withModel.'.user_id' => $user['id'])));
				if (!empty($roles)) {
					//$primaryRole = $this->user($this->fieldKey);
					// retrieve associated role that are not the primary one

					# MAYBE USEFUL FOR GUEST!!!
					//$roles = set::extract('/'.$with.'['.$this->fieldKey.'!='.$primaryRole.']/'.$this->fieldKey, $roles);

					// add the suplemental roles id under the Auth session key
					$completeAuth[$this->settings['userModel']][$parentModelAlias] = $roles; // or USER_ROLE_KEY
					//pr($completeAuth);
				}
			} else {
				//$completeAuth[$this->settings['userModel']][$parentModelAlias][] = $completeAuth[$this->settings['userModel']]['role_id'];
			}
		}

		# deprecated!
		if (isset($Model->hasOne['UserInfo'])) {
			$with = $Model->hasOne['UserInfo']['className'];
			list($plugin, $withModel) = pluginSplit($with);
			if (!isset($this->{$withModel})) {
				$this->{$withModel} = ClassRegistry::init($with);
			}
			$infos = $this->{$withModel}->find('first', array('fields' => array(), 'conditions' => array($withModel.'.id' => $user['id'])));

			$completeAuth[$this->settings['userModel']][USER_INFO_KEY] = array(); # default: no rights!
			if (!empty($infos)) {
				$completeAuth[$this->settings['userModel']][USER_INFO_KEY] = $infos[$with];
				//pr($completeAuth);
			}
		}

		# deprecated!
		if (isset($Model->hasOne['UserRight'])) {
			$with = $Model->hasOne['UserRight']['className'];
			list($plugin, $withModel) = pluginSplit($with);
			if (!isset($this->{$withModel})) {
				$this->{$withModel} = ClassRegistry::init($with);
			}
			$rights = $this->{$withModel}->find('first', array('fields' => array(), 'conditions' => array($withModel.'.id' => $user['id'])));

			$completeAuth[$this->settings['userModel']][USER_RIGHT_KEY] = array(); # default: no rights!
			if (!empty($rights)) {
				// add the suplemental roles id under the Auth session key
				$completeAuth[$this->settings['userModel']][USER_RIGHT_KEY] = $rights[$with];
				//pr($completeAuth);
			}
		}

		if (method_exists($Model, 'completeAuth')) {
			$completeAuth = $Model->completeAuth($completeAuth);
			return $completeAuth[$this->settings['userModel']];
		}
		return $completeAuth[$this->settings['userModel']];
	}

	/**
	 * Main execution method. Handles redirecting of invalid users, and processing
	 * of login form data.
	 *
	 * @param Controller $controller A reference to the instantiating controller object
	 * @return boolean
	 */
	public function startup(Controller $controller) {
		//parent::startup($controller);
		if ($controller->name === 'CakeError') {
			return true;
		}

		$methods = array_flip(array_map('strtolower', $controller->methods));
		# fix: reverse camelCase first
		$action = strtolower(Inflector::underscore($controller->request->params['action']));

		$isMissingAction = (
			$controller->scaffold === false &&
			!isset($methods[$action])
		);

		if ($isMissingAction) {
			return true;
		}

		if (!$this->_setDefaults()) {
			return false;
		}
		$request = $controller->request;

		$url = '';

		if (isset($request->url)) {
			$url = $request->url;
		}
		$url = Router::normalize($url);
		$loginAction = Router::normalize($this->loginAction);

		$allowedActions = $this->allowedActions;
		$isAllowed = (
			$this->allowedActions == array('*') ||
			in_array($action, array_map('strtolower', $allowedActions))
		);

		if ($loginAction != $url && $isAllowed) {
			return true;
		}

		if ($loginAction == $url) {
			if (empty($request->data)) {
				if (!$this->Session->check('Auth.redirect') && !$this->loginRedirect && env('HTTP_REFERER')) {
					$this->Session->write('Auth.redirect', $controller->referer(null, true));
				}
			}
			return true;
		} else {
			if (!$this->_getUser()) {
				if (!$request->is('ajax')) {
					$this->flash($this->authError);
					$this->Session->write('Auth.redirect', $request->here());
					$controller->redirect($loginAction);
					return false;
				} elseif (!empty($this->ajaxLogin)) {
					$controller->viewPath = 'Elements';
					echo $controller->render($this->ajaxLogin, $this->RequestHandler->ajaxLayout);
					$this->_stop();
					return false;
				} else {
					$controller->redirect(null, 403);
				}
			}
		}
		if (empty($this->authorize) || $this->isAuthorized($this->user())) {
			return true;
		}

		$this->flash($this->authError);
		$default = '/';
		if (!empty($this->loginRedirect)) {
			$default = $this->loginRedirect;
		}
		$controller->redirect($controller->referer($default), null, true);
		return false;
	}

	/**
	 * @deprecated
	 * @return bool $success
	 */
	public function verifyUser($id, $pwd) {
		trigger_error('deprecated - use Authenticate class');
		$options = array(
			'conditions' => array('id'=>$id, 'password'=>$this->password($pwd)),
		);
		return $this->getModel()->find('first', $options);

		$this->constructAuthenticate();
		$this->request->data['User']['password'] = $pwd;
		return $this->identify($this->request, $this->response);
	}

	/**
	 * returns the current User model
	 * @return object $User
	 */
	public function getModel() {
		$model = $this->settings['userModel'];
		return ClassRegistry::init($model);
	}

}
