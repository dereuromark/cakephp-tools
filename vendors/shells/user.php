<?php

if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}

class UserShell extends Shell {
	var $tasks = array();
	var $uses = array(CLASS_USER);

	/*
	function initialize() {
		//Configure::write('debug', 2);

		parent::initialize();
		//$this->User = ClassRegistry::init('User');
	}
	*/

	function help() {
		$this->out('command: cake user');
	}


	//TODO: refactor (smaller sub-parts)
	function main() {
		if (App::import('Component', 'AuthExt')) {
			$this->Auth = new AuthExtComponent();
		} else {
			App::import('Component', 'Auth');
			$this->Auth = new AuthComponent();
		}

		while (empty($username)) {
			$username = $this->in(__('Username (2 characters at least)', true));
		}
		while (empty($password)) {
			$password = $this->in(__('Password (2 characters at least)', true));
		}

		$schema = $this->User->schema();

		if (isset($this->User->Role) && is_object($this->User->Role)) {
			$roles = $this->User->Role->find('list');

			if (!empty($roles)) {
				$this->out('');
				pr($roles);
			}

			$roleIds = array_keys($roles);
			while (!empty($roles) && empty($role)) {
				$role = $this->in(__('Role', true), $roleIds);
			}
		} elseif (method_exists($this->User, 'roles')) {
			$roles = User::roles();

			if (!empty($roles)) {
				$this->out('');
				pr ($roles);
			}

			$roleIds = array_keys($roles);
			while (!empty($roles) && empty($role)) {
				$role = $this->in(__('Role', true), $roleIds);
			}
		}
		if (empty($roles)) {
			$this->out('No Role found (either no table, or no data)');
			$role = $this->in(__('Please insert a role manually', true));
		}

		$this->out('');
		$pwd = $this->Auth->password($password);

		$data = array('User'=>array(
			'password' => $pwd,
			'active' => 1
		));
		if (!empty($username)) {
			$data['User']['username'] = $username;
		}
		if (!empty($email)) {
			$data['User']['email'] = $email;
		}
		if (!empty($role)) {
			$data['User']['role_id'] = $role;
		}

		if (!empty($schema['status']) && method_exists('User', 'statuses')) {
			$statuses = User::statuses();
			pr($statuses);
			while(empty($status)) {
				$status = $this->in(__('Please insert a status', true), array_keys($statuses));
			}
			$data['User']['status'] = $status;
		}

		if (!empty($schema['email'])) {
			$provideEmail = $this->in(__('Provide Email? ', true),array('y', 'n'), 'n');
			if ($provideEmail === 'y') {
				$email = $this->in(__('Please insert an email', true));
				$data['User']['email'] = $email;
			}
			if (!empty($schema['email_confirmed'])) {
				$data['User']['email_confirmed'] = 1;
			}
		}


		$this->out('');
		pr ($data);
		$this->out('');
		$this->out('');
		$continue = $this->in(__('Continue? ', true),array('y', 'n'), 'n');
		if ($continue != 'y') {
			$this->error('Not Executed!');
		}

		$this->out('');
		$this->hr();
		if ($this->User->save($data)) {
			$this->out('User inserted! ID: '.$this->User->id);
		} else {
			$this->error('User could not be inserted ('.print_r($this->User->validationErrors, true).')');
		}
	}
}

