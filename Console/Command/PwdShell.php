<?php
App::uses('AppShell', 'Console/Command');
App::uses('ComponentCollection', 'Controller');

/**
 * Password hashing and output
 *
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 */
class PwdShell extends AppShell {

	/**
	 * PwdShell::hash()
	 *
	 * @return void
	 */
	public function hash() {
		$components = array('Tools.AuthExt', 'Auth');

		$class = null;
		foreach ($components as $component) {
			if (App::import('Component', $component)) {
				$component .= 'Component';
				list($plugin, $class) = pluginSplit($component);
				break;
			}
		}
		if (!$class || !method_exists($class, 'password')) {
			return $this->error(__('No Auth Component found'));
		}

		$this->out('Using: ' . $class);

		while (empty($pwToHash) || mb_strlen($pwToHash) < 2) {
			$pwToHash = $this->in(__('Password to Hash (2 characters at least)'));
		}

		if ($authType = Configure::read('Passwordable.authType')) {
			list($plugin, $authType) = pluginSplit($authType, true);
			$className = $authType . 'PasswordHasher';
			App::uses($className, $plugin . 'Controller/Component/Auth');
			$passwordHasher = new $className();
			$pw = $passwordHasher->hash($pwToHash);
		} else {
			$class = new $class(new ComponentCollection());
			$pw = $class->password($pwToHash);
		}

		$this->hr();
		$this->out($pw);
	}

	/**
	 * PwdShell::help()
	 *
	 * @return void
	 */
	public function help() {
		$this->out('-- Hash Passwort with Auth(Ext) Component --');
		$this->out('-- cake Tools.Pwd hash');
		$this->out('---- using the salt of the core.php (!)');
	}

}
