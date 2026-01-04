<?php
declare(strict_types = 1);

namespace Tools\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Shim\Utility\Inflector;

/**
 * Inflect the heck out of your words.
 *
 * @author Jose Diaz-Gonzalez
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class InflectCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Apply CakePHP inflection rules to a word.';
	}

	/**
	 * Valid inflection rules
	 *
	 * @var array<string>
	 */
	protected array $validActions = [
		'pluralize', 'singularize', 'camelize',
		'underscore', 'humanize', 'tableize',
		'classify', 'variable', 'dasherize',
	];

	/**
	 * Valid inflection rules
	 *
	 * @var array<string>
	 */
	protected array $validCommands = [
		'pluralize', 'singularize', 'camelize',
		'underscore', 'humanize', 'tableize',
		'classify', 'variable', 'dasherize',
		'all', 'quit',
	];

	/**
	 * Hook action for defining this command's option parser.
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
	 * @return \Cake\Console\ConsoleOptionParser The built parser.
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$parser->addArgument('word');
		$parser->addArgument('action');

		return $parser;
	}

	/**
	 * Implement this action with your command's logic.
	 *
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		if ($args->getArguments()) {
			$arguments = $this->_parseArguments($args->getArguments(), $io);
		} else {
			$arguments = $this->_interactive($io);
		}

		if (!$arguments['action'] || !$arguments['word']) {
			return static::CODE_SUCCESS;
		}

		$this->_inflect($arguments['action'], $arguments['word'], $io);
	}

	/**
	 * Prompts the user for word
	 *
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return array
	 */
	protected function _interactive(ConsoleIo $io): array {
		$word = $this->_getWord($io);
		$action = $this->_getAction($io);

		return ['action' => $action, 'word' => $word];
	}

	/**
	 * Requests a valid inflection action
	 *
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return string
	 */
	protected function _getAction(ConsoleIo $io): string {
		$validCharacters = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'q'];
		$validCommands = array_merge($validCharacters, $this->validCommands);

		$command = null;
		while (!$command) {
			$io->out('Please type the number or name of the inflection you would like to use');
			$io->hr();
			$io->out('[1] pluralize');
			$io->out('[2] singularize');
			$io->out('[3] camelize');
			$io->out('[4] underscore');
			$io->out('[5] humanize');
			$io->out('[6] tableize');
			$io->out('[7] classify');
			$io->out('[8] variable');
			$io->out('[9] dasherize');
			$io->out('[a] all');
			$io->out('[q] quit');
			$answer = $io->ask('What action would you like to perform?', 'q');
			if (in_array(strtolower($answer), $validCommands, true) || str_contains($answer, ',')) {
				$command = strtolower($answer);
			} else {
				$io->out('Try again.');
			}
		}

		if (str_contains($command, ',')) {
			$elements = array_map('trim', explode(',', $command));
			foreach ($elements as $element) {
				$action = $element;
				if (is_numeric($action)) {
					$action = $this->validActions[(int)$action - 1] ?? '';
				}

				if (!in_array($action, $this->validActions, true)) {
					$io->error('Invalid action: ' . $element);
					$this->abort(static::CODE_SUCCESS);
				}
			}

			return implode(',', $elements);
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
			case 'a':
			case 'all':
				return 'all';
			case 'q':
			case 'quit':
			default:
				$this->abort(static::CODE_SUCCESS);
		}
	}

	/**
	 * Requests word to inflect
	 *
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return string|null
	 */
	protected function _getWord(ConsoleIo $io): ?string {
		$word = null;
		while (empty($word)) {
			$temp = $io->ask('What word would you like to inflect?');
			if ($temp) {
				$word = $temp;
			} else {
				$io->out('Try again.');
			}
		}

		return $word;
	}

	/**
	 * Parse the arguments into the function and the word to be inflected
	 *
	 * @param array $arguments
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return array
	 */
	protected function _parseArguments(array $arguments, ConsoleIo $io): array {
		$word = array_shift($arguments);

		if (!$arguments) {
			$action = $this->_getAction($io);
		} else {
			$action = array_shift($arguments);
		}

		return ['action' => $action, 'word' => $word];
	}

	/**
	 * Inflects a set of word based upon the inflection set in the arguments
	 *
	 * @param string $function
	 * @param string $word
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return void
	 */
	protected function _inflect(string $function, string $word, ConsoleIo $io): void {
		$io->out($word);

		if (str_contains($function, ',')) {
			$io->out('Chained:');
			$elements = array_map('trim', explode(',', $function));
			foreach ($elements as $element) {
				$action = $element;
				if (is_numeric($action)) {
					$action = $this->validActions[(int)$action - 1] ?? '';
				}

				if (!in_array($action, $this->validActions, true)) {
					$io->error('Invalid action: ' . $element);
					$this->abort(static::CODE_SUCCESS);
				}
				$functionName = $this->_getMessage($action);
				$word = Inflector::$action($word);
				$io->out(" - {$functionName}: " . $word);
			}

			return;
		}

		if ($function === 'all') {
			foreach ($this->validActions as $action) {
				$functionName = $this->_getMessage($action);
				$io->out("{$functionName}: " . Inflector::$action($word));
			}
		} else {
			$functionName = $this->_getMessage($function);
			if (!$functionName) {
				$io->error('Action does not exist');
			}
			$io->out("{$functionName}: " . Inflector::$function($word));
		}
	}

	/**
	 * Returns the appropriate message for a given function
	 *
	 * @param string $function
	 * @return string|null
	 */
	protected function _getMessage(string $function): ?string {
		$messages = [
			'camelize' => 'CamelCase form             ',
			'camelBacked' => 'camelBacked form           ',
			'classify' => 'Cake Model Class form      ',
			'humanize' => 'Human Readable Group form  ',
			'singularize' => 'Singular form              ',
			'dasherize' => 'Dasherized-form            ',
			'pluralize' => 'Pluralized form            ',
			'tableize' => 'table_names form           ',
			'underscore' => 'under_scored_form          ',
			'variable' => 'variableForm               ',
		];

		return $messages[$function] ?? null;
	}

}
