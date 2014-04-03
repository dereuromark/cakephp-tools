<?php
// Support BC (snake case config)
if (!Configure::read('Mail.smtp_host')) {
	Configure::write('Mail.smtpHost', Configure::read('Mail.smtp_host'));
}
if (!Configure::read('Mail.smtp_username')) {
	Configure::write('Mail.smtpUsername', Configure::read('Mail.smtp_username'));
}
if (!Configure::read('Mail.smtp_password')) {
	Configure::write('Mail.smtpPassword', Configure::read('Mail.smtp_password'));
}

/**
 * BaseEmailConfig for APP/Config/email.php
 */
class BaseEmailConfig {

	public $default = array(
		'transport' => 'Smtp',
	);

	/**
	 * Read Configure Email pwds and assign them to the configs.
	 * Also assigns custom Mail config as well as log/trace configs.
	 */
	public function __construct() {
		$pwds = (array)Configure::read('Email.Pwd');
		foreach ($pwds as $key => $val) {
			if (isset($this->{$key})) {
				$this->{$key}['password'] = $val;
			}
		}

		if (!empty($this->default['log'])) {
			$this->default['report'] = true;
		}
		if (isset($this->default['log'])) {
			unset($this->default['log']);
		}
		if (isset($this->default['trace'])) {
			$this->default['log'] = 'email_trace';
		}

		if (Configure::read('debug') && !Configure::read('Email.live')) {
			$this->default['transport'] = 'Debug';
			if (!isset($this->default['trace'])) {
				$this->default['log'] = 'email_trace';
			}
		}
		if ($config = Configure::read('Mail')) {
			if (!empty($config['smtp_host'])) {
				$this->default['host'] = $config['smtpHost'];
			}
			if (!empty($config['smtp_username'])) {
				$this->default['username'] = $config['smtpUsername'];
			}
			if (!empty($config['smtp_password'])) {
				$this->default['password'] = $config['smtpPassword'];
			}
		}
	}

}