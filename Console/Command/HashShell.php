<?php
App::uses('AppShell', 'Console/Command');
//include_once('files/sha256.inc');

class HashShell extends AppShell {

	const DEFAULT_HASH_ALG = 4; # sha1

	public $active = array('md5', 'sha1', 'sha256', 'sha512');

	public $tasks = array();

	/**
	 * Override main() for help message hook
	 *
	 * @return void
	 */
	public function main() {
		$this->out($this->OptionParser->help());
	}

	public function string() {
		$this->out('Hash Strings...');
		$hashAlgos = hash_algos();

		$types = array_merge(array_keys($hashAlgos), array('q'));
		foreach ($hashAlgos as $key => $t) {
			$this->out(($key + 1) . ': ' . $t . (in_array($t, $this->active) ? ' (!)' : ''));
		}
		while (!isset($type) || !in_array($type - 1, $types)) {
			$type = $this->in(__('Select hashType - or [q] to quit'), null, self::DEFAULT_HASH_ALG);
			if ($type === 'q') {
				return $this->error('Aborted!');
			}
		}
		$type--;

		while (empty($pwToHash) || mb_strlen($pwToHash) < 2) {
			$pwToHash = $this->in(__('String to Hash (2 characters at least)'));
		}

		$pw = $this->_hash($hashAlgos[$type], $pwToHash);
		$this->out('type: ' . strtoupper($hashAlgos[$type]) . ' (length: ' . mb_strlen($pw) . ')');
		$this->hr();
		echo $pw;
	}

	/**
	 * List all available
	 */
	public function available() {
		$hashAlgos = hash_algos();
		foreach ($hashAlgos as $hashAlgo) {
			$this->out('- ' . $hashAlgo);
		}
	}

	public function compare() {
		$algos = hash_algos();
		$data = 'hello';
		foreach ($algos as $v) {
			$res = hash($v, $data, false);
			$r = str_split($res, 50);
			printf("%-12s %3d  %s\n", $v, strlen($res), array_shift($r));
			while (!empty($r)) {
				printf("                  %s\n", array_shift($r));
			}
		}
	}

	public function time() {
		$data = '';
		for ($i = 0; $i < 64000; $i++) {
			$data .= hash('md5', rand(), true);
		}
		echo strlen($data) . ' bytes of random data built !' . PHP_EOL . PHP_EOL . 'Testing hash algorithms ...' . PHP_EOL;

		$results = array();
		foreach (hash_algos() as $v) {
			echo $v . PHP_EOL;

			$time = microtime(true);
			hash($v, $data, false);
			$time = microtime(true) - $time;
			$results[$time * 1000000000][] = "$v (hex)";
			$time = microtime(true);
			hash($v, $data, true);
			$time = microtime(true) - $time;
			$results[$time * 1000000000][] = "$v (raw)";
		}

		ksort($results);

		echo PHP_EOL . PHP_EOL . 'Results: ' . PHP_EOL;

		$i = 1;
		foreach ($results as $k => $v) {
		foreach ($v as $k1 => $v1) {
		echo ' ' . str_pad($i++ . '.', 4, ' ', STR_PAD_LEFT) . '  ' . str_pad($v1, 30, ' ') . ($k / 1000000) . ' ms' . PHP_EOL;
			}
		}
	}

	public function help() {
		$this->out('-- Hash Strings --');
		$this->out('-- cake Tools.Hash [method]');
		$this->out('---- for custom hashing of pwd strings (method name optional)');
		$this->out('-- cake Tools.Hash compare');
		$this->out('---- to list all available methods and their lenghts');
	}

	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(__('A console tool for hashing strings'))
			->addSubcommand('string', array(
				'help' => __('Hash a string'),
				'parser' => array(
					'description' => __('hash'),
				)
			))->addSubcommand('compare', array(
				'help' => __('Compare algs'),
				'parser' => array(
					'description' => __('Compare algs'),
				)
			))->addSubcommand('time', array(
				'help' => __('Measure alg times'),
				'parser' => array(
					'description' => __('Measure alg times'),
				)
			))->epilog(
				array(
					__('sha1 is the default algorithm')
				)
			);
		return $parser;
	}

	protected function _hash($type, $string) {
		if (in_array(strtolower($type), hash_algos())) {
			return hash($type, $string);
		}
		return $string;
	}

}
