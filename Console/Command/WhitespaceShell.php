<?php
App::uses('AppShell', 'Console/Command');
App::uses('Folder', 'Utility');

class WhitespaceShell extends AppShell {

	public $autoCorrectAll = false;
	# each report: [0] => found, [1] => corrected
	public $report = array('leading'=>array(0, 0),'trailing'=>array(0, 0));

	public function main() {
		$App = new Folder(APP);

		$r = $App->findRecursive('.*\.php');
		$this->out("Checking *.php in ".APP);

		$folders = array();

		foreach ($r as $file) {
			$error = '';
			$action = '';

			$c = file_get_contents($file);
			if (preg_match('/^[\n\r|\n\r|\n|\r|\s]+\<\?php/', $c)) {
				$error = 'leading';
			}
			if (preg_match('/\?\>[\n\r|\n\r|\n|\r|\s]+$/', $c)) {
				$error = 'trailing';
			}

			if (!empty($error)) {
				$this->report[$error][0]++;
				$this->out('');
				$this->out('contains '.$error.' whitespaces: '.$this->shortPath($file));

				if (!$this->autoCorrectAll) {
					$dirname = dirname($file);

					if (in_array($dirname, $folders)) {
						$action = 'y';
					}

					while (empty($action)) {
						//TODO: [r]!
						$action = $this->in(__('Remove? [y]/[n], [a] for all in this folder, [r] for all below, [*] for all files(!), [q] to quit'), array('y','n','r','a','q','*'), 'q');
					}
				} else {
					$action = 'y';
				}

				if ($action == '*') {
					$action = 'y';
					$this->autoCorrectAll = true;

				} elseif ($action == 'a') {
					$action = 'y';
					$folders[] = $dirname;
					$this->out('All: '.$dirname);
				}

				if ($action == 'q') {
					die('Abort... Done');

				} elseif ($action == 'y') {
					if ($error == 'leading') {
						$res = preg_replace('/^[\n\r|\n\r|\n|\r|\s]+\<\?php/', '<?php', $c);
					} else { //trailing
						$res = preg_replace('/\?\>[\n\r|\n\r|\n|\r|\s]+$/', '?>', $c);
					}

					file_put_contents($file, $res);
					$this->report[$error][1]++;
					$this->out('fixed '.$error.' whitespaces: '.$this->shortPath($file));
				}
			}
		}

		# report
		$this->out('--------');
		$this->out('found '.$this->report['leading'][0].' leading, '.$this->report['trailing'][0].' trailing ws');
		$this->out('fixed '.$this->report['leading'][1].' leading, '.$this->report['trailing'][1].' trailing ws');
	}

}

