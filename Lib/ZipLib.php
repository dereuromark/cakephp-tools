<?php

/**
 * A Zip reader class - mainly for reading and extracing Zip archives.
 *
 * To write to Zip use pclzip instead
 * @see http://www.php.net/manual/de/class.ziparchive.php
 *
 * @author Mark Scherer
 * @license MIT
 */
class ZipLib {

	protected $Zip = null;

	protected $filename = null;

	protected $path = null;

	protected $error = null;

	public function __construct($path = null, $create = false) {
		if (!function_exists('zip_open')) {
			throw new CakeException('Zip not available (enable php extension "zip")');
		}

		if ($path !== null) {
			$this->open($path, $create);
		}
	}

	public function __destruct() {
		$this->close();
	}

	/**
	 * Return the filename.
	 *
	 * @return string
	 */
	public function filename() {
		return $this->filename;
	}

	/**
	 * Count all files (not folders) - works recursivly.
	 *
	 * @return integer Size or false on failure
	 */
	public function numFiles() {
		if (!$this->Zip) {
			return false;
		}

		$size = 0;
		while ($dirResource = zip_read($this->Zip)) {
			$size++;
		}
		return $size;
	}

	/**
	 * Size in bytes
	 *
	 * @return integer Size or false on failure
	 */
	public function size() {
		if (!$this->Zip) {
			return false;
		}

		$size = 0;
		while ($dirResource = zip_read($this->Zip)) {
			$size += zip_entry_filesize($dirResource);
		}
		return $size;
	}

	/**
	 * Open a file.
	 *
	 * @params string, boolean
	 *	@return boolean Success
	 */
	public function open($path = null, $create = false) {
		$this->filename = basename($path);

		$path = str_replace('\\', '/', $path);

		$this->path = $path;
		$zip = zip_open($path);
		if (is_resource($zip)) {
			$this->Zip = $zip;
			return true;
		}
		$this->error = $zip;
		return false;
	}

	/**
	 * Close the Zip
	 *
	 * @return boolean Success
	 */
	public function close() {
		if ($this->Zip !== null) {
			zip_close($this->Zip);
			$this->Zip = null;
			return true;
		}
		return false;
	}

	/**
	 * Unzip to a specific location or the current path
	 *
	 * @param string $location
	 * @param boolean $flatten
	 * @return boolean Success
	 */
	public function unzip($location = null, $flatten = false) {
		if (!$this->Zip) {
			return false;
		}

		$file = $this->path;
		if (empty($location)) {
			$location = dirname($file) . DS;
		} else {
			if (substr($location, 0, -1) !== DS) {
				$location .= DS;
			}
			if (!file_exists($location)) {
				if (!mkdir($location, 0770, true)) {
					return false;
				}
			}
		}

		while ($zipEntry = zip_read($this->Zip)) {
			$x = str_replace('\\', '/', $location) . zip_entry_name($zipEntry);
			if ($flatten) {
				$x = str_replace('\\', '/', $location) . basename(zip_entry_name($zipEntry));
			}

			if (!file_exists($l = dirname($x))) {
				if (!mkdir($l, 0770, true)) {
					return false;
				}
			}

			$fp = fopen($x, "w");
			if (zip_entry_open($this->Zip, $zipEntry, "r")) {
				$buf = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
				fwrite($fp, $buf);
				zip_entry_close($zipEntry);
				fclose($fp);
			}
		}
		return true;
	}

	/**
	 * Returns the error string, if no error, it will return empty string ''
	 *
	 * @return string
	 */
	public function getError($text = false) {
		if ($this->error === null) {
			return '';
		}
		if ($text) {
			return $this->errMsg($this->error);
		}
		return $this->error;
	}

	/**
	 * ZipLib::errMsg()
	 *
	 * @param mixed $errno
	 * @return string
	 */
	public function errMsg($errno) {
		// using constant name as a string to make this function PHP4 compatible
		$zipFileFunctionsErrors = array(
			'ZIPARCHIVE::ER_MULTIDISK' => 'Multi-disk zip archives not supported.',
			'ZIPARCHIVE::ER_RENAME' => 'Renaming temporary file failed.',
			'ZIPARCHIVE::ER_CLOSE' => 'Closing zip archive failed',
			'ZIPARCHIVE::ER_SEEK' => 'Seek error',
			'ZIPARCHIVE::ER_READ' => 'Read error',
			'ZIPARCHIVE::ER_WRITE' => 'Write error',
			'ZIPARCHIVE::ER_CRC' => 'CRC error',
			'ZIPARCHIVE::ER_ZIPCLOSED' => 'Containing zip archive was closed',
			'ZIPARCHIVE::ER_NOENT' => 'No such file.',
			'ZIPARCHIVE::ER_EXISTS' => 'File already exists',
			'ZIPARCHIVE::ER_OPEN' => 'Can\'t open file',
			'ZIPARCHIVE::ER_TMPOPEN' => 'Failure to create temporary file.',
			'ZIPARCHIVE::ER_ZLIB' => 'Zlib error',
			'ZIPARCHIVE::ER_MEMORY' => 'Memory allocation failure',
			'ZIPARCHIVE::ER_CHANGED' => 'Entry has been changed',
			'ZIPARCHIVE::ER_COMPNOTSUPP' => 'Compression method not supported.',
			'ZIPARCHIVE::ER_EOF' => 'Premature EOF',
			'ZIPARCHIVE::ER_INVAL' => 'Invalid argument',
			'ZIPARCHIVE::ER_NOZIP' => 'Not a zip archive',
			'ZIPARCHIVE::ER_INTERNAL' => 'Internal error',
			'ZIPARCHIVE::ER_INCONS' => 'Zip archive inconsistent',
			'ZIPARCHIVE::ER_REMOVE' => 'Can\'t remove file',
			'ZIPARCHIVE::ER_DELETED' => 'Entry has been deleted',
		);
		$errmsg = 'unknown';
		foreach ($zipFileFunctionsErrors as $constName => $errorMessage) {
			if (defined($constName) && constant($constName) === $errno) {
				return 'Zip File Function error: ' . $errorMessage;
			}
		}
		return 'Zip File Function error: unknown';
	}

}
