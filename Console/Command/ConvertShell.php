<?php

if (!defined('CHMOD_PUBLIC')) {
	define('CHMOD_PUBLIC', 0770);
}
App::uses('AppShell', 'Console/Command');

/**
 * A convert shell to quickly convert/correct the type of line endings in files.
 * It recursivly walks through a specified folder.
 * Uses dos2unix >= 6.0 (should contain 3 separate command tools).
 *
 * Console call:
 *   dos2unix [-fhkLlqV] [-c convmode] [-o file ...] [-n inputfile outputfile ...]
 *
 * It is also possible to manually define the binPath (for Windows for example).
 *
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 */
class ConvertShell extends AppShell {

	/**
	 * Predefined options
	 *
	 * @var array
	 */
	public $modes = array(
		'git' => array('dos2unix', 'unix2dos'), # d2u + u2d
		'd2u' => 'dos2unix', 'u2d' => 'unix2dos', # dos/unix
		'u2m' => 'unix2mac', 'm2u' => 'mac2unix', # unix/mac
		'd2m' => array('dos2unix', 'unix2mac'), 'm2d' => array('mac2unix', 'unix2dos'), # dos/mac
	);

	/**
	 * @var string
	 */
	public $binPath;

	/**
	 * Shell startup, prints info message about dry run.
	 *
	 * @return void
	 */
	public function startup() {
		parent::startup();

		$this->binPath = Configure::read('Cli.dos2unixPath');

		if ($this->params['dry-run']) {
			$this->out(__d('cake_console', '<warning>Dry-run mode enabled!</warning>'), 1, Shell::QUIET);
		}
		if (false && !$this->_test()) {
			$this->out(__d('cake_console', '<warning>dos2unix not available</warning>'), 1, Shell::QUIET);
		}
	}

	/**
	 * ConvertShell::folder()
	 *
	 * @return void
	 */
	public function folder() {
		$this->out('Converting folder...');

		$folder = APP;
		$mode = $this->params['mode'];
		if (empty($mode) || !array_key_exists($mode, $this->modes)) {
			return $this->error('Invalid mode', 'Please specify d2u, u2d, git (d2u+u2d) ...');
		}
		if (!empty($this->args)) {
			$folder = array_shift($this->args);
			$folder = realpath($folder);
		}
		if (empty($folder)) {
			return $this->error('Invalid dir', 'No valid dir given (either absolute or relative to APP)');
		}

		$this->_convert($folder, $mode);
		$this->out('Done!');
	}

	public function version() {
		$this->_test();
	}

	/**
	 * ConvertShell::version()
	 * //TODO: fixme, always outputs right away..
	 *
	 * @return void
	 */
	protected function _test() {
		ob_start();
		exec($this->binPath . 'dos2unix --version', $output, $x);
		$output = ob_get_contents();
		ob_end_clean();
		return !empty($output) && $x === 0;
	}

	/**
	 * ConvertShell::_convert()
	 *
	 * @param mixed $dir
	 * @param mixed $mode
	 * @param mixed $excludes
	 * @return void
	 */
	protected function _convert($dir, $mode, $excludes = array()) {
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
			if (strpos($fullPath, DS . '.') !== false) {
				continue;
			}
			if (!empty($this->params['verbose'])) {
				$this->out('Converting file: ' . $fullPath);
			}
			if (empty($this->params['dry-run'])) {
				ob_start();
				$commands = (array)$this->modes[$mode];
				foreach ($commands as $command) {
					$this->out('Running', 1, Shell::VERBOSE);
					system($this->binPath . $command . ' ' . $fullPath, $x);
				}
				$output = ob_get_contents();
				ob_end_clean();
			}
		}
	}

	/**
	 * Get the option parser
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
				'dry-run' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the clear command, no files will actually be deleted. Should be combined with verbose!'),
					'boolean' => true
				),
				'exclude' => array(
					'short' => 'x',
					'help' => __d('cake_console', 'exclude the following files or folders'),
					'boolean' => true,
					'default' => ''
				)
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "The Convert Shell converts files from dos/unix/mac to another system"))
			->addSubcommand('version', array(
				'help' => __d('cake_console', 'Test and display version.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('folder', array(
				'help' => __d('cake_console', 'Convert folder recursivly (Tools.Convert folder [options] [path])'),
				'parser' => $subcommandParser
			));
	}

}
