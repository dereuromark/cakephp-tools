<?php
App::uses('ConsoleOutput', 'Console');

/**
 * Use as
 *
 *  App::uses('MyConsoleOutput', 'Tools.TestSuite');
 *
 *  $stdOut = new MyConsoleOutput();
 *  $this->MyShell = new MyShell($stdOut);
 *
 * @license MIT
 * @author Mark Scherer
 */
class MyConsoleOutput extends ConsoleOutput {

	/**
	 * Holds all output messages.
	 *
	 * @var array
	 */
	public $output = array();

	/**
	 * Overwrite _write to output the message to debug instead of CLI.
	 *
	 * @param string $message
	 * @return void
	 */
	protected function _write($message) {
		$this->output[] = $message;
	}

	/**
	 * Helper method to return the debug output as string.
	 *
	 * @return string
	 */
	public function output() {
		return implode('', $this->output);
	}

}