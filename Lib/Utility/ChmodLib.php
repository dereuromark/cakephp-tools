<?php

/*
 *
 * TODO: change names of convertFromOctal and convertToOctal
 *
 */

/**
 * PHP5
 * u=user, g=group, o=other
 */
class ChmodLib {

	//protected $dir;
	protected $modes = array('user' => 0, 'group' => 0, 'other' => 0);

/*** calc octal ***/

	/**
	 * From Octal 0xxx back to STRING with leading zero added on leading zero = true
	 * e.g. 0777 => 0777, '755' => 0755
	 *
	 * @return string
	 */
	public static function convertFromOctal($mode, $leadingZero = false) {
		$res = (string)substr(sprintf('%o', $mode), -4);
		if ($leadingZero === true) {
			$res = '0' . $res;
		}
		return $res;
	}

	/**
	 * From INT or STRING with or without leading 0 -> Octal 0xxx
	 *
	 * @return integer
	 */
	public static function convertToOctal($mode) {
		return intval((string)$mode, 8);
	}

/*** set/get modes ***/

	public function setUser($read, $write, $execute) {
		$this->modes['user'] = $this->setMode($read, $write, $execute);
	}

	public function setGroup($read, $write, $execute) {
		$this->modes['group'] = $this->setMode($read, $write, $execute);
	}

	public function setOther($read, $write, $execute) {
		$this->modes['other'] = $this->setMode($read, $write, $execute);
	}

	/**
	 * Get mode as octal value or
	 *
	 * @param options
	 * - string: string/int/symbolic
	 * @return integer Mode
	 */
	public function getMode($options = array()) {
		$mode = (string)($this->modes['user'] . $this->modes['group'] . $this->modes['other']);
		if (!empty($options['type'])) {
			if ($options['type'] === 'string') {
				return $mode;
			} elseif ($options['type'] === 'int') {
				return (int)$mode;
			} elseif ($options['type'] === 'symbolic') {
				$mode = $this->symbol($this->modes['user']) . $this->symbol($this->modes['group']) . $this->symbol($this->modes['other']);
				return $mode;
			}
		}
		return intval($mode, 8);
	}

	/**
	 * Full table with all rights
	 * //TODO
	 */
	public function table() {
		$res = array();

		return $res;
	}

	/**
	 * Get symbol for
	 * read(4) = 'r', write(2) = 'w', execute(1) = 'x'
	 * e.g: 4 for = r--
	 *
	 * @return string Symbol
	 */
	protected function symbol($mode) {
		$res = '---';
		if ($mode == 7) {
			$res = 'rwx';
		} elseif ($mode == 6) {
			$res = 'rw-';
		} elseif ($mode == 5) {
			$res = 'r-x';
		} elseif ($mode == 4) {
			$res = 'r--';
		} elseif ($mode == 3) {
			$res = '-wx';
		} elseif ($mode == 2) {
			$res = '-w-';
		} elseif ($mode == 1) {
			$res = '--x';
		}
		return $res;
	}

	/**
	 * ChmodLib::setMode()
	 *
	 * @param integer $r
	 * @param integer $w
	 * @param integer $e
	 * @return integer
	 */
	protected function setMode($r, $w, $e) {
		$mode = 0;
		if ($r) $mode += 4;
		if ($w) $mode += 2;
		if ($e) $mode += 1;
		return $mode;
	}

}
