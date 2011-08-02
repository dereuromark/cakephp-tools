<?php

App::import('Core', 'Folder');
App::import('Core', 'File');

if (!defined('LF')) {
	define('LF', "\r\n"); # windows compatible as default
}

/**
 * Code Completion
 * 2009-12-26 ms
 */
class CcShell extends Shell {
	var $uses = array();

	protected $plugins = null;
	protected $content = '';

	function main() {
		$this->out('Code Completion Dump - customized for PHPDesigner');

		//TODO: ask for version (1.2 etc - defaults to 1.3!)

		$this->filename = APP.'code_completion__.php';

		# get classes
		$this->models();
		$this->behaviors();
		
		$this->components();
		$this->helpers();
		//TODO: behaviors

		# write to file
		$this->_dump();

		$this->out('...done');
	}

	/**
	 * @deprecated
	 * now use: Configure::listObjects()
	 */
	function __getFiles($folder) {
		$handle = new Folder($folder);
		$handleFiles = $handle->read(true, true);
		$files = $handleFiles[1];
		foreach ($files as $key => $file) {
			$file = extractPathInfo('file', $file);

			if (mb_strrpos($file, '_') === mb_strlen($file) - 1) { # ending with _ like test_.php
				unset($files[$key]);
			} else {
				$files[$key] = Inflector::camelize($file);
			}
		}
		return $files;
	}


	public function _getFiles($type) {
    $files = App::objects($type, null, false);
    
    //$paths = (array)App::path($type.'s');
    //$libFiles = App::objects($type, $paths[0] . 'lib' . DS, false);

    if (!isset($this->plugins)) {
    	$this->plugins = App::objects('plugin');
    }
    
    if (!empty($this->plugins)) {
      foreach ($this->plugins as $plugin) {
      	$path = App::pluginPath($plugin);
      	if ($type == 'helper') {
      		$path .= 'views' . DS;
      	} elseif ($type == 'components') {
      		$path .= 'controllers' . DS;
      	} elseif ($type == 'behavior') {
      		$path .= 'models' . DS;
      	} elseif ($type == 'datasources') {
      		$path .= 'models' . DS;
      	} 
				$path .= $type.'s' . DS;
      	
				$pluginFiles = App::objects($type, $path, false);
				if (!empty($pluginFiles)) {
				    foreach ($pluginFiles as $t) {
				        $files[] = $t;
				    }
				}
      }
    }
    $files = array_unique($files);

		$appIndex = array_search('App', $files);
		if ($appIndex !== false) {
			unset($files[$appIndex]);
		}

		# no test/tmp files etc (helper.test.php or helper.OLD.php)
    foreach ($files as $key => $file) {
			if (strpos($file, '.') !== false || !preg_match('/^[\da-zA-Z_]+$/', $file)) {
				unset($files[$key]);
			}
		}
    return $files;
	}


	function models() {
		$files = $this->_getFiles('model');

		$content = LF.'<?php'.LF;
		$content .= '/*** model start ***/'.LF;
		$content .= 'class AppModel extends Model {'.LF;
		if (!empty($files)) {
			$content .= $this->_prepModels($files);
		}
		
		$content .= '}'.LF;
		$content .= '/*** model end ***/'.LF;
		$content .= '?>';

		$this->content .= $content;
	}
	
	function behaviors() {
		$files = $this->_getFiles('behavior');

		$content = LF.'<?php'.LF;
		$content .= '/*** behavior start ***/'.LF;
		$content .= 'class AppModel extends Model {'.LF;
		if (!empty($files)) {
			$content .= $this->_prepBehaviors($files);
		}
		$content .= '}'.LF;
		$content .= '/*** behavior end ***/'.LF;
		$content .= '?>';
		
		$content .= '/*** model start ***/'.LF;
		
		$this->content .= $content;
	}
	
	function components() {
		$files = $this->_getFiles('component');

		$content = LF.'<?php'.LF;
		$content .= '/*** component start ***/'.LF;
		$content .= 'class AppController extends Controller {'.LF;
		if (!empty($files)) {
			$content .= $this->_prepComponents($files);
		}
		$content .= '}'.LF;
		$content .= '/*** component end ***/'.LF;
		$content .= '?>';

		$this->content .= $content;
	}

	function helpers() {
		$files = $this->_getFiles('helper');
		$content = LF.'<?php'.LF;
		$content .= '/*** helper start ***/'.LF;
		$content .= 'class AppHelper extends Helper {'.LF;
		if (!empty($files)) {
			$content .= $this->_prepHelpers($files);
		}
		$content .= '}'.LF;
		$content .= '/*** helper end ***/'.LF;
		$content .= '?>';

		$this->content .= $content;
	}

	function _prepModels($files) {
		$res = '';
		foreach ($files as $name) {
			$res .= '
	/**
	* '.$name.'
	*
	* @var '.$name.'
	*/
	public $'.$name.';
'.LF;
		}

		$res .= '	function __construct() {';

		foreach ($files as $name) {
			$res .= '
		$this->'.$name.' = new '.$name.'();';
		}

		$res .= LF.'	}'.LF;
		return $res;
	}
	
	function _prepBehaviors($files) {
		$res = '';
		foreach ($files as $name) {
			$res .= '
	/**
	* '.$name.'Behavior
	*
	* @var '.$name.'Behavior
	*/
	public $'.$name.';
'.LF;
		}

		$res .= '	function __construct() {';

		foreach ($files as $name) {
			$res .= '
		$this->'.$name.' = new '.$name.'Behavior();';
		}

		$res .= LF.'	}'.LF;
		return $res;
	}

	function _prepComponents($files) {
		$res = '';
		foreach ($files as $name) {
			$res .= '
	/**
	* '.$name.'Component
	*
	* @var '.$name.'Component
	*/
	public $'.$name.';
'.LF;
		}

		$res .= '	function __construct() {';

		foreach ($files as $name) {
			$res .= '
		$this->'.$name.' = new '.$name.'Component();';
		}

		$res .= LF.'	}'.LF;
		return $res;
	}

	function _prepHelpers($files) {
		# new ones
		$res = '';

		foreach ($files as $name) {
			$res .= '
	/**
	* '.$name.'Helper
	*
	* @var '.$name.'Helper
	*/
	public $'.$name.';
'.LF;
		}

		$res .= '	function __construct() {';

		foreach ($files as $name) {
			$res .= '
		$this->'.$name.' = new '.$name.'Helper();';
		}

		/*
		foreach ($files as $name) {
		$res .= '
		$'.lcfirst($name).' = new '.$name.'Helper();
		';
		}
		$res .= LF;
		*/

		$res .= LF.'	}'.LF;

		return $res;
	}


	function _dump() {
		$file = new File($this->filename, true);

		$content = '<?php exit();'.LF;
		$content .= '//Add in some helpers so the code assist works much better'.LF;
		$content .= '//Printed: '.date('d.m.Y, H:i:s').LF;
		$content .= '?>'.LF;
		$content .= $this->content;
		return $file->write($content);
	}
}


