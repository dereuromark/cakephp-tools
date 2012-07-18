<?php
App::uses('AppShell', 'Console/Command');
App::uses('ComponentCollection', 'Controller');

/**
 * password hashing and output
 *
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 * 2011-11-05 ms
 */
class PwdShell extends AppShell {
	public $tasks = array();
	//public $uses = array('User');

	public $Auth = null;

	public function hash() {
		$components = array('Tools.AuthExt', 'Auth');

		$class = null;
		foreach ($components as $component) {
			if (App::import('Component', $component)) {
				$component .='Component';
				list($plugin, $class) = pluginSplit($component);
				break;
			}
		}
		if (!$class || !method_exists($class, 'password')) {
			$this->out(__('No Auth Component found'));
			die();
		}

		$this->out('Using: '.$class);

		while (empty($pwToHash) || mb_strlen($pwToHash) < 2) {
			$pwToHash = $this->in(__('Password to Hash (2 characters at least)'));
		}

		$pw = $class::password($pwToHash);
		$this->hr();
		echo $pw;
	}



	public function help() {
		$this->out('-- Hash Passwort with Auth(Ext) Component --');
		$this->out('-- cake Tools.Pwd hash');
		$this->out('---- using the salt of the core.php (!)');
	}
}

