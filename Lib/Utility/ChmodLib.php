<?php

/*
 *
 * TODO: change names of convertFromOctal and convertToOctal
 *
 *
 */

/**
 * PHP5
 * u=user, g=group, o=other
 * 2010-06-21 ms
 */
class ChmodLib {

	//protected $dir;
	protected $modes = array('user' => 0 , 'group' => 0 , 'other' => 0);


/*** calc octal ***/

	 	/**
	 * from Octal 0xxx back to STRING with leading zero added on leading zero = true
	 * e.g. 0777 => 0777, '755' => 0755
	 * @access static Chmod::convertFromOctal(mode, leadingZero)
	 * 2009-07-26 ms
	 */
	public static function convertFromOctal($mode, $leadingZero = false) {
		$res = (String)substr(sprintf('%o', $mode), -4);
		if ($leadingZero===true) {
			$res = '0'.$res;
		}
		return $res;
	}

	/**
	 * from INT or STRING with or without leading 0 -> Octal 0xxx
	 * @access static Chmod::converttoOctal(mode)
	 * 2009-07-26 ms
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
	 * get mode as octal value or
	 * @param options
	 * - string: string/int/symbolic
	 * 2010-06-21 ms
	 */
	public function getMode($options = array()) {
		$mode = (string)($this->modes['user'] . $this->modes['group'] . $this->modes['other']);
		if (!empty($options['type'])) {
			if ($options['type'] == 'string') {
				return $mode;
			} elseif ($options['type'] == 'int') {
				return (int)$mode;
			} elseif ($options['type'] == 'symbolic') {
				$mode = $this->symbol($this->modes['user']).$this->symbol($this->modes['group']).$this->symbol($this->modes['other']);
				return $mode;
			}
		}
		return intval($mode, 8);
	}


	/**
	 * full table with all rights
	 * //TODO
	 * 2010-06-21 ms
	 */
	public function table() {
		$res = array();


		return $res;
	}

	/**
	 * get symbol for
	 * read(4) = 'r', write(2) = 'w', execute(1) = 'x'
	 * e.g: 4 for = r--
	 * 2010-06-21 ms
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

	protected function setMode($r, $w, $e) {
		$mode = 0;
		if ($r) $mode+=4;
		if ($w) $mode+=2;
		if ($e) $mode+=1;
		return $mode;
	}

}

