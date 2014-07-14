<?php
App::uses('File', 'Utility');

/**
 * Convenience class for reading, writing and appending to files.
 *
 */
class FileLib extends File {

	/**
	 * Allowed delimiters for csv
	 */
	protected $allowedDelimiters = array(
		',',
		';',
		'|',
		' ',
		'#');

	/**
	 * Allowed enclosures for csv
	 */
	protected $allowedEnclosures = array('"', '\'');

	/**
	 * Allowed tags for pattern reading
	 */
	protected $allowedTags = array(
		'<h1>',
		'<h2>',
		'<h3>',
		'<p>',
		'<b>',
		'<a>',
		'<img>');

	protected $defaultFormat = '%s';

	/**
	 * A better csv reader which handles encoding as well as removes completely empty lines
	 *
	 * Options:
	 * - int length (0 = no limit)
	 * - string delimiter (null defaults to ,)
	 * - string enclosure (null defaults to " - do not pass empty string)
	 * - string mode
	 * - string force Force open/read the file
	 * - bool removeEmpty Remove empty lines (simple newline characters without meaning)
	 * - bool encode Encode to UTF-8
	 *
	 * @param array $options Options
	 * @return array Content or false on failure
	 */
	public function readCsv($options = array(), $delimiter = null, $enclosure = null, $mode = 'rb', $force = false, $removeEmpty = false, $encode = true) {
		// For BC
		if (!is_array($options)) {
			$options = array(
				'delimiter' => $delimiter !== null ? $delimiter : ',',
				'enclosure' => $enclosure !== null ? $enclosure : '"',
				'mode' => $mode,
				'force' => $force,
				'removeEmpty' => $removeEmpty,
				'encode' => $encode,
				'length' => $options
			);
		}
		$defaults = array(
			'delimiter' => ',',
			'enclosure' => '"',
			'escape' => "\\",
			'mode' => 'rb',
			'force' => false,
			'removeEmpty' => false,
			'encode' => true,
			'length' => 0
		);
		$options += $defaults;
		extract($options);

		if ($this->open($mode, $force) === false) {
			return false;
		}

		if ($this->lock !== null && flock($this->handle, LOCK_SH) === false) {
			return false;
		}

		// PHP cannot handle delimiters with more than a single char
		if (strlen($delimiter) > 1) {
			throw new InternalErrorException('Invalid delimiter');
		}

		$res = array();
		while (true) {
			$data = fgetcsv($this->handle, $length, $delimiter, $enclosure, $escape);
			if ($data === false) {
				break;
			}
			if ($encode) {
				$data = $this->_encode($data);
			}
			$isEmpty = true;
			foreach ($data as $key => $val) {
				if (!empty($val)) {
					$isEmpty = false;
					break;
				}
			}
			if ($isEmpty && $removeEmpty) {
				continue;
			}
			$res[] = $data;
		}

		if ($this->lock !== null) {
			flock($this->handle, LOCK_UN);
		}
		$this->close();
		return $res;
	}

	/**
	 * FileLib::readCsvFromString()
	 *
	 * @param string $string CSV content
	 * @param array $options Options array
	 * @return array Parsed content
	 */
	public static function readCsvFromString($string, $options = array()) {
		$file = fopen("php://memory", "rw");
		fwrite($file, $string);
		fseek($file, 0);

		$defaults = array(
			'delimiter' => ',',
			'enclosure' => '"',
			'escape' => "\\",
			'eol' => "\n",
			'encode' => false,
			'removeEmpty' => false
		);
		$options += $defaults;
		extract($options);

		// PHP cannot handle delimiters with more than a single char
		if (strlen($delimiter) > 1) {
			throw new InternalErrorException('Invalid delimiter');
		}

		$res = array();
		while (true) {
			$data = fgetcsv($file, 0, $delimiter, $enclosure, $escape);

			if ($data === false) {
				break;
			}
			if ($encode) {
				$data = $this->_encode($data);
			}
			$isEmpty = true;
			foreach ($data as $key => $val) {
				if (!empty($val)) {
					$isEmpty = false;
					break;
				}
			}
			if ($isEmpty && $removeEmpty) {
				continue;
			}
			$res[] = $data;
		}

		fclose($file);
		return $res;
	}

	/**
	 * Write an array to a csv file
	 *
	 * @param array $data
	 * @param string $delimiter (null defaults to ,)
	 * @param string $enclosure (null defaults to " - do not pass empty string)
	 * @return bool Success
	 */
	public function writeCsv($data, $delimiter = null, $enclosure = null) {
		if ($this->open('w', true) !== true) {
			return false;
		}
		if ($this->lock !== null) {
			if (flock($this->handle, LOCK_EX) === false) {
				return false;
			}
		}
		$success = true;
		foreach ($data as $row) {
			if (fputcsv($this->handle, array_values((array)$row), (isset($delimiter) ? $delimiter : ','), (isset($enclosure) ? $enclosure : '"')) === false) {
				$success = false;
			}
		}
		if ($this->lock !== null) {
			flock($this->handle, LOCK_UN);
		}
		$this->close();
		return $success;
	}

	/**
	 * Read files with fscanf() and pattern
	 *
	 * @param string $format (e.g. "%s\t%s\t%s\n")
	 * @param string $mode
	 * @param string $force Force open/read the file
	 * @return array Content or false on failure
	 */
	public function readWithPattern($format = null, $mode = 'rb', $force = false) {
		$res = array();
		if ($this->open($mode, $force) === false) {
			return false;
		}

		if ($this->lock !== null && flock($this->handle, LOCK_SH) === false) {
			return false;
		}

		if (empty($format)) {
			$format = $this->defaultFormat;
		}

		while (true) {
			$data = fscanf($this->handle, $format);
			if ($data === false) {
				break;
			}
			$res[] = $data;
		}

		if ($this->lock !== null) {
			flock($this->handle, LOCK_UN);
		}

		return $res;
	}

	/**
	 * Return the contents of this File as a string - but without tags
	 *
	 * @param string/array $tags: <tag><tag2><tag3> or array('<tag>',...) otherwise default tags are used
	 * @param string $mode
	 * @param bool $force If true then the file will be re-opened even if its already opened, otherwise it won't
	 * @return mixed string on success, false on failure
	 */
	public function readWithTags($tags = null, $mode = 'rb', $force = false) {
		if ($this->open($mode, $force) === false) {
			return false;
		}
		if ($this->lock !== null && flock($this->handle, LOCK_SH) === false) {
			return false;
		}

		if (empty($tags)) {
			$tags = implode($this->allowedTags);
		} else {
			if (is_array($tags)) {
				$tags = implode($tags);
			}
		}

		$data = '';
		while (!feof($this->handle)) {
			$data .= fgetss($this->handle, 4096, $tags);
		}
		$data = trim($data);

		if ($this->lock !== null) {
			flock($this->handle, LOCK_UN);
		}

		return $data;
	}

	/**
	 * Transfer array to cake structure
	 *
	 * @param data (usually with the first row as keys!)
	 * @param options
	 * - keys (defaults to first array content in data otherwise) (order is important!)
	 * - preserve_keys (do not slug and lowercase)
	 * @return array Result
	 */
	public function transfer($data, $options = array()) {
		$res = array();

		if (empty($options['keys'])) {
			$keys = array_shift($data);
		} else {
			$keys = $options['keys'];
		}

		foreach ($keys as $num => $key) {
			if (empty($options['preserve_keys'])) {
				$key = strtolower(Inflector::slug($key));
			}
			foreach ($data as $n => $val) {
				$res[$n][$key] = $val[$num];
			}
		}
		return $res;
	}

	/**
	 * Assert proper encoding
	 *
	 * @param array Input
	 * @return array Output
	 */
	protected function _encode(array $array) {
		$convertedArray = array();
		foreach ($array as $key => $value) {
			if (!mb_check_encoding($key, 'UTF-8')) {
				$key = utf8_encode($key);
			}
			if (is_array($value)) {
				$value = $this->_encode($value);
			} else {
				if (!mb_check_encoding($value, 'UTF-8')) {
					$value = utf8_encode($value);
				}
				$value = trim($value);
			}
			$convertedArray[$key] = $value;
		}
		return $convertedArray;
	}

	/**
	 * Check if a blob string contains the BOM.
	 * Useful for file_get_contents() + json_decode() that needs the BOM removed.
	 *
	 * @param string $content
	 * @return bool Success
	 */
	public static function hasByteOrderMark($content) {
		return strpos($content, b"\xEF\xBB\xBF") === 0;
	}

	/**
	 * Remove BOM from a blob string if detected.
	 * Useful for file_get_contents() + json_decode() that needs the BOM removed.
	 *
	 * @param string $content
	 * @return string Cleaned content
	 */
	public static function removeByteOrderMark($content) {
		return trim($content, b"\xEF\xBB\xBF");
	}

}
