<?php

//TODO: make a helper
class ICalObject {

	var $config = array('database' => '');
	var $__count = 0;
	var $__lastInsertId = null;
	var $__data = null;

	var $__timezones = array('US-Eastern');
	var $columns = array('primary_key' => array('name' => 'uid'),
						'string' => array('name' => 'string'),
						'timestamp' => array('name' => 'timestamp', 'format' => 'Ymd/T/His'),
						'datetime' => array('name' => 'timestamp', 'format' => 'Ymd/T/His')//,
	);
	var $__keyMap = array(
			'id'			=> 'uid',
			'end_date'		=> 'dtend',
			'start_date'	=> 'dtstart',
			'date_stamp'	=> 'dtstamp'
	);
	var $__textMap = array(
			'"'		=> 'DQUOTE',
			','		=> '\,',
			//':'		=> '":"', // Not sure about this one
			';'		=> '\;',
			'\\'	=> '\\\\',
			'\n'	=> '\\n'
	);

	function listSources() {
		return array('calendars', 'events', 'todos', 'alarms', 'journals');
	}

	function create($data) {
		return $this->__output($data);
	}
	

	function read($filename = null) {

		if ($filename != null) {
			$this->__count = 0;
			$this->config['database'] = $filename;
		}

		if ($this->__data == null) {
			$this->__data = $this->__parse(file_get_contents($this->config['database']));
		}
		return $this->__data;
	}

	function update($data) { }

	function delete($data) { }

	function __output($data) {
		$out = '';
		foreach($data as $key => $val) {

			$keyAppend = '';
			if (in_array($key, array('Calendar', 'Event', 'Timezone', 'Todo', 'Alarm', 'Journals'))) {
				$key = 'v' . $key;
			}

			if (is_array($val) && strtolower($key) != $key) {
				if (countdim($val) > 1) {
					foreach ($val as $val2) {
						$out .= strtoupper("begin:{$key}\n");
						$out .= $this->__output($val2);
						$out .= strtoupper("end:{$key}\n");
					}
				} else {
					$out .= strtoupper("begin:{$key}\n");
					$out .= $this->__output($val);
					$out .= strtoupper("end:{$key}\n");
				}
			} else {
				if (is_array($val)) {
					$tmp = array();
					foreach ($val as $key2 => $val2) {
						if ($key2 !== 0) {
							$tmp[] = strtoupper($key2) . '=' . $val2;
						}
					}

					if (!empty($tmp)) {
						$keyAppend = ';' . join(';', $tmp);
					}

					$_val = $val[0];
				} else {
					$_val = $val;
				}

				switch ($key) {
					case 'end_date':
					case 'start_date':
					case 'date_stamp':
					case 'last_modified':
					case 'trigger':
						if (strpos($_val, ' weeks') === false && strpos($_val, ' days') === false && strpos($_val, ' hours') === false && strpos($_val, ' minutes') === false && strpos($_val, ' seconds') === false) {
							$utc = false;
							if (strpos($_val, 'UTC')) {
								$utc = true;
							}
							
							if (strpos($_val, ' ') === false && strpos($_val, ':') === false) {
								$val = date('Ymd', strtotime($_val));
							} else {
								$_val = trim(r('UTC', '', $_val));
								$tmp = date('Ymd', strtotime($_val)).'T'.date('His', strtotime($_val));
								if ($utc) {
									$tmp .= 'Z';
								}
								$val = $tmp;
							}
						} else {
							$val = $this->__putDuration($val);
						}
					break;
					case 'duration':
						$val = $this->__putDuration($val);
					break;
					case 'contact':
					case 'comment':
					case 'description':
					case 'location':
					case 'prodid':
					case 'resources':
					case 'status':
					case 'summary':
						$s = array_keys($this->__textMap);
						$r = array_values($this->__textMap);
						$val = str_replace($s, $r, $val);
						$val = str_replace('\\\\', '\\', $val);
					break;
					default:
						if ($val === true) {
							$val = 'TRUE';
						} elseif ($val === false) {
							$val = 'FALSE';
						}
					break;
				}

				if (in_array($key, array_keys($this->__keyMap))) {
					$key = $this->__keyMap[$key];
				}
				if (is_array($val) && isset($val[0])) {
					$val = $val[0];
				}

				$out .= strtoupper(str_replace('_', '-', $key)) . $keyAppend . ':' . $val . "\n";
			}
		}
		return $out;
	}

	function __parse(&$lines) {

		if (is_string($lines)) {
			$lines = str_replace("\r", '', $lines);
			$lines = explode("\n", $lines);

			$lines1 = ($lines);

			for ($i = 0; $i < count($lines); $i++) {
				if (substr($lines[$i], 0, 1) == ' ') {
					$lines[$i - 1] .= substr($lines[$i], 1);
					array_splice($lines, $i, 1);
				} elseif ($lines[$i] == '') {
					array_splice($lines, $i, 1);
				}
			}
		}

		$data = array();
		for ($i = $this->__count; $i < count($lines); $i++) {

			$idx = strpos($lines[$i], ':');
			$key = str_replace('-', '_', substr($lines[$i], 0, $idx));
			$value = substr($lines[$i], $idx + 1);

			if (strtolower($key) == 'end') {
				$this->__count = $i++;
				return $data;
			} elseif (strtolower($key) == 'begin') {
				$key = ucwords(strtolower($value));
				if ($key{0} == 'V') {
					$key = ucwords(substr($key, 1));
				}
				
				$this->__count = ++$i;
				$value = $this->__parse($lines);
				$i = $this->__count;
			} else {
				if (strpos($key, ';')) {
					$key = explode(';', $key);
					$props = $key;
					$key = $key[0];
					array_shift($props);
	
					$value = array($value);
					foreach ($props as $v) {
						$tmp = explode('=', $v);
						if (isset($tmp[1])) {
							$value[strtolower($tmp[0])] = $tmp[1];
						}
					}
				}
				$key = strtolower($key);
			}

			if (in_array($key, $this->__keyMap)) {
				$reverse = array_combine(array_values($this->__keyMap), array_keys($this->__keyMap));
				$key = $reverse[$key];
			}

			// Format the data types
			switch ($key) {
				case 'end_date':
				case 'start_date':
				case 'date_stamp':
				case 'last_modified':
				case 'trigger':

					if (is_array($value)) {
						$value[0] = $this->__timestamp($value[0]);
					} elseif (strpos(strtolower($value), '-p') !== false || strpos(strtolower($value), 'p') !== false) {
						$value = $this->__duration($value);
					} else {
						$value = $this->__timestamp($value);
					}
				break;
				case 'duration':
					$value = $this->__duration($value);
				break;
				case 'contact':
				case 'comment':
				case 'description':
				case 'location':
				case 'prodid':
				case 'resources':
				case 'status':
				case 'summary':
					$r = array_keys($this->__textMap);
					$s = array_values($this->__textMap);
					$value = str_replace($s, $r, $value);
				break;
				default:
					if ($value == 'TRUE') {
						$value = true;
					} elseif ($value == 'FALSE') {
						$value = false;
					}
				break;
			}

			if (isset($data[$key])) {
				if (!isset($data[$key][0])) {
					$data[$key] = array($data[$key]);
					$data[$key][] = $value;
				} elseif (isset($data[$key][0]) && is_array($data[$key])) {
					$data[$key][] = $value;
				}
			} else {
				$data[$key] = $value;
			}
		}

		return $data;
	}

	// Event UID generator
	function __insertID() {
		$chunk = array();
		$hash = strtoupper(md5(intval(str_replace('.', '', env('SERVER_ADDR'))).''.intval(rand() * 1000).time()));
		$chunk[] = substr($hash, 0, 8);
		$chunk[] = substr($hash, 8, 4);
		$chunk[] = substr($hash, 12, 4);
		$chunk[] = substr($hash, 16, 4);
		$chunk[] = substr($hash, 20);
		$this->__lastInsertId = join('-', $chunk);
		return $this->__lastInsertId;
	}

	function lastInsertId() {
		return $this->__lastInsertId;
	}

	function __timestamp($time) {
		if (strpos(strtolower($time), 'p') === 0 || strpos(strtolower($time), 'p') === 1) {
			return $this->__duration($time);
		}
		$utc = false;
		if (strpos(strtolower($time), 'z')) {
			$utc = true;
		}

		if (strpos(strtolower($time), 't') === false) {
			return date('Y-m-d', strtotime($time));
		}
		$time = explode('t', str_replace('z', '', strtolower($time)));
		$time[1] = substr($time[1], 0, 2).':'.substr($time[1], 2, 2).':'.substr($time[1], 4, 2);
		return date('Y-m-d', strtotime($time[0])).' '.$time[1] . ($utc ? ' UTC' : '');
	}

	function __putDuration($time, $date = null) {

		$negative = false;
		if (is_string($time)) {
			if (strpos($time, '-') === 0) {
				$time = substr($time, 1);
				$negative = true;
			}
			$time = strtotime('+' . $time) - strtotime('now');;
		}

		if ($time < 0) {
			$time = abs($time);
			$negative = true;
		}

		$out = 'P';
		$t = false;
		$offset = array('W' => 604800, 'D' => 86400, 'H' => 3600, 'M' => 60, 'S' => 1);

		if ($date != null) {
			$time = strtotime($date) - strtotime($time);
		}

		foreach ($offset as $key => $val) {
			$tmp = 0;
			if ($time >= $val) {
				$tmp = $time / $val;
			}
			
			if ($tmp >= 1) {
				if (in_array($key, array('H', 'M', 'S')) && $t == false) {
					$t = true;
					$out .= 'T';
				}

				$out .= $tmp.$key;
				$time -= $tmp * $val;
			}
		}
		return ($negative ? '-' : '') . $out;
	}

	function __duration($dur) {

		$tmp = '';
		$out = '';

		for ($i = 0; $i < strlen($dur); $i++) {
			switch(strtolower($dur{$i})) {
				case 't':
				case 'p':
					// do nothing
				break;
				case 'w':
					$out .= $tmp . ' week' . (intval($tmp) != 1 ? 's' : '') . ' ';
					$tmp = '';
				break;
				case 'd':
					$out .= $tmp . ' day' . (intval($tmp) != 1 ? 's' : '') . ' ';
					$tmp = '';
				break;
				case 'h':
					$out .= $tmp . ' hour' . (intval($tmp) != 1 ? 's' : '') . ' ';
					$tmp = '';
				break;
				case 'm':
					$out .= $tmp . ' minute' . (intval($tmp) != 1 ? 's' : '') . ' ';
					$tmp = '';
				break;
				case 's':
					$out .= $tmp . ' second' . (intval($tmp) != 1 ? 's' : '') . ' ';
					$tmp = '';
				break;
				default:
					$tmp .= $dur{$i};
				break;
			}
		}
		return trim($out);
	}

	function debug_compare($lines1) {

		$data = $this->__parse($lines1);
		pr($data);

		$diff = 0;
		$lines2 = explode("\n", $this->__output($data));
		pr($lines2);
		e('<table border=1>');

		foreach ($lines1 as $i => $val) {

			e('<tr><td>');
			pr($i . ' : ' . $val);
			e('</td><td>');

			if ($lines2[$i] != $val) {
				pr($lines2[$i]);
				$diff++;
			}
			e('</td></tr>');
		}
		e('</table>');
		pr('Diffs : '.$diff);

		die();
	}
}


