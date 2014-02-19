<?php

/**
 * A wrapper class to generate diffs.
 *
 * Text:
 * - Context
 * - Unified
 * HTML:
 * - Inline
 * - SideBySide
 *
 * Currently uses Horde classes, but could also use PEAR or php-diff packages.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DiffLib {

	/**
	 * What engine should Text_Diff use.
	 * Avaible: auto (chooses best), native, xdiff
	 *
	 * @var string
	 */
	public $engine = 'auto';

	// xdiff needs external libs
	public $engines = array('auto', 'native', 'shell', 'xdiff');

	/**
	 * What renderer to use
	 * for avaible renderers look in Text/Diff/Renderer/*
	 * Standard: unified, context, inline
	 * Additional: sidebyside?
	 *
	 * @var string
	 */
	public $renderer = 'inline';

	public $renderers = array('inline', 'unified', 'context'); //'sidebyside'

	/**
	 * Do you want to use the Character diff renderer additionally to the sidebyside renderer ?
	 * sidebyside renderer is the only one supporting the additional renderer
	 *
	 * @var boolean
	 */
	public $characterDiff = true;

	/**
	 * If the params are strings on what characters do you want to explode the string?
	 * Can be an array if you want to explode on multiple chars
	 *
	 * @var mixed
	 */
	public $explodeOn = "\r\n";

	/**
	 * How many context lines do you want to see around the changed line?
	 *
	 * @var integer
	 */
	public $contextLines = 4;

	/**
	 * DiffLib::__construct()
	 *
	 */
	public function __construct() {
		set_include_path(get_include_path() . PATH_SEPARATOR . CakePlugin::path('Tools') . 'Vendor' . DS);
		App::import('Vendor', 'Tools.HordeAutoloader', array('file' => 'Horde/Autoloader/Default.php'));
	}

	/**
	 * Set/Get renderer
	 *
	 * @param string $renderType
	 * 'unified', 'inline', 'context', 'sidebyside'
	 * defaults to "inline"
	 * @return boolean Success
	 */
	public function renderType($type = null) {
		if ($type === null) {
			return $this->renderer;
		}
		if (in_array($type, $this->renderers)) {
			$this->renderer = $type;
			return true;
		}
		return false;
	}

	/**
	 * Set/Get engine
	 *
	 * @param string $engineType
	 * 'auto', 'native', 'xdiff', 'shell', 'string'
	 * defaults to "auto"
	 * @return boolean Success
	 */
	public function engineType($type = null) {
		if ($type === null) {
			return $this->engine;
		}
		if (in_array($type, $this->engines)) {
			$this->engine = $type;
			return true;
		}
		return false;
	}

	/**
	 * Compare function
	 * Compares two strings/arrays using the specified method and renderer
	 *
	 * @param mixed $original
	 * @param mixed $changed
	 * @param array $options
	 * - div: true/false
	 * - class: defaults to "diff"
	 * - escape: defaults to true
	 * @return string output
	 */
	public function compare($original, $changed, $options = array()) {
		if (!is_array($original)) {
			$original = $this->_explode($original);
		}
		if (!is_array($changed)) {
			$changed = $this->_explode($changed);
		}
		$rendererClassName = 'Horde_Text_Diff_Renderer_' . ucfirst($this->renderer);

		$renderer = new $rendererClassName(array('context_lines' => $this->contextLines, 'character_diff' => $this->characterDiff));
		$diff = new Horde_Text_Diff($this->engine, array($original, $changed));

		$string = $renderer->render($diff);
		return $string;
	}

	/**
	 * @param string $string Either context or unified diff snippet
	 * @param array $options
	 * - mode (autodetect, context, unified)
	 */
	public function reverse($string, $options = array()) {
		$defaults = array(
			'mode' => 'autodetect',
		);
		$options += $defaults;

		$diff = new Horde_Text_Diff('string', array($string, $options['mode']));
		$rendererClassName = 'Horde_Text_Diff_Renderer_' . ucfirst($this->renderer);
		$renderer = new $rendererClassName(array('context_lines' => $this->contextLines, 'character_diff' => $this->characterDiff));
		$string = $renderer->render($diff);
		return $string;
	}

	/**
	 * Explodes the string into an array
	 *
	 * @param string $text
	 * @return array
	 */
	protected function _explode($text) {
		if (is_array($this->explodeOn)) {
			foreach ($this->explodeOn as $explodeOn) {
				$text = explode($explodeOn, $text);
			}
			return $text;
		}
		return explode($this->explodeOn, $text);
	}

	/**
	 * Parses a unified diff output
	 *
	 * @param array $text an entire section of a unified diff (between @@ lines)
	 * @param char $_check a '+' or '-' denoting whether we're looking for lines
	 * added or removed
	 * @return
	 */
	public function parseDiff($text, $_check) {
		$start = 0; // Start of the diff
		$length = 0; // number of lines to recurse
		$changes = array(); // all the changes

		$regs = array();

		if (preg_match("/^@@ ([\+\-])([0-9]+)(?:,([0-9]+))? [\+\-]([0-9]+)(?:,([0-9]+))? @@$/", array_shift($text), $regs) == false) {
			return;
		}

		$start = $regs[4];

		$length = count($text);
		$instance = new Changes();

		/* We don't count removed lines when looking at start of a change. For
		* example, we have this :
		* - foo
		* + bar
		* bar starts at line 1, not line 2.
		*/
		$minus = 0;

		for ($i = 0; $i < $length; $i++) {
			$line = $text[$i];

			// empty line? EOF?
			if (strlen($line) === 0) {
				if ($instance->length > 0) {
					array_push($changes, $instance);
					$instance = new Changes();
				}
				continue;
			}

			if ($_check === '-' && $_check == $line[0]) {
				if ($instance->length == 0) {
					$instance->line = $start + $i - $minus;
					$instance->symbol = $line[0];
					$instance->length++;

				array_push($instance->oldline, substr($line, 1));
			} elseif ($_check === '+' && $_check == $line[0]) {
				if ($instance->length == 0) {
					$instance->line = $start + $i - $minus;
					$instance->symbol = $line[0];
				}
				$instance->length++;
			} else {
				if ($instance->length > 0) {
					array_push($changes, $instance);
					$instance = new Changes();
				}

			}
			if ($line[0] === '-')
				$minus++;
			}
		}
		if ($instance->length > 0) {
			array_push($changes, $instance);
			$instance = new Changes();
		}

		return $changes;
	}

	/**
	 * Appends or Replaces text
	 *
	 * @param array &$text Array of Line objects
	 * @param array $change Array of Change objects
	 * @param integer &$offset how many lines to skip due to previous additions
	 */
	public function applyChange(&$text, $change, &$offset = 0) {
		$index = 0;

		// $i is the change we are on
		for ($i = 0; $i < count($change); $i++) {
			$lines = $change[$i];
			// $j is the line within the change
			for ($j = 0; $j < $lines->length; $j++) {
				$linenum = $lines->line - 1;
				$line = $text[$linenum + $j + $offset];
				$color = 'green';

				if (strlen(ltrim($line->text)) === 0) {
					continue;
				}

				if ($lines->symbol === '-') {
					$add = $lines->oldline;
					array_splice($text, $linenum + $j + $offset, 0, $add);
					// $k is the counter for the old lines we
					// removed from the previous version
					for ($k = 0; $k < count($add); $k++) {
						$l = new Line();
						$l->changed = true;
						$l->symbol = '-';
						$l->text = sprintf("%s <span class='diff-remove'>%s</span>\n", "-", rtrim($add[$k], "\r\n"));

						$text[$linenum + $j + $k + $offset] = $l;
					}
					$offset += count($add);
				} else {
					$l = new Line();
					$l->symbol = '+';
					$l->changed = true;
					$l->text = sprintf("%s <span class='diff-add'>%s</span>\n", $lines->symbol, rtrim($line->text, "\r\n"));
					$text[$linenum + $j] = $l;
				}
			}
		}
	}

}

/*** other files **/

class Changes {

	public $line = 0;

	public $length = 0;

	public $symbol;

	public $oldline = array(); // only for code removed
}

// This object is created for every line of text in the file.
// It was either this, or some funk string changes
class Line {

	public $text = '';

	public $symbol = '';

	public $changed = false;
}
