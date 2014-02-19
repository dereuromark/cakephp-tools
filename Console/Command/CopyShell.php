<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('AppShell', 'Console/Command');

/** Valid characters: letters,numbers,underscores,hyphens only */
if (!defined('VALID_ALPHANUMERIC_HYPHEN_UNDERSCORE')) {
	define('VALID_ALPHANUMERIC_HYPHEN_UNDERSCORE', '/^[\da-zA-Z_-]+$/');
}
if (!defined('NL')) {
	define('NL', PHP_EOL);
}
if (!defined('WINDOWS')) {
	define('WINDOWS', substr(PHP_OS, 0, 3) === 'WIN' ? true : false);
}

/**
 * A Copy Shell to update changes to your life site.
 * It can also be used for backups and other "copy changes only" services.
 *
 * Using a PPTP tunnel it is also highly secure while being as fast as ftp tools
 * like scp or rsnyc or sitecopy can possibly be.
 *
 * tested on Console:
 * - Windows (XP, Vista, Win7, Win8)
 *
 * tested with:
 * - FTP update (updating a remote server)
 *
 * does not work with:
 * - SFTP (although supported in newest version there are errors)
 *
 * based on:
 * - sitecopy [linux] (maybe switch to "rsync" some time...? way more powerful and reliable!)
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.x
 */
class CopyShell extends AppShell {

	public $scriptFolder = null;

	public $sitecopyFolder = null;

	public $sitecopyFolderName = 'copy';

	public $configCustomFile = 'sitecopy.txt'; // inside /config

	public $configGlobalFile = 'sitecopy_global.txt'; // inside /config

	public $configCount = 0; # total count of all configs (all types)

	const TYPE_APP = 0;
	const TYPE_CAKE = 1;
	const TYPE_PLUGIN = 2;
	const TYPE_VENDOR = 3;
	const TYPE_CUSTOM = 4;

	public $types = array(
		self::TYPE_APP => 'app',
		self::TYPE_CAKE => 'cake',
		self::TYPE_VENDOR => 'vendor',
		self::TYPE_PLUGIN => 'plugin',
		self::TYPE_CUSTOM => 'custom'
	);

	public $matches = array(
		self::TYPE_CAKE => 'lib/Cake',
		self::TYPE_VENDOR => 'vendors', # in root dir
		self::TYPE_PLUGIN => 'plugins', # in root dir
	);

	public $type = self::TYPE_APP;

	public $configName = null; # like "test" in "app_test" or "123" in "custom_123"
	// both typeName and configName form the "site" name: typeName_configName

	public $configCustom = array(); # configFile Content
	public $configGlobal = array(); # configFile Content

	public $tmpFolder = null;

	public $tmpFile = null;

	public $logFile = null;

	public $localFolder = APP;

	public $remoteFolder = null;

	/**
	 * CopyShell::startup()
	 *
	 * @return void
	 */
	public function startup() {
		$this->scriptFolder = dirname(__FILE__) . DS;
		$this->sitecopyFolder = $this->scriptFolder . $this->sitecopyFolderName . DS;
		$this->tmpFolder = TMP . 'cache' . DS . 'copy' . DS;

		/*
		TODO - garbage clean up of log file
		if (file_exists($this->tmpFolder.'log.txt') && (int)round(filesize($this->tmpFolder.'log.txt')/1024) > 2000) { # > 2 MB
			unlink($this->tmpFolder.'log.txt');
		}
		//echo (int)round(filesize($this->tmpFolder.'log.txt')/1024);
		*/
		parent::startup();
	}

	/**
	 * Main method to run updates
	 * to use params you need to explicitly call "Tools.Copy main -params"
	 *
	 * @return void
	 */
	public function run() {
		$configContent = $this->getConfigs();

		// change type if param given
		if (!empty($this->params['cake'])) { # cake core
			$this->type = self::TYPE_CAKE;
		} elseif (!empty($this->params['vendors'])) {
			$this->type = self::TYPE_VENDOR;
		} elseif (!empty($this->params['plugins'])) {
			$this->type = self::TYPE_PLUGIN;
		}
		$this->out($this->types[$this->type]);

		// find all mathing configs to this type
		$configs = array();
		if (!empty($configContent)) {
			$configs = $this->getConfigNames($configContent);
		}
		$this->out('' . count($configs) . ' of ' . $this->configCount . ' configs match:');
		$this->out('');

		$connections = array();

		if (!empty($configs)) {
			//$connections = array_keys($configs); # problems with 0 (mistake in shell::in())
			foreach ($configs as $key => $config) {
				$this->out(($key + 1) . ': ' . $config);
				$connections[] = $key + 1;
			}
		} else {
			$this->out('No configs found in /config/' . $this->configCustomFile . '!');
		}

		if (false) {
		/*
		if (count($connections) == 1) {
			$connection = 1;
		} elseif (isset($this->args[0])) {
			$tryConnection = array_search($this->args[0], $configs);
			if ($tryConnection !== false) {
				$connection = $tryConnection+1;
			}
		*/
		} else {
			array_unshift($connections, 'q', 'h');
			$connection = $this->in(__('Use Sitecopy Config ([q] to quit, [h] for help)') . ':', $connections, 'q');
		}

		$this->out('');

		if (empty($connection) || $connection === 'q') {
			return $this->error('Aborted!');
		}
		if ($connection === 'h') {
			$this->help();
			return;
		}

		if (in_array($connection, $connections) && is_numeric($connection)) {
			$configuration = $this->getConfig($configs[$connection - 1], $configContent);
			//$this->typeName :: $this->types[$this->type]
			$configName = explode('_', $configs[$connection - 1], 2);
			if (!empty($configName[1])) {
				$this->configName = $configName[1];
			} else {
				return $this->error('Invalid config name \'' . $configs[$connection - 1] . '\'');
			}
		}

		// allow c, v and p only with app configs -> set params (by splitting app_configName into app and configName)
		if ($this->type > 3 || $this->type > 0 && $configName[0] !== 'app') {
			return $this->error('"-c" (-cake), "-v" (-vendor) and "-p" (-plugin) only possible with app configs (not with custom ones)');
		}

		if (empty($configuration)) {
			return $this->error('Error...');
		}
		$this->out('... Config \'' . $this->types[$this->type] . '_' . $this->configName . '\' selected ...');

		$hasLocalPath = false;
		$this->out('');
		// display global content (if available)
		if (!empty($this->configGlobal)) {
			//$this->out('GLOBAL CONFIG:');
			foreach ($this->configGlobal as $c) {
				if ($rF = $this->isRemotePath($c)) {
					$this->remoteFolder = $rF;
				} elseif ($this->isLocalPath($c)) {
					$hasLocalPath = true;
				}
				//$this->out($c);
			}
		}

		// display custom content
		//$this->out('CUSTOM CONFIG (may override global config):');
		$this->credentials = array();

		foreach ($configuration as $c) {
			if ($rF = $this->isRemotePath($c)) {
				$this->remoteFolder = $rF;
			} elseif ($lF = $this->isLocalPath($c)) {
				$this->localFolder = $lF;
				$hasLocalPath = true;
			} elseif ($cr = $this->areCredentials($c)) {
				$this->credentials[] = $cr;
			}
		}

		// "vendor" or "cake"? -> change both localFolder and remoteFolder and add them to to the config array
		if ($this->type > 0) {
			$configuration = $this->getConfig($this->types[$this->type], $configContent);
			//pr($configuration);
			$folder = $this->types[$this->type];
			if (!empty($this->matches[$this->type])) {
				$folder = $this->matches[$this->type];
			}

			// working with different OS - best to always use / slash
			$this->localFolder = dirname($this->localFolder) . DS . $folder;
			$this->localFolder = str_replace(DS, '/', $this->localFolder);

			$this->remoteFolder = dirname($this->remoteFolder) . DS . $folder;
			$this->remoteFolder = str_replace(DS, '/', $this->remoteFolder);

			foreach ($this->credentials as $c) {
				$configuration[] = $c;
			}
			$configuration[] = $this->localFolder;
			$configuration[] = $this->remoteFolder;
		}
		/*
		if (!$hasLocalPath) {
			// add the automatically found app folder as local path (default if no specific local path was given)
			$localPath = 'local '.TB.TB.$this->localFolder;
			$this->out($localPath);
			$configuration[] = $localPath;
		}
		*/

		$this->tmpFile = 'config_' . $this->types[$this->type] . '_' . $this->configName . '.tmp';

		$this->logFile = 'log_' . $this->types[$this->type] . '_' . $this->configName . '.txt';

		// create tmp config file (adding the current APP path, of no local path was given inside the config file)
		$File = new File($this->tmpFolder . $this->tmpFile, true, 0770);
		//$File->open();
		$configTotal = array();
		// extract "side xyz" from config, add global and then the rest of custom
		$configTotal[] = 'site ' . $this->types[$this->type] . '_' . $this->configName;//$configuration[0];
		unset($configuration[0]);
		foreach ($this->configGlobal as $c) {
			$configTotal[] = $c;
		}
		foreach ($configuration as $c) {
			$configTotal[] = $c;
		}

		foreach ($configTotal as $key => $val) {
			$this->out($val);
		}

		$File->write(implode(NL, $configTotal), 'w', true);

		while (true) {
			$this->out('');
			$this->out('Type: ' . $this->types[$this->type]);
			$this->out('');
			$allowedActions = array('i', 'c', 'l', 'f', 'u', 's');

			if (isset($this->args[1])) {
				$action = strtolower(trim($this->args[1]));
				$this->args[1] = null; # only the first time
			} elseif (isset($this->args[0])) {
				if (mb_strlen(trim($this->args[0])) === 1) {
					$action = strtolower(trim($this->args[0]));
				}
				$this->args[0] = null; # only the first time
			}
			if (empty($action) || !in_array($action, $allowedActions)) {
				$action = strtolower($this->in(__('Init, Catchup, List, Fetch, Update, Synch (or [q] to quit)') . ':', array_merge($allowedActions, array('q')), 'l'));
			}

			if ($action === 'q') {
				return $this->error('Aborted!');
			}
			if (in_array($action, $allowedActions)) {
				// synch can destroy local information that might not have been saved yet, so confirm
				if ($action === 's') {
					$continue = $this->in(__('Local files might be overridden... Continue?'), array('y', 'n'), 'n');
					if (strtolower($continue) !== 'y' && strtolower($continue) !== 'yes') {
						$action = '';
						continue;
					}
				}

				$options = array();
				$options[] = '--show-progress';

				if (!empty($this->params['force'])) {
					$options[] = '--keep-going';
				}

				$name = $this->types[$this->type] . '_' . $this->configName;
				$this->_execute($name, $action, $options);
			}

			$action = '';
		}
	}

	/**
	 * Only main functions covered - see "sitecopy --help" for more information
	 */
	protected function _execute($config = null, $action = null, $options = array()) {
		$options[] = '--debug=ftp,socket --rcfile=' . $this->tmpFolder . $this->tmpFile .
			' --storepath=' . $this->tmpFolder . ' --logfile=' . $this->tmpFolder . $this->logFile;
		if (!empty($action)) {
			if ($action === 'i') {
				$options[] = '--initialize';
			} elseif ($action === 'c') {
				$options[] = '--catchup';
			} elseif ($action === 'l') {
				$options[] = '--list';
			} elseif ($action === 'f') {
				$options[] = '--fetch';
			} elseif ($action === 'u') {
				$options[] = '--update';
			} elseif ($action === 's') {
				$options[] = '--synchronize';
			}
		}

		#last
		if (!empty($config)) {
			$options[] = $config;
			//pr($options);
		}

		$this->_exec(false, $options);

		// "Job Done"-Sound for the time comsuming actions (could be other sounds as well?)
		if ($action === 'f' || $action === 'u') {
			$this->_beep();
		}
		$this->out('... done ...');
	}

	/**
	 * @return boolean isLocalPath (true/false)
	 */
	protected function isLocalPath($line) {
		if (mb_strlen($line) > 7 && trim(mb_substr($line, 0, 6)) === 'local') {
			$config = trim(str_replace('local ', '', $line));

			if (!empty($config)) {
				return $config;
			}
		}
		return false;
	}

	/**
	 * @return string path on success, boolean FALSE otherwise
	 */
	protected function isRemotePath($line) {
		if (mb_strlen($line) > 8 && trim(mb_substr($line, 0, 7)) === 'remote') {
			$config = trim(str_replace('remote ', '', $line));

			if (!empty($config)) {
				return $config;
			}
		}
		return false;
	}

	/**
	 * @return string path on success, boolean FALSE otherwise
	 */
	protected function areCredentials($line) {
		if (mb_strlen($line) > 8) {
			if (trim(mb_substr($line, 0, 7)) === 'server') {
				$config = trim(str_replace('server ', '', $line));
			} elseif (trim(mb_substr($line, 0, 9)) === 'username') {
				$config = trim(str_replace('username ', '', $line));
			} elseif (trim(mb_substr($line, 0, 9)) === 'password') {
				$config = trim(str_replace('password ', '', $line));
			}

			if (!empty($config)) {
				return $config;
			}
		}
		return false;
	}

	/**
	 * Make a small sound to inform the user accustically about the success.
	 *
	 * @return void
	 */
	protected function _beep() {
		if ($this->params['silent']) {
			return;
		}
		// seems to work only on windows systems - advantage: sound does not need to be on
		$File = new File($this->scriptFolder . 'files' . DS . 'beep.bat');
		$sound = $File->read();
		system($sound);
		// seems to work on only on windows xp systems + where sound is on
		//$sound = 'sndrec32 /play /close "'.$this->scriptFolder.'files'.DS.'notify.wav';
		//system($sound);
		if (WINDOWS) {

		} else {
			exec('echo -e "\a"');
		}
	}

	/**
	 * @return boolean Success
	 */
	protected function _exec($silent = true, $options = array()) {
		// make sure, folder exists
		$Folder = new Folder($this->tmpFolder, true, 0770);

		$f = (WINDOWS ? $this->sitecopyFolder : '') . 'sitecopy ';
		$f .= implode(' ', $options);

		if (!empty($this->params['debug'])) {
			$this->hr();
			$this->out($f);
			$this->hr();
			return true;
		}
		if ($silent !== false) {
			$res = exec($f);
			return $res === 0;
		}
		$res = system($f);
		return $res !== false;
	}

	/**
	 * Displays help contents
	 *
	 * @return void
	 */
	public function help() {
		$this->hr();
		$this->out("Usage: cake copy <arg1> <arg2>...");
		$this->hr();
		$this->out('Commands [arg1]:');
		$this->out("\tName of Configuration (only neccessarry if there are more than 1)");
		$this->out("\nCommands [arg2]:");
		$this->out("\ti: Init (Mark all files and directories as not updated)");
		$this->out("\tc: Catchup (Mark all files and directories as updated)");
		$this->out("\tl: List (Show differences)");
		$this->out("\tf: Fetch (Get differences)");
		$this->out("\tu: Update (Copy local content to remote location)");
		$this->out("\ts: Synch (Get remote content and override local files");
		$this->out("");

		$continue = $this->in(__('Show script help, too?'), array('y', 'n'), 'y');
		if (strtolower($continue) === 'y' || strtolower($continue) === 'yes') {
			// somehow does not work yet (inside cake console anyway...)
			$this->_exec(false, array('--help'));
			$this->out('');
			$this->_exec(false, array('--version'));
		}
		return $this->_stop();
	}

	/**
	 * Read out config file and parse it to an array
	 */
	protected function getConfigs() {
		// global file (may be present)
		$File = new File($this->localFolder . 'config' . DS . $this->configGlobalFile);
		if ($File->exists()) {
			$File->open('r');
			$content = (string)$File->read();
			$content = explode(NL, $content);

			if (!empty($content)) {
				$configGlobal = array();
				foreach ($content as $line => $c) {
					$c = trim($c);
					if (!empty($c)) {
						$configGlobal[] = $c;
					}
				}
				$this->configGlobal = $configGlobal;
			}
		}

		// custom file (must be present)
		$File = new File($this->localFolder . 'config' . DS . $this->configCustomFile);

		if (!$File->exists()) {
			return $this->error('No config file present (/config/' . $this->configCustomFile . ')!');
		}
		$File->open('r');

		// Read out configs
		$content = $File->read();
		if (empty($content)) {
			return array();
		}
		$content = explode(NL, $content);

		if (empty($content)) {
			return array();
		}

		$configContent = array();
		foreach ($content as $line => $c) {
			$c = trim($c);
			if (!empty($c)) {
				$configContent[] = $c;
			}
		}
		return $configContent;
	}

	/**
	 * Get a list with available configs
	 *
	 * @param array $content
	 * checks on whether all config names are valid!
	 */
	protected function getConfigNames($content) {
		$configs = array();
		foreach ($content as $c) {
			if (mb_strlen($c) > 6 && trim(mb_substr($c, 0, 5)) === 'site') {
				$config = trim(str_replace('site ', '', $c));
				if (!empty($config)) {
					if (!$this->isValidConfigName($config)) {
						return $this->error('Invalid Config Name \'' . $config . '\' in /config/' . $this->configCustomFile . '!' . NL . 'Allowed: [app|custom]+\'_\'+{a-z0-9-} or [cake|vendor|plugin]');
					}

					if ($this->typeMatchesConfigName($config, $this->type)) {
						$configs[] = $config;
					}
					$this->configCount++;
				}
			}
		}

		return $configs;
	}

	/**
	 * Makes sure nothing strange happens if there is an invalid config name
	 * (like updating right away on "cake copy u", if u is supposed to be the config name...)
	 */
	protected function isValidConfigName($name) {
		$reservedWords = array('i', 'c', 'l', 'f', 'u', 's');
		if (in_array($name, $reservedWords)) {
			return false;
		}
		if (!preg_match(VALID_ALPHANUMERIC_HYPHEN_UNDERSCORE, $name)) {
			return false;
		}
		if ($name !== 'cake' && $name !== 'vendor' && $name !== 'plugin' && substr($name, 0, 4) !== 'app_' && substr($name, 0, 7) !== 'custom_') {
			return false;
		}
		return true;
	}

	/**
	 * Makes sure type matches config name (app = only app configs, no cake or vendor or custom configs!)
	 *
	 * @return string type on success, otherwise boolean false
	 */
	protected function typeMatchesConfigName($name, $type) {
		if (array_key_exists($type, $this->types) && $name !== 'cake' && $name !== 'vendor' && $name !== 'plugin') {
			$splits = explode('_', $name, 2); # cake_eee_sss = cake & eee_sss
			if (!empty($splits[0]) && in_array(trim($splits[0]), $this->types)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return the specific config of a config name
	 *
	 * @param string $config name
	 * @param array $content
	 */
	protected function getConfig($config, $content) {
		$configs = array();
		$started = false;
		foreach ($content as $c) {
			if (mb_strlen($c) > 6 && substr($c, 0, 5) === 'site ') {
				$currentConfig = trim(str_replace('site ', '', $c));
				if (!empty($currentConfig) && $currentConfig == $config) {
					// start
					if (!$started) {
						// prevent problems with 2 configs with the same alias (but shouldnt happen anyway)
						$currentConfig = null;
					}
					$started = true;
				}
			}

			if ($started && !empty($currentConfig)) {
				// done
				break;
			}

			if ($started) {
				$configs[] = $c;
			}
		}
		return $configs;
	}

	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				/*
				'plugin' => array(
					'short' => 'g',
					'help' => __d('cake_console', 'The plugin to update. Only the specified plugin will be updated.'),
					'default' => ''
				),
				'dry-run'=> array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the update, no files will actually be modified.'),
					'boolean' => true
				),
				'log'=> array(
					'short' => 'l',
					'help' => __d('cake_console', 'Log all ouput to file log.txt in TMP dir'),
					'boolean' => true
				),
				*/
				'silent' => array(
					'short' => 's',
					'help' => __d('cake_console', 'Silent mode (no beep sound)'),
					'boolean' => true
				),
				'vendors' => array(
					'short' => 'e',
					'help' => __d('cake_console', 'ROOT/vendor'),
					'boolean' => true
				),
				'cake' => array(
					'short' => 'c',
					'help' => __d('cake_console', 'ROOT/lib/Cake'),
					'boolean' => true
				),
				'app' => array(
					'short' => 'a',
					'help' => __d('cake_console', 'ROOT/app'),
					'boolean' => true
				),
				'plugins' => array(
					'short' => 'p',
					'help' => __d('cake_console', 'ROOT/plugin'),
					'boolean' => true
				),
				'custom' => array(
					'short' => 'u',
					'help' => __d('cake_console', 'custom'),
					'boolean' => true
				),
				'force' => array(
					'short' => 'f',
					'help' => __d('cake_console', 'force (keep going regardless of errors)'),
					'boolean' => true
				),
				'debug' => array(
					'help' => __d('cake_console', 'Debug output only'),
					'boolean' => true
				)
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "A shell to quickly upload modified files (diff) only."))
			->addSubcommand('run', array(
				'help' => __d('cake_console', 'Update'),
				'parser' => $subcommandParser
			));
	}

}
