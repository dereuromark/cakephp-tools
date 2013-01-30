<?php
App::uses('Folder', 'Utility');

/**
 * Folder lib with a few custom improvements

 * 2010-05-16 ms
 */
class FolderLib extends Folder {

	/**
	 * Empty the folder.
	 * Instead of deleting the folder itself as delete() does,
	 * this method only removes its content recursivly.
	 *
	 * @return boolean $success or null on invalid folder
	 * 2010-12-07 ms
	 */
	public function clear($path = null) {
		if (!$path) {
			$path = $this->pwd();
		}
		if (!$path) {
			return null;
		}
		$path = Folder::slashTerm($path);
		if (is_dir($path)) {
			$normalFiles = glob($path . '*');
			$hiddenFiles = glob($path . '\.?*');

			$normalFiles = $normalFiles ? $normalFiles : array();
			$hiddenFiles = $hiddenFiles ? $hiddenFiles : array();

			$files = array_merge($normalFiles, $hiddenFiles);
			if (is_array($files)) {
				foreach ($files as $file) {
					if (preg_match('/(\.|\.\.)$/', $file)) {
						continue;
					}
					chmod($file, 0770);
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
			}
		} else {
			unlink($path);
		}
		return true;
	}

}
