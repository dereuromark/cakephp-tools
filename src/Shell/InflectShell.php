<?php

namespace Tools\Shell;

use Cake\Console\Shell;
use Shim\Utility\Inflector;

/**
 * Inflect Shell
 *
 * Inflect the heck out of your word(s)
 *
 * @author Jose Diaz-Gonzalez
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class InflectShell extends Shell {

	/**
	 * Valid inflection rules
	 *
	 * @var array
	 */
	protected $validMethods = [
		'pluralize', 'singularize', 'camelize',
		'underscore', 'humanize', 'tableize',
		'classify', 'variable', 'dasherize', 'slug',
	];

	/**
	 * Valid inflection rules
	 *
	 * @var array
	 */
	protected $validCommands = [
		'pluralize', 'singularize', 'camelize',
		'underscore', 'humanize', 'tableize',
		'classify', 'variable', 'dasherize', 'slug', 'all', 'quit',
	];

	/**
	 * Inflects words
	 *
	 * @return bool|int|null|void
	 */
	public function main() {
		if (!empty($this->args)) {
			$arguments = $this->_parseArguments($this->args);
		} else {
			$arguments = $this->_interactive();
		}
		$this->_inflect($arguments['method'], $arguments['words']);
	}

	/**
	 * Prompts the user for words
	 *
	 * @return array
	 */
	protected function _interactive() {
		$method = $this->_getMethod();
		$words = $this->_getWords();
		return ['method' => $method, 'words' => $words];
	}

	/**
	 * Requests a valid inflection method
	 *
	 * @return string|null
	 */
	protected function _getMethod() {
		$validCharacters = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'q'];
		$validCommands = array_merge($validCharacters, $this->validCommands);

		$command = null;
		while (empty($command)) {
			$this->out('Please type the number or name of the inflection method you would like to use');
			$this->hr();
			$this->out('[1] Pluralize');
			$this->out('[2] Singularize');
			$this->out('[3] Camelize');
			$this->out('[4] Underscore');
			$this->out('[5] Humanize');
			$this->out('[6] Tableize');
			$this->out('[7] Classify');
			$this->out('[8] Variable');
			$this->out('[9] Dasherize');
			$this->out('[10] Slug');
			$this->out('[q] Quit');
			$temp = $this->in('What command would you like to perform?', null, 'q');
			if (in_array(strtolower($temp), $validCommands)) {
				$command = strtolower($temp);
			} else {
				$this->out('Try again.');
			}
		}

		switch ($command) {
			case '1':
			case 'pluralize':
				return 'pluralize';
			case '2':
			case 'singularize':
				return 'singularize';
			case '3':
			case 'camelize':
				return 'camelize';
			case '4':
			case 'underscore':
				return 'underscore';
			case '5':
			case 'humanize':
				return 'humanize';
			case '6':
			case 'tableize':
				return 'tableize';
			case '7':
			case 'classify':
				return 'classify';
			case '8':
			case 'variable':
				return 'variable';
			case '9':
			case 'dasherize':
				return 'dasherize';
			case '10':
			case 'slug':
				return 'slug';
			case 'q':
			case 'quit':
			default:
				$this->out('Exit');
				$this->_stop();
				return null;
		}
	}

	/**
	 * Requests words to inflect
	 *
	 * @return string|null
	 */
	protected function _getWords() {
		$words = null;
		while (empty($words)) {
			$temp = $this->in('What word(s) would you like to inflect?');
			if (!empty($temp)) {
				$words = $temp;
			} else {
				$this->out('Try again.');
			}
		}
		return $words;
	}

	/**
	 * Parse the arguments into the function and the word(s) to be inflected
	 *
	 * @param array $arguments
	 * @return array
	 */
	protected function _parseArguments($arguments) {
		$words = null;
		$function = $arguments[0];
		unset($arguments[0]);
		if (!in_array($function, array_merge($this->validMethods, ['all']))) {
			$function = $this->_getMethod();
		}

		$arguments = array_reverse($arguments);
		if (count($arguments) === 0) {
			$words = $this->_getWords();
		} else {
			while (count($arguments) > 0) {
				$words .= array_pop($arguments);
				if (count($arguments) > 0) {
					$words .= ' ';
				}
			}
		}

		return ['method' => $function, 'words' => $words];
	}

	/**
	 * Inflects a set of words based upon the inflection set in the arguments
	 *
	 * @param string $function
	 * @param string $words
	 * @return void
	 */
	protected function _inflect($function, $words) {
		$this->out($words);
		if ($function === 'all') {
			foreach ($this->validMethods as $method) {
				$functionName = $this->_getMessage($method);
				$this->out("{$functionName}: " . Inflector::$method($words));
			}
		} else {
			$functionName = $this->_getMessage($function);
			$this->out("{$functionName}: " . Inflector::$function($words));
		}
	}

	/**
	 * Returns the appropriate message for a given function
	 *
	 * @param string $function
	 * @return string
	 */
	protected function _getMessage($function) {
		$messages = [
			'camelize' => 'CamelCase form             ',
			'classify' => 'Cake Model Class form      ',
			'humanize' => 'Human Readable Group form  ',
			'singularize' =>	'Singular form              ',
			'dasherize' => 'Dasherized-form               ',
			'slug' => 'Slugged-form               ',
			'pluralize' => 'Pluralized form            ',
			'tableize' => 'table_names form           ',
			'underscore' => 'under_scored_form          ',
			'variable' => 'variableForm               ',
		];
		return $messages[$function];
	}

	/**
	 * Displays help contents
	 *
	 * @return void
	 */
	public function help() {
		$this->out('Inflector Shell');
		$this->out('');
		$this->out('This shell uses the Inflector class to inflect any word(s) you wish');
		$this->hr();
		$this->out('Usage: cake inflect');
		$this->out('       cake inflect methodName');
		$this->out('       cake inflect methodName word');
		$this->out('       cake inflect methodName words to inflect');
		$this->out('');
	}

}
