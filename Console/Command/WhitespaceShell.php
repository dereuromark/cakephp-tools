<?php
App::uses('AppShell', 'Console/Command');
App::uses('Folder', 'Utility');

class WhitespaceShell extends AppShell {

	public $autoCorrectAll = false;

	// each report: [0] => found, [1] => corrected
	public $report = array('leading' => array(0, 0), 'trailing' => array(0, 0));

	/**
	 * Whitespaces before or after
	 *
	 * @return void
	 */
	public function find() {
		if (!empty($this->args[0])) {
			$folder = realpath($this->args[0]);
		} elseif ($this->params['plugin']) {
			$folder = CakePlugin::path(Inflector::classify($this->params['plugin']));
		} else {
			$folder = APP;
		}
		$App = new Folder($folder);
		$this->out("Checking *.php in " . $folder);

		$files = $App->findRecursive('.*\.php');
		$this->out('Found ' . count($files) . ' files.');

		$action = $this->in(__('Continue? [y]/[n]'), array('y', 'n'), 'n');
		if ($action !== 'y') {
			return $this->error('Aborted');
		}

		$folders = array();

		foreach ($files as $file) {
			$error = '';
			$action = '';
			$this->out('Processing ' . $file, 1, Shell::VERBOSE);

			$c = file_get_contents($file);
			if (preg_match('/^[\n\r|\n\r|\n|\r|\s]+\<\?php/', $c)) {
				$error = 'leading';
			}
			if (preg_match('/\?\>[\n\r|\n\r|\n|\r|\s]+$/', $c)) {
				$error = 'trailing';
			}

			if (empty($error)) {
				continue;
			}
			$this->report[$error][0]++;
			$this->out('');
			$this->out('contains ' . $error . ' whitespaces: ' . $this->shortPath($file));

			if (!$this->autoCorrectAll) {
				$dirname = dirname($file);

				if (in_array($dirname, $folders)) {
					$action = 'y';
				}

				while (empty($action)) {
					//TODO: [r]!
					$action = $this->in(__('Remove? [y]/[n], [a] for all in this folder, [r] for all below, [*] for all files(!), [q] to quit'), array('y', 'n', 'r', 'a', 'q', '*'), 'q');
				}
			} else {
				$action = 'y';
			}

			if ($action === '*') {
				$action = 'y';
				$this->autoCorrectAll = true;

			} elseif ($action === 'a') {
				$action = 'y';
				$folders[] = $dirname;
				$this->out('All: ' . $dirname);
			}

			if ($action === 'q') {
				return $this->error('Abort... Done');

			}
			if ($action === 'y') {
				if ($error === 'leading') {
					$res = preg_replace('/^[\n\r|\n\r|\n|\r|\s]+\<\?php/', '<?php', $c);
				} else { //trailing
					$res = preg_replace('/\?\>[\n\r|\n\r|\n|\r|\s]+$/', '?>', $c);
				}

				file_put_contents($file, $res);
				$this->report[$error][1]++;
				$this->out('fixed ' . $error . ' whitespaces: ' . $this->shortPath($file));
			}
		}

		// Report.
		$this->out('--------');
		$this->out('found ' . $this->report['leading'][0] . ' leading, ' . $this->report['trailing'][0] . ' trailing ws');
		$this->out('fixed ' . $this->report['leading'][1] . ' leading, ' . $this->report['trailing'][1] . ' trailing ws');
	}

	/**
	 * Whitespaces at the end of the file
	 *
	 * @return void
	 */
	public function eof() {
		if (!empty($this->args[0])) {
			$folder = realpath($this->args[0]);
		} else {
			$folder = APP;
		}
		$App = new Folder($folder);
		$this->out("Checking *.php in " . $folder);

		$files = $App->findRecursive('.*\.php');

		$this->out('Found ' . count($files) . ' files.');

		$action = $this->in(__('Continue? [y]/[n]'), array('y', 'n'), 'n');
		if ($action !== 'y') {
			return $this->error('Aborted');
		}

		foreach ($files as $file) {
			$this->out('Processing ' . $file, 1, Shell::VERBOSE);
			$content = $store = file_get_contents($file);

			$newline = PHP_EOL;
			$x = substr_count($content, "\r\n");
			if ($x > 0) {
				$newline = "\r\n";
			} else {
				$newline = "\n";
			}

			// add one new line at the end
			$content = trim($content) . $newline;
			if ($content !== $store) {
				file_put_contents($file, $content);
			}
		}
		$this->out('Done');
	}

	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
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
				'plugin' => array(
					'short' => 'p',
					'help' => __d('cake_console', 'Plugin'),
					'default' => '',
				),
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', 'The Whitespace Shell removes uncessary/wrong whitespaces.
Either provide a path as first argument, use -p PluginName or run it as it is for the complete APP dir.'))
			->addSubcommand('find', array(
				'help' => __d('cake_console', 'Detect any leading/trailing whitespaces'),
				'parser' => $subcommandParser
			))
			->addSubcommand('eof', array(
				'help' => __d('cake_console', 'Fix whitespaces at the end of PHP files (a single newline as per coding standards)'),
				'parser' => $subcommandParser
			));
	}

}
