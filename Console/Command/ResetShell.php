<?php

# enhancement for plugin user model
if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}
App::uses('AppShell', 'Console/Command');

/**
 * reset user data
 *
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 * 2011-11-05 ms
 */
class ResetShell extends AppShell {
	public $tasks = array();
	//public $uses = array('User');

	public $Auth = null;


	public function main() {
		$this->help();
	}

	/**
	 * reset all emails - e.g. your admin email (for local development)
	 * 2011-08-16 ms
	 */
	public function email() {
		$this->out('email:');
		App::uses('Validation', 'Utility');
		while (empty($email) || !Validation::email($email)) {
			$email = $this->in(__('New email address (must have a valid form at least)'));
		}

		$this->User = ClassRegistry::init(CLASS_USER);
		if (!$this->User->hasField('email')) {
			$this->error(CLASS_USER.' model doesnt have an email field!');
		}

		$this->hr();
		$this->out('resetting...');
		Configure::write('debug', 2);
		$this->User->recursive = -1;
		$this->User->updateAll(array('User.email'=>'\''.$email.'\''), array('User.email !='=>$email));
		$count = $this->User->getAffectedRows();
		$this->out($count.' emails resetted - DONE');
	}


	/**
	 * reset all pwds to a simply pwd (for local development)
	 * 2011-08-01 ms
	 */
	public function pwd() {
		$components = array('AuthExt', 'Auth');
		foreach ($components as $component) {
			if (App::import('Component', $component)) {
				$component .='Component';
				$this->Auth = new $component(new ComponentCollection());
				break;
			}
		}
		if (!is_object($this->Auth)) {
			$this->out('No Auth Component found');
			die();
		}

		$this->out('Using: '.get_class($this->Auth).' (Abort with STRG+C)');


		if (!empty($this->args[0]) && mb_strlen($this->args[0]) >= 2) {
			$pwToHash = $this->args[0];
		}
		while (empty($pwToHash) || mb_strlen($pwToHash) < 2) {
			$pwToHash = $this->in(__('Password to Hash (2 characters at least)'));
		}
		$this->hr();
		$this->out('pwd:');
		$this->out($pwToHash);
		$pw = $this->Auth->password($pwToHash);
		$this->hr();
		$this->out('hash:');
		$this->out($pw);

		$this->hr();
		$this->out('resetting...');

		$this->User = ClassRegistry::init(CLASS_USER);
		if (!$this->User->hasField('password')) {
			$this->error(CLASS_USER.' model doesnt have a password field!');
		}

		if (method_exists($this->User, 'escapeValue')) {
			$newPwd = $this->User->escapeValue($pw);
		} else {
			$newPwd = '\''.$pw.'\'';
		}
		$this->User->recursive = -1;
		$this->User->updateAll(array('password'=>$newPwd), array('password !='=>$pw));
		$count = $this->User->getAffectedRows();
		$this->out($count.' pwds resetted - DONE');
	}


	public function help() {
		$this->out('-- pwd: Hash and Reset all user passwords with Auth(Ext) Component --');
		$this->out('-- email: Reset all user emails --');
	}
}
