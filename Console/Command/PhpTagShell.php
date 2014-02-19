<?php
App::uses('AppShell', 'Console/Command');
App::uses('Folder', 'Utility');

/**
 * Removes closing php tag (?>) from php files
 * it also makes sure there is no whitespace at the beginning of the file
 *
 * @author Mark Scherer, Maximilian Ruta
 * @cakephp 2.x
 * @license MIT
 */
class PhpTagShell extends AppShell {

	public $autoCorrectAll = false;

	// each report: [0] => found, [1] => corrected
	public $report = array(
		'leading' => array(0, 0),
		'trailing' => array(0, 0)
	);

	public function main() {
		$this->out('Removes closing php tag (?>) from php files.', 2);

		$this->out('Usage: cake Tools.PhpTag run [/path/or/file]');
	}

	/**
	 * Note: uses provided folder (first param)
	 * otherwise complete APP
	 *
	 * @return void
	 */
	public function run() {
		if (isset($this->args[0]) && !empty($this->args[0])) {
			$folder = realpath($this->args[0]);
		} else {
			$folder = APP;
		}
		if (is_file($folder)) {
			$r = array($folder);
		} else {
			$App = new Folder($folder);
			$this->out("Find recursive *.php in [" . $folder . "] ....");
			$r = $App->findRecursive('.*\.php');
		}

		$folders = array();

		foreach ($r as $file) {
			$error = array();
			$action = '';

			$c = file_get_contents($file);
			if (preg_match('/^[\n\r|\n\r|\n|\r|\s]+\<\?php/', $c)) {
				$error[] = 'leading';
			}
			if (preg_match('/\?\>[\n\r|\n\r|\n|\r|\s]*$/', $c)) {
				$error[] = 'trailing';
			}
			if (!empty($error)) {
				foreach ($error as $e) {
					$this->report[$e][0]++;
				}
				$this->out('');
				$this->out('contains ' . rtrim(implode($error, ', '), ', ') . ' whitespaces / php tags: ' . $this->shortPath($file));

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
					$res = $c;
					if (in_array('leading', $error)) {
						$res = preg_replace('/^[\n\r|\n\r|\n|\r|\s]+\<\?php/', '<?php', $res);
					}
					if (in_array('trailing', $error)) {
						$res = preg_replace('/\?\>[\n\r|\n\r|\n|\r|\s]*$/', "\n", $res);
					}
					file_put_contents($file, $res);
					foreach ($error as $e) {
						$this->report[$e][1]++;
						$this->out('fixed ' . $e . ' php tag: ' . $this->shortPath($file));
					}
				}
			}
		}

		// report
		$this->out('--------');
		$this->out('found ' . $this->report['leading'][0] . ' leading, ' . $this->report['trailing'][0] . ' trailing ws / php tag');
		$this->out('fixed ' . $this->report['leading'][1] . ' leading, ' . $this->report['trailing'][1] . ' trailing ws / php tag');
	}

}
