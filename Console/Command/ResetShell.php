<?php

// Enhancement for plugin user model
if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}
App::uses('AppShell', 'Console/Command');

/**
 * Reset user data
 *
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 */
class ResetShell extends AppShell {

	public $Auth = null;

	/**
	 * ResetShell::main()
	 *
	 * @return void
	 */
	public function main() {
		$this->help();
	}

	/**
	 * Resets all emails - e.g. to your admin email (for local development).
	 *
	 * @return void
	 */
	public function email() {
		$this->out('Email:');
		App::uses('Validation', 'Utility');
		while (empty($email) || !Validation::email($email)) {
			$email = $this->in(__('New email address (must have a valid form at least)'));
		}

		$this->User = ClassRegistry::init(CLASS_USER);
		if (!$this->User->hasField('email')) {
			return $this->error(CLASS_USER . ' model doesnt have an email field!');
		}

		$this->hr();
		$this->out('resetting...');
		Configure::write('debug', 2);
		$this->User->recursive = -1;
		$this->User->updateAll(array('User.email' => '\'' . $email . '\''), array('User.email !=' => $email));
		$count = $this->User->getAffectedRows();
		$this->out($count . ' emails resetted - DONE');
	}

	/**
	 * Resets all pwds to a simple pwd (for local development).
	 *
	 * @return void
	 */
	public function pwd() {
		$components = array('Tools.AuthExt', 'Auth');
		foreach ($components as $component) {
			if (App::import('Component', $component)) {
				$component .= 'Component';
				list($plugin, $component) = pluginSplit($component);
				$this->Auth = new $component(new ComponentCollection());
				break;
			}
		}
		if (!is_object($this->Auth)) {
			return $this->error('No Auth Component found');
		}

		$this->out('Using: ' . get_class($this->Auth) . ' (Abort with STRG+C)');

		if (!empty($this->args[0]) && mb_strlen($this->args[0]) >= 2) {
			$pwToHash = $this->args[0];
		}
		while (empty($pwToHash) || mb_strlen($pwToHash) < 2) {
			$pwToHash = $this->in(__('Password to Hash (2 characters at least)'));
		}
		$this->hr();
		$this->out('Password:');
		$this->out($pwToHash);

		if ($authType = Configure::read('Passwordable.authType')) {
			list($plugin, $authType) = pluginSplit($authType, true);
			$className = $authType . 'PasswordHasher';
			App::uses($className, $plugin . 'Controller/Component/Auth');
			$passwordHasher = new $className();
			$pw = $passwordHasher->hash($pwToHash);
		} else {
			$pw = $this->Auth->password($pwToHash);
		}
		$this->hr();
		$this->out('Hash:');
		$this->out($pw);

		$this->hr();
		$this->out('resetting...');

		$this->User = ClassRegistry::init(CLASS_USER);
		if (!$this->User->hasField('password')) {
			return $this->error(CLASS_USER . ' model doesnt have a password field!');
		}

		$newPwd = '\'' . $pw . '\'';
		$this->User->recursive = -1;
		$this->User->updateAll(array('password' => $newPwd), array('password !=' => $pw));
		$count = $this->User->getAffectedRows();
		$this->out($count . ' pwds resetted - DONE');
	}

	/**
	 * ResetShell::help()
	 *
	 * @return void
	 */
	public function help() {
		$this->out('-- pwd: Hash and Reset all user passwords with Auth(Ext) Component --');
		$this->out('-- email: Reset all user emails --');
	}

}
