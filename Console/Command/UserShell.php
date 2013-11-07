<?php

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}

App::uses('AppShell', 'Console/Command');
App::uses('ComponentCollection', 'Controller');

/**
 * Create a new user from CLI
 *
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 */
class UserShell extends AppShell {

	public $uses = array(CLASS_USER);

	/**
	 * UserShell::main()
	 * //TODO: refactor (smaller sub-parts)
	 *
	 * @return void
	 */
	public function main() {
		if (App::import('Component', 'Tools.AuthExt') && class_exists('AuthExtComponent')) {
			$this->Auth = new AuthExtComponent(new ComponentCollection());
		} else {
			App::import('Component', 'Auth');
			$this->Auth = new AuthComponent(new ComponentCollection());
		}

		while (empty($username)) {
			$username = $this->in(__('Username (2 characters at least)'));
		}
		while (empty($password)) {
			$password = $this->in(__('Password (2 characters at least)'));
		}

		$schema = $this->User->schema();

		if (isset($this->User->Role) && is_object($this->User->Role)) {
			$roles = $this->User->Role->find('list');

			if (!empty($roles)) {
				$this->out('');
				$this->out(print_r($roles, true));
			}

			$roleIds = array_keys($roles);
			while (!empty($roles) && empty($role)) {
				$role = $this->in(__('Role'), $roleIds);
			}
		} elseif (method_exists($this->User, 'roles')) {
			$roles = User::roles();

			if (!empty($roles)) {
				$this->out('');
				$this->out(print_r($roles, true));
			}

			$roleIds = array_keys($roles);
			while (!empty($roles) && empty($role)) {
				$role = $this->in(__('Role'), $roleIds);
			}
		}
		if (empty($roles)) {
			$this->out('No Role found (either no table, or no data)');
			$role = $this->in(__('Please insert a role manually'));
		}

		$this->out('');
		$this->User->Behaviors->load('Tools.Passwordable', array('confirm' => false));
		//$this->User->validate['pwd']
		$data = array('User' => array(
			'pwd' => $password,
			'active' => 1
		));
		if (!empty($username)) {
			$usernameField = $this->User->displayField;
			$data['User'][$usernameField] = $username;
		}
		if (!empty($email)) {
			$data['User']['email'] = $email;
		}
		if (!empty($role)) {
			$data['User']['role_id'] = $role;
		}

		if (!empty($schema['status']) && method_exists('User', 'statuses')) {
			$statuses = User::statuses();
			$this->out(print_r($statuses, true));
			$status = $this->in(__('Please insert a status'), array_keys($statuses));

			$data['User']['status'] = $status;
		}

		if (!empty($schema['email'])) {
			$provideEmail = $this->in(__('Provide Email? '), array('y', 'n'), 'n');
			if ($provideEmail === 'y') {
				$email = $this->in(__('Please insert an email'));
				$data['User']['email'] = $email;
			}
			if (!empty($schema['email_confirmed'])) {
				$data['User']['email_confirmed'] = 1;
			}
		}

		$this->out('');
		$continue = $this->in(__('Continue?'), array('y', 'n'), 'n');
		if ($continue !== 'y') {
			return $this->error('Not Executed!');
		}

		$this->out('');
		$this->hr();
		if (!$this->User->save($data)) {
			return $this->error('User could not be inserted (' . print_r($this->User->validationErrors, true) . ')');
		}
		$this->out('User inserted! ID: ' . $this->User->id);
	}

}
