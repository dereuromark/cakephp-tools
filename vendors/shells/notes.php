<?php
/**
 * The NotesTask is a source-annotations extractor task for bake2, that allows you to add FIXME, OPTIMIZE,
 * and TODO comments to your source code that can then be extracted in concert with this task
 *
 * PHP versions 4 and 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2006-2007, Joel Moss
 * @link				http://joelmoss.info
 * @since			CakePHP(tm) v 1.2
 * @version			$Version: 1.0 $
 * @modifiedby		$LastChangedBy: joelmoss $
 * @lastmodified	$Date: 2007-02-27 (Tues, 27 Feb 2007) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 * 
 * 
 * @Changelog
 * 
 * v 1.0
 *  [+] Initial code offering
 *  
 * 
 * LOTS OF PROBLEMS - until working...
 * 2009-05-07 ms
 */

App::import('Core','Folder');
App::import('Core','File');

class NotesShell extends Shell {
  var $notes = array();
  var $type = null;
  var $dirs = array(
    'config',
    'controllers',
    'models',
    'plugins',
  );
  
	function main($params = null) {
		$this->welcome();
		
		if (isset($params[0])) {
			if ($params[0] == 'todo') {
  				$this->type = 'TODO';
  			} elseif ($params[0] == 'fixme') {
  				$this->type = 'FIXME';
  			} elseif ($params[0] == 'optimise' || $params[0] == 'optimize') {
  				$this->type = 'OPTIMIZE';
  			} elseif ($params[0] == 'help') {
  				$this->help();
  			}
		}

		$this->read();
		foreach ($this->dirs as $d) {
			$this->read($d, true);
		}
		foreach ($this->notes as $file => $types) {
			$this->out("$file:");
			$this->out('');
			foreach ($types as $type => $notes)
			{
			  foreach ($notes as $ln => $note)
			  {
			    $this->out("   * [$ln] [$type] $note");
			  }
			}
			$this->out('');
		}
		$this->hr();
  }
    
  function read($dir = null, $recursive = false) {
    $notes = array();
    $path = CORE_PATH.APP_PATH.$dir;
		
    $folder = new Folder(APP_PATH.$dir);
    $fold = $recursive ? $folder->findRecursive('.*\.php') : $folder->find('.*\.php');
    foreach ($fold as $file) {
      $file = $recursive ? $file : $path.$file;
      $file_path = r(CORE_PATH.APP_PATH, '', $file);
      
      $handle = new File($file_path);
      $content = $handle->read();
      $lines = explode(PHP_EOL, $content);
      //$lines = file($file);
      $ln = 1;
      if (!empty($lines)) {
	      foreach ($lines as $line) {
	      	if ((is_null($this->type) || $this->type == 'TODO') &&
	      	     preg_match("/[#\*\\/\\/]\s*TODO\s*(.*)/", $line, $match)) {
	      	  $this->notes[$file_path]['TODO'][$ln] = $match[1];
	      	}
	      	if ((is_null($this->type) || $this->type == 'OPTIMIZE') &&
	      	     preg_match("/[#\*\\/\\/]\s*OPTIMIZE|OPTIMISE\s*(.*)/", $line, $match)) {
	      	  $this->notes[$file_path]['OPTIMIZE'][$ln] = $match[1];
	      	}
	      	if ((is_null($this->type) || $this->type == 'FIXME') &&
	      	     preg_match("/[#\*\\/\\/]\s*FIXME|BUG\s*(.*)/", $line, $match)) {
	      	  $this->notes[$file_path]['FIXME'][$ln] = $match[1];
	      	}
	      	$ln++;
	      }
      }
    }
    return $this->notes;
  }
  
  function help() {
    $this->out("This task allows you to add");
    $this->out("FIXME/BUG, OPTIMIZE, and TODO comments to your source, e.g.:");    
    $this->out("# FIXME: blablub");
    $this->out("");
    $this->out("code that can then be extracted in concert with bake2 notes (shows all), bake2");
    $this->out("notes fixme, bake2 notes optimize and bake2 notes todo.");
    $this->out("Usage: bake notes [todo|optimize|fixme]");
    $this->hr();
    exit;
  }
  
  /*
  function out($str='', $newline=true)
  {
    $nl = $newline ? "\n" : "";
    echo "  $str$nl";
  }
  function hr()
  {
    echo "\n  ----------------------------------------------------------------------------\n";
  }
  function err($str)
  {
    $this->out('');
    $this->out('');
    $this->out($str);
    $this->out('');
    $this->out('');
    exit;
  }
  */
  
  function welcome()
  {
    $this->out('');
    $this->hr();
    $this->out('-- Notes --');
    $this->hr();
    $this->out('');
  }
  
}

