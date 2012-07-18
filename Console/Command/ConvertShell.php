<?php

if (!defined('CHMOD_PUBLIC')) {
	define('CHMOD_PUBLIC', 0770);
}
App::uses('AppShell', 'Console/Command');

/**
 * uses dos2unix >= 5.0
 * console call: dos2unix [-fhkLlqV] [-c convmode] [-o file ...] [-n inputfile outputfile ...]
 *
 * @cakephp 2.0
 * @author Mark Scherer
 * @license MIT
 * 2011-11-04 ms
 */
class ConvertShell extends AppShell {
	public $uses = array();


	/**
	 * predefined options
	 */
	public $modes = array(
		'd2u', 'u2d', 'git', # dos/unix
		'd2m', 'm2d', # dos/mac
		'u2m', 'm2u' # unix/mac
	);

	/**
	 * Shell startup, prints info message about dry run.
	 *
	 * @return void
	 */
	public function startup() {
		parent::startup();

		if ($this->params['dry-run']) {
			$this->out(__d('cake_console', '<warning>Dry-run mode enabled!</warning>'), 1, Shell::QUIET);
		}
		if (!$this->_test()) {
			$this->out(__d('cake_console', '<warning>dos2unix not available</warning>'), 1, Shell::QUIET);
		}
	}




	public function folder() {
		$this->out('Converting folder...');

		$folder = APP;
		$mode = $this->params['mode'];
		if (empty($mode) || !in_array($mode, $this->modes)) {
			$this->error('Invalid mode', 'Please specify d2u, u2d, git (d2u+u2d) ...');
		}
		if (!empty($this->args)) {
			$folder = array_shift($this->args);
			$folder = realpath($folder);
		}
		if (empty($folder)) {
			$this->error('Invalid dir', 'No valid dir given (either absolute or relative to APP)');
		}

		$this->_convert($folder, $mode);
		$this->out('Done!');
	}


	public function _test() {
		# bug - always outputs the system call right away, no way to catch and surpress it
		return true;

		ob_start();
		system('dos2unix -h', $x);
		$output = ob_get_contents();
		ob_end_clean();

		return !empty($output) && $x === 0;
	}


	public function _convert($dir, $mode, $excludes = array()) {
		$Iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
			RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($Iterator as $path) {
			$fullPath = $path->__toString();
			$continue = false;

			foreach ($excludes as $exclude) {
				if (strpos($fullPath, $exclude) === 0) {
					$continue = true;
					break;
				}
			}
			if ($continue) {
				continue;
			}

			if ($path->isDir()) {
				continue;
			}
			if (strpos($fullPath, DS.'.') !== false) {
				continue;
			}
			if (!empty($this->params['verbose'])) {
				$this->out('Converting file: '.$fullPath);
			}
			if (empty($this->params['dry-run'])) {
				ob_start();
				if ($mode == 'git') {
					system('dos2unix --'.'d2u'.' --skipbin '.$fullPath, $x);
					system('dos2unix --'.'u2d'.' --skipbin '.$fullPath, $x);
				} else {
					system('dos2unix --'.$mode.' --skipbin '.$fullPath, $x);
				}
				$output = ob_get_contents();
				ob_end_clean();
			}
		}
	}



	/**
	 * get the option parser
	 *
	 * @return ConsoleOptionParser
	 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'mode' => array(
					'short' => 'm',
					'help' => __d('cake_console', 'Mode'),
					'default' => '' # auto detect
				),
				'ext' => array(
					'short' => 'e',
					'help' => __d('cake_console', 'Specify extensions [php|txt|...]'),
					'default' => '',
				),
				'dry-run'=> array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the clear command, no files will actually be deleted. Should be combined with verbose!'),
					'boolean' => true
				),
				'exclude'=> array(
					'short' => 'x',
					'help' => __d('cake_console', 'exclude the following files or folders'),
					'boolean' => true,
					'default' => ''
				)
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "The Convert Shell converts files from dos/unix/mac to another system"))
			->addSubcommand('folder', array(
				'help' => __d('cake_console', 'Convert folder recursivly (Tools.Convert folder [options] [path])'),
				'parser' => $subcommandParser
			));
	}

}
