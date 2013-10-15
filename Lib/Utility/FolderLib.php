<?php
App::uses('Folder', 'Utility');

/**
 * Folder lib with a few custom improvements
 */
class FolderLib extends Folder {

	/**
	 * Empty the folder.
	 * Instead of deleting the folder itself as delete() does,
	 * this method only removes its content recursivly.
	 *
	 * Note: It skips hidden folders (starting with a . dot).
	 *
	 * @return boolean Success or null on invalid folder
	 */
	public function clear($path = null) {
		if (!$path) {
			$path = $this->pwd();
		}
		if (!$path) {
			return null;
		}
		$path = Folder::slashTerm($path);
		if (!is_dir($path)) {
			return null;
		}
		$normalFiles = glob($path . '*');
		$hiddenFiles = glob($path . '\.?*');

		$normalFiles = $normalFiles ? $normalFiles : array();
		$hiddenFiles = $hiddenFiles ? $hiddenFiles : array();

		$files = array_merge($normalFiles, $hiddenFiles);
		foreach ($files as $file) {
			if (preg_match('/(\.|\.\.)$/', $file)) {
				continue;
			}
			if (is_file($file)) {
				if (@unlink($file)) {
					$this->_messages[] = __('%s removed', $file);
				} else {
					$this->_errors[] = __('%s NOT removed', $file);
				}
			} elseif (is_dir($file) && $this->delete($file) === false) {
				return false;
			}
		}
		return true;
	}

}
