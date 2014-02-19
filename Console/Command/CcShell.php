<?php
App::uses('AppShell', 'Console/Command');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

if (!defined('LF')) {
	define('LF', PHP_EOL); # use PHP to detect default linebreak
}

/**
 * Code Completion Shell
 * Workes perfectly with PHPDesigner - but should also work with most other IDEs out of the box
 *
 * @version 1.1
 * @cakephp 2.x
 * @author Mark Scherer
 * @license MIT
 */
class CcShell extends AppShell {

	public $plugins = null;

	public $content = '';

	public $appFiles = array();

	/**
	 * CcShell::main()
	 *
	 * @return void
	 */
	public function main() {
		$this->out('Code Completion Dump - customized for PHPDesigner');

		$this->filename = APP . 'CodeCompletion.php';

		// get classes
		$this->models();
		$this->behaviors();

		$this->controller();
		$this->helpers();
		$this->appFiles();

		// write to file
		$this->_dump();

		$this->out('...done');
	}

	/**
	 * CcShell::models()
	 *
	 * @return void
	 */
	public function models() {
		$files = $this->_getFiles('Model');

		$content = LF;
		$content .= '/*** model start ***/' . LF;
		$content .= 'class AppModel extends Model {' . LF;
		if (!empty($files)) {
			$content .= $this->_prepModels($files);
		}

		$content .= '}' . LF;
		$content .= '/*** model end ***/' . LF;

		$this->content .= $content;
	}

	/**
	 * CcShell::behaviors()
	 *
	 * @return void
	 */
	public function behaviors() {
		$files = $this->_getFiles('Model/Behavior');

		$content = LF;
		$content .= '/*** behavior start ***/' . LF;
		$content .= 'class AppModel extends Model {' . LF;
		if (!empty($files)) {
			$content .= $this->_prepBehaviors($files);
		}
		$content .= '}' . LF;
		$content .= '/*** behavior end ***/' . LF;

		$this->content .= $content;
	}

	/**
	 * Components + models
	 *
	 * @return void
	 */
	public function controller() {
		$content = LF;
		$content .= '/*** component start ***/' . LF;
		$content .= 'class AppController extends Controller {' . LF;

		$files = $this->_getFiles('Controller/Component');
		if (!empty($files)) {
			$content .= $this->_prepComponents($files);
		}

		$content .= LF . LF;

		$files = $this->_getFiles('Model');
		if (!empty($files)) {
			$content .= $this->_prepModels($files);
		}

		$content .= '}' . LF;
		$content .= '/*** component end ***/' . LF;

		$this->content .= $content;
	}

	/**
	 * CcShell::helpers()
	 *
	 * @return void
	 */
	public function helpers() {
		$files = $this->_getFiles('View/Helper');
		$content = LF;
		$content .= '/*** helper start ***/' . LF;
		$content .= 'class AppHelper extends Helper {' . LF;
		if (!empty($files)) {
			$content .= $this->_prepHelpers($files);
		}
		$content .= '}' . LF;
		$content .= '/*** helper end ***/' . LF;

		$this->content .= $content;
	}

	/**
	 * CcShell::appFiles()
	 *
	 * @return void
	 */
	public function appFiles() {
		$files = $this->appFiles;
		$content = LF;
		$content .= '/*** plugin files start ***/' . LF;
		if (!empty($files)) {
			$content .= $this->_prepAppFiles($files);
		}
		$content .= '/*** plugin files end ***/' . LF;

		$this->content .= $content;
	}

	/**
	 * CcShell::_prepAppFiles()
	 *
	 * @param mixed $files
	 * @return string
	 */
	protected function _prepAppFiles($files) {
		$res = '';
		foreach ($files as $name => $parent) {
			$res .= 'class ' . $name . ' extends ' . $parent . ' {}' . LF;
		}
		return $res;
	}

	/**
	 * CcShell::_prepModels()
	 *
	 * @param mixed $files
	 * @return string
	 */
	protected function _prepModels($files) {
		$res = '';
		foreach ($files as $name) {

			$res .= '
	/**
	* ' . $name . '
	*
	* @var ' . $name . '
	*/
	public $' . $name . ';
' . LF;
		}

		$res .= '	public function __construct() {';

		foreach ($files as $name) {
			$res .= '
		$this->' . $name . ' = new ' . $name . '();';
		}

		$res .= LF . '	}' . LF;
		return $res;
	}

	/**
	 * CcShell::_prepBehaviors()
	 *
	 * @param mixed $files
	 * @return string
	 */
	protected function _prepBehaviors($files) {
		$res = '';
		foreach ($files as $name) {
			if (!($varName = $this->_varName($name, 'Behavior'))) {
				continue;
			}
			$res .= '
	/**
	* ' . $name . 'Behavior
	*
	* @var ' . $varName . '
	*/
	public $' . $varName . ';
' . LF;
		}

		$res .= '	public function __construct() {';

		foreach ($files as $name) {
			if (!($varName = $this->_varName($name, 'Behavior'))) {
				continue;
			}
			$res .= '
		$this->' . $varName . ' = new ' . $name . '();';
		}

		$res .= LF . '	}' . LF;
		return $res;
	}

	/**
	 * Check on correctness to avoid duplicates
	 *
	 * @return void
	 */
	protected function _varName($name, $type) {
		if (($pos = strrpos($name, $type)) === false) {
			return '';
		}
		return substr($name, 0, $pos);
	}

	/**
	 * CcShell::_prepComponents()
	 *
	 * @param mixed $files
	 * @return string
	 */
	protected function _prepComponents($files) {
		$res = '';
		foreach ($files as $name) {
			if (!($varName = $this->_varName($name, 'Component'))) {
				continue;
			}
			$res .= '
	/**
	* ' . $name . '
	*
	* @var ' . $varName . '
	*/
	public $' . $varName . ';
' . LF;
		}

		$res .= '	public function __construct() {';

		foreach ($files as $name) {
			if (!($varName = $this->_varName($name, 'Component'))) {
				continue;
			}
			$res .= '
		$this->' . $varName . ' = new ' . $name . '();';
		}

		$res .= LF . '	}' . LF;
		return $res;
	}

	/**
	 * CcShell::_prepHelpers()
	 *
	 * @param mixed $files
	 * @return string
	 */
	protected function _prepHelpers($files) {
		// new ones
		$res = '';

		foreach ($files as $name) {
			if (!($varName = $this->_varName($name, 'Helper'))) {
				continue;
			}
			$res .= '
	/**
	* ' . $name . '
	*
	* @var ' . $varName . '
	*/
	public $' . $varName . ';
' . LF;
		}

		$res .= '	public function __construct() {';

		foreach ($files as $name) {
			if (!($varName = $this->_varName($name, 'Helper'))) {
				continue;
			}
			$res .= '
		$this->' . $varName . ' = new ' . $name . '();';
		}

		$res .= LF . '	}' . LF;

		return $res;
	}

	/**
	 * CcShell::_dump()
	 *
	 * @return void
	 */
	protected function _dump() {
		//$File = new File($this->filename, true);

		$content = '<?php exit();' . PHP_EOL . PHP_EOL;
		$content .= 'class CodeCompletion {' . PHP_EOL;
		$content .= '}' . PHP_EOL . PHP_EOL;
		$content .= '//Printed: ' . date('d.m.Y, H:i:s') . PHP_EOL;
		$content .= $this->content;

		//return $File->write($content);
		file_put_contents($this->filename, $content);
	}

	/**
	 * CcShell::_getFiles()
	 *
	 * @param mixed $type
	 * @return array
	 */
	protected function _getFiles($type) {
		$files = App::objects($type, null, false);
		$corePath = App::core($type);
		$coreFiles = App::objects($type, $corePath, false);
		$files = array_merge($coreFiles, $files);
		//$paths = (array)App::path($type.'s');
		//$libFiles = App::objects($type, $paths[0] . 'lib' . DS, false);
		$appIndex = array_search('AppModel', $files);
		if ($appIndex !== false) {
			unset($files[$appIndex]);
		}

		if (!isset($this->plugins)) {
			$this->plugins = App::objects('plugin');
		}

		if (!empty($this->plugins)) {
			foreach ($this->plugins as $plugin) {
				$pluginType = $plugin . '.' . $type;
					$pluginFiles = App::objects($pluginType, null, false);
					if (!empty($pluginFiles)) {
						foreach ($pluginFiles as $file) {
							if (strpos($file, 'App' . $type) !== false) {
								//$this->appFiles[$file] = $plugin.'.'.$type;
								continue;
							}
							$files[] = $file;
						}
					}
			}
		}
		$files = array_unique($files);

		// no test/tmp files etc (helper.test.php or helper.OLD.php)
		foreach ($files as $key => $file) {
			if (strpos($file, '.') !== false || !preg_match('/^[\da-zA-Z_]+$/', $file)) {
				unset($files[$key]);
			}
		}
		return $files;
	}

}
