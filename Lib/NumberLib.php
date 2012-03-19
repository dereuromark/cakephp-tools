<?php

//TODO: rename to TimeLib or move the time stuff to a time lib???!!!
/**
 * 2011-03-07 ms
 */
class NumberLib {

	/**
	 * get the rounded average
	 * @param array $values: int or float values
	 * @param int $precision
	 * @return int $average
	 * 2009-09-05 ms
	 */
	public static function average($values, $precision = 0) {
		$average = round(array_sum($values) / count($values), $precision);
		return $average;
	}
	
	/**
	 * @access public
	 * @param float $number
	 * @param float $increment
	 * @return float $result
	 * 2011-04-14 lb
	 */
	public static function roundTo($number, $increments = 1.0) {
		return round($number, self::getDecimalPlaces($increments));
	}

	/**
	 * @access public
	 * @param float $number
	 * @param int $increment
	 * @return float $result
	 * 2011-04-14 lb
	 */
	public static function roundUpTo($number, $increments = 1) {
	return (ceil($number / $increments) * $increments);
	}

	/**
	 * @access public
	 * @param float $number
	 * @param int $increment
	 * @return float $result
	 * 2011-04-14 lb
	 */
	public static function roundDownTo($number, $increments = 1) {
	return (floor($number / $increments) * $increments);
	}

	/**
	 * @access public
	 * @param float $number
	 * @return int $decimalPlaces
	 * 2011-04-15 lb
	 */
	public static function getDecimalPlaces($number) {
		$decimalPlaces = 0;
		while ($number > 1 && $number != 0) {
			$number /= 10;
			$decimalPlaces -= 1;
		}
		while ($number < 1 && $number != 0) {
			$number *= 10;
			$decimalPlaces += 1;
		}
		return $decimalPlaces;
	}


	/**
	 * hours, minutes
	 * e.g. 9.3 => 9.5
	 * 2010-11-03 ms
	 */
	public static function standardToDecimalTime($value) {
		$base = (int)$value;
		$tmp = $value-$base;

		$tmp *= 100;
		$tmp *= 1/60;

		$value = $base+$tmp;
		return $value;
	}

	/**
	 * hours, minutes
	 * e.g. 9.5 => 9.3
	 * with pad=2: 9.30
	 * 2010-11-03 ms
	 */
	public static function decimalToStandardTime($value, $pad = null, $decPoint = '.') {
		$base = (int)$value;
		$tmp = $value-$base;

		$tmp /= 1/60;
		$tmp /= 100;

		$value = $base+$tmp;
		if ($pad === null) {
			return $value;
		}
		return number_format($value, $pad, $decPoint, '');
	}

	/**
	 * parse 2,5 - 2.5 2:30 2:31:58 or even 2011-11-12 10:10:10
	 * now supports negative values like -2,5 -2,5 -2:30 -:30 or -4
	 * @param string
	 * @return int: seconds
	 * 2011-03-06 ms
	 */
	public static function parseTime($duration, $allowed = array(':', '.', ',')) {
		if (empty($duration)) {
			return 0;
		}
		$parts = explode(' ', $duration);
		$duration = array_pop($parts);

		if (strpos($duration, '.') !== false && in_array('.', $allowed)) {
			App::uses('NumberLib', 'Tools.Lib');
			//$numberLib = new NumberLib();
			$duration = NumberLib::decimalToStandardTime($duration, 2, ':');
		} elseif (strpos($duration, ',') !== false && in_array(',', $allowed)) {
			App::uses('NumberLib', 'Tools.Lib');
			$duration = str_replace(',', '.', $duration);
			$duration = NumberLib::decimalToStandardTime($duration, 2, ':');
		}

		# now there is only the time schema left...
		$pieces = explode(':', $duration, 3);
		$res = 0;
		$hours = abs((int)$pieces[0])*HOUR;
		//echo pre($hours);
		$isNegative = (strpos((string)$pieces[0], '-') !== false ? true : false);

		if (count($pieces) === 3) {
			$res += $hours + ((int)$pieces[1])*MINUTE + ((int)$pieces[2])*SECOND;
		} elseif (count($pieces) === 2) {
			$res += $hours + ((int)$pieces[1])*MINUTE;
		} else {
			$res += $hours;
		}
		if ($isNegative) {
			return -$res;
		}
		return $res;
	}

	/**
	 * parse 2022-11-12 or 12.11.2022 or even 12.11.22
	 * @param string $date
	 * @return int: seconds
	 * 2011-03-09 ms
	 */
	public static function parseDate($date, $allowed = array('.', '-')) {
		$datePieces = explode(' ', $date, 2);
		$date = array_shift($datePieces);

		if (strpos($date, '.') !== false) {
			$pieces = explode('.', $date);
			$year = $pieces[2];
			if (strlen($year) === 2) {
				if ($year < 50) {
					$year = '20'.$year;
				} else {
					$year = '19'.$year;
				}
			}
			$date = mktime(0, 0, 0, $pieces[1], $pieces[0], $year);

		} elseif (strpos($date, '-') !== false) {
			//$pieces = explode('-', $date);
			$date = strtotime($date);
		} else {
			return 0;
		}
		return $date;
	}


	/**
	 * return strings like 2:30 (later //TODO: or 2:33:99) from seconds etc
	 * @param int: seconds
	 * @return string
	 * 2011-03-06 ms
	 */
	public static function buildTime($duration, $mode = 'H:MM') {
		if ($duration < 0) {
			$duration = abs($duration);
			$isNegative = true;
		}

		$minutes = $duration%HOUR;
		$hours = ($duration - $minutes)/HOUR;
		$res = (int)$hours.':'.str_pad(intval($minutes/MINUTE), 2, '0', STR_PAD_LEFT);
		if (strpos($mode, 'SS') !== false) {
			//TODO
		}
		if (!empty($isNegative)) {
			$res = '-'.$res;
		}
		return $res;
	}

	/**
	 * return strings like 2:33:99 from seconds etc
	 * @param int: seconds
	 * @return string
	 * 2011-03-09 ms
	 */
	public static function buildDefaultTime($duration) {
		$minutes = $duration%HOUR;
		$duration = $duration - $minutes;
		$hours = ($duration)/HOUR;

		//$duration = $minutes*MINUTE;

		$seconds = $minutes%MINUTE;
		return self::pad($hours).':'.self::pad($minutes/MINUTE).':'.self::pad($seconds/SECOND);
	}

	public static function pad($value, $length = 2) {
		return str_pad(intval($value), $length, '0', STR_PAD_LEFT);
	}
}


