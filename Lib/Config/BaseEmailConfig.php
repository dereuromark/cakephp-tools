<?php
// Support BC (snake case config)
if (!Configure::read('Mail.smtpHost')) {
	Configure::write('Mail.smtpHost', Configure::read('Mail.smtp_host'));
}
if (!Configure::read('Mail.smtpUsername')) {
	Configure::write('Mail.smtpUsername', Configure::read('Mail.smtp_username'));
}
if (!Configure::read('Mail.smtpPassword')) {
	Configure::write('Mail.smtpPassword', Configure::read('Mail.smtp_password'));
}

/**
 * BaseEmailConfig for APP/Config/email.php
 *
 * Defaults to `Smtp` as transport.
 *
 * You can set up your $default and other configs without having to specify a password
 * Those will be read from Configure::read('Email.Pwd').
 *
 * You can also specify a few more things via Configure, e.g. 'Email.live' to force sending emails.
 * Per default it would not send mails in debug mode, but log them away.
 *
 * Additionally, you can set custom SMTP configs via Configure::read('Mail'):
 * - Smtp.host
 * - Smtp.username
 * - Smtp.password
 * Those will then be merged in.
 *
 * Your email.php config file then should not contain any sensitive information and can be part of version control.
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
		if (!empty($this->default['log'])) {
			$this->default['report'] = true;
		}
		if (isset($this->default['log'])) {
			unset($this->default['log']);
		}
		if (isset($this->default['trace'])) {
			$this->default['log'] = 'email_trace';
		}

		// Depreated, use Email.[TransportClass]. instead
		if ($config = Configure::read('Mail')) {
			if (!empty($config['smtpHost'])) {
				$this->default['host'] = $config['smtpHost'];
			}
			if (!empty($config['smtpUsername'])) {
				$this->default['username'] = $config['smtpUsername'];
			}
			if (!empty($config['smtpPassword'])) {
				$this->default['password'] = $config['smtpPassword'];
			}
			if (!empty($config['smtpPort'])) {
				$this->default['port'] = $config['smtpPort'];
			}
			if (isset($config['smtpTimeout'])) {
				$this->default['timeout'] = $config['smtpTimeout'];
			}
			if (isset($config['smtpTls'])) {
				$this->default['tls'] = $config['smtpTls'];
			}
		}
		// Add transport specific Configure settings
		if ($config = Configure::read('Email.' . $this->default['transport'])) {
			$this->default = $config + $this->default;
		}

		// Add password data from Configure
		$pwds = (array)Configure::read('Email.Pwd');
		foreach ($pwds as $key => $val) {
			if (isset($this->{$key})) {
				$this->{$key}['password'] = $val;
			}
		}

		// Prevent debug mails to be accidently sent
		if (Configure::read('debug') && !Configure::read('Email.live')) {
			$this->default['transport'] = 'Debug';
			if (!isset($this->default['trace'])) {
				$this->default['log'] = 'email_trace';
			}
		}
	}

}