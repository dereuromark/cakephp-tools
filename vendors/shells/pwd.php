<?php

class PwdShell extends Shell {
	var $tasks = array();
	//var $uses = array('User');
	
	var $Auth = null;	
	
	function main() {
		$components = array('AuthExt', 'Auth');
		
		foreach ($components as $component) {
			if (App::import('Component', $component)) {
				$component .='Component';
				$this->Auth = new $component();
				break;
			}
		}
		if (!is_object($this->Auth)) {
			$this->out('No Auth Component found');
			die();
		}
		
		$this->out('Using: '.get_class($this->Auth));
		
		while (empty($pwToHash) || mb_strlen($pwToHash) < 2) {
			$pwToHash = $this->in(__('Password to Hash (2 characters at least)', true));
		}
				
		$pw = $this->Auth->password($pwToHash);
		$this->hr();
		echo $pw;	
	}
	
	
	function hash() {
		if (!empty($this->args[0]) && in_array(strtolower($this->args[0]), hash_algos())) {
			$type = strtolower($this->args[0]);
		} else {
			# prompt for one
			$type = $this->in(__('Hash Type', true), array_combine(array_values(hash_algos()), array_values(hash_algos())), 'sha1');
		}
		$pwd = '123';
		$this->hr();
		echo hash($type, $pwd);
	}
	
	function compare() {
		$algos = hash_algos();
		$data = "hello";
		foreach ($algos as $v) { 
			$res = hash($v, $data, false); 
			$r = str_split($res, 50);
			printf("%-12s %3d  %s\n", $v, strlen($res), array_shift($r));
			while (!empty($r)) { 
				printf("                  %s\n", array_shift($r));
			} 
		} 
	}
	
	function help() {
		$this->out('-- Hash Passwort with Auth(Ext) Component --');
		$this->out('-- cake pwd');
		$this->out('---- using the salt of the core.php (!)');
		$this->out('-- cake pwd hash [method]');
		$this->out('---- for custom hashing of pwd strings (method name optional)');
		$this->out('-- cake pwd compare');
		$this->out('---- to list all available methods and their lenghts');
	}
}

