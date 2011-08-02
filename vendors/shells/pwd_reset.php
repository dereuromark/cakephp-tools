<?php

# enhancement for plugin user model
if (!defined('CLASS_USER')) {
	define('CLASS_USER', 'User');
}

/**
 * reset user passwords
 * 2011-08-01 ms
 */
class PwdResetShell extends Shell {
	var $tasks = array();
	//var $uses = array('User');

	var $Auth = null;

	/**
	 * reset all pwds to a simply pwd (for local development)
	 * 2011-08-01 ms
	 */
	function main() {
		$components = array('AuthExt', 'Auth');
		foreach ($components as $component) {
			if (App::import('Component', $component)) {
				$component .='Component';
				$this->Auth = new $component();
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
			$pwToHash = $this->in(__('Password to Hash (2 characters at least)', true));
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


	function help() {
		$this->out('-- Hash and Reset all user passwords with Auth(Ext) Component --');
	}
}
