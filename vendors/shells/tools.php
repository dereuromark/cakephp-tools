<?php

/**
 * Install Tools
 * 2010-06-05 ms
 */
class ToolsShell extends Shell {
	var $uses = array();
	var $tasks = array('DbConfig');

	private $files = array();
	private $folder = null;

	private $models = array('Configuration', 'User', 'Role');
	private $Db;

	function startup() {
		Configure::write('debug', 2);

		foreach ($this->models as $m) {
			$imported = App::import('Model', $m);
			if ($imported === true) {
				$modelname = $m;
				break;
			}
		}
		if (empty($modelname)) {
			$this->error('At least one of the following DB-Tables are required:', implode(', ', $this->models));
		}
		$this->Db = ClassRegistry::init($modelname); //old: new Model();
	}


	function main() {
    $this->out('Install/Manage Tools');
		$this->out('');
		$this->out('Usage:');
		$this->out('- cake install Tool {params}');
		$this->out('- cake uninstall Tool {params}');

		$this->out('');
		$this->out('Tools:');
    $this->_getFiles();
    foreach ($this->files as $file) {
			$this->out('- '.Inflector::camelize(extractPathInfo('file', $file)));
		}
		$this->out('');
		$this->out('Params:');
		$this->out('-f => Force Reinstall (Drop + Create)');
		$this->out('-s => Status (TODO!)');
	}


	function install() {
		if (empty($this->args)) {
			return $this->main();
		}
		$args = $this->args;

		if (!empty($args[0]) && $args[0] == 'all') {
   		$this->_getFiles();
			$args = $this->files;
		}
		if (!empty($this->params['f'])) {
			$this->args = $args;
			$this->uninstall();
		}

		foreach ($args as $arg) {

			if ($sql = $this->_getFile($arg)) {
				$sql = String::insert($sql, array('prefix'=>$this->Db->tablePrefix), array('before'=>'{', 'after'=>'}', 'clean'=>true));
				$this->Db->query($sql);
				$this->out('OK: '.$arg.' created');
			} else {
				$this->out($arg.' not found');
			}
		}

		$this->out('... done');
	}

	function uninstall() {
		if (empty($this->args)) {
			return $this->main();
		}

		$args = $this->args;

		if (!empty($args[0]) && $args[0] == 'all') {
			$this->_getFiles();
			$args = $this->files;
		}

		foreach ($args as $arg) {
			if ($sql = $this->_getFile($arg)) {
				$sqlParts = explode(NL, $sql, 2);
				if (mb_strpos($sqlParts[0], '-- ') !== 0) {
					$this->out('Error: '.$arg);
					continue;
				}
				$sql = trim(mb_substr($sqlParts[0], 3));
				if (!empty($sql)) {
					//$this->Db->execute('DROP TABLE IF EXISTS `'.$this->Db->tablePrefix.$sql.'`;');
					//$this->_ensureDatabaseConnection();
					$this->Db->query('DROP TABLE IF EXISTS `'.$this->Db->tablePrefix.$sql.'`;');
					$this->out('OK: '.$arg.' dropped');
					//die('drop: '.$sql);
				} else {
					$this->out('Error: '.$sql);
				}

			} else {
				$this->out($arg.' not found');
			}
		}

		$this->out('... done');
	}

	/*
	function _ensureDatabaseConnection() {
		if (empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}
		$this->Db =& ConnectionManager::getDataSource($this->connection);
	}
	*/


	function _getFiles() {
		App::import('Core', 'Folder');
		//TODO: make it more generic (could be somewhere else too...)
		$this->folder = APP.'plugins'.DS.'tools'.DS.'config'.DS.'sql'.DS;

		$handle = new Folder($this->folder);
		$content = $handle->read(true, true);
		$this->files = $content[1];
	}

	function _getFile($file) {
		if (empty($this->files)) {
			$this->_getFiles();
		}
		$file = Inflector::underscore($file).'.sql';
		if (file_exists($this->folder.$file)) {
			return file_get_contents($this->folder.$file);
		}
		return false;
	}

}
?>