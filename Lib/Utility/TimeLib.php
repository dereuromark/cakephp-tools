<?php
App::uses('CakeTime', 'Utility');

/**
 * Extend CakeNumber with a few important improvements:
 * - correct timezones for date only input and therefore unchanged day here
 *
 */
class TimeLib extends CakeTime {

	/**
	 * Detect if a timezone has a DST
	 *
	 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
	 * @return boolean
	 */
	public static function hasDaylightSavingTime($timezone = null) {
		$timezone = self::timezone($timezone);
		// a date outside of DST
		$offset = $timezone->getOffset(new DateTime('@' . mktime(0, 0, 0, 2, 1, date('Y'))));
		$offset = $offset / HOUR;

		// a date inside of DST
		$offset2 = $timezone->getOffset(new DateTime('@' . mktime(0, 0, 0, 8, 1, date('Y'))));
		$offset2 = $offset2 / HOUR;

		return abs($offset2 - $offset) > 0;
	}

	/**
	 * Calculate the current GMT offset from a timezone string (respecting DST)
	 *
	 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
	 * @return integer Offset in hours
	 */
	public function getGmtOffset($timezone = null) {
		$timezone = self::timezone($timezone);
		$offset = $timezone->getOffset(new DateTime('@' . time()));
		$offset = $offset / HOUR;
		return $offset;
	}

	/**
	 * Gets the timezone that is closest to the given coordinates
	 *
	 * @param float $lag
	 * @param float $lng
	 * @return DateTimeZone Timezone object
	 */
	public function timezoneByCoordinates($lat, $lng) {
		$current = array('timezone' => null, 'distance' => 0);
		$identifiers = DateTimeZone::listIdentifiers();
		foreach ($identifiers as $identifier) {
			//TODO
		}
		return $current['timezone'];
	}

	/**
	 * Calculate the difference between two dates
	 *
	 * TODO: deprecate in favor of DateTime::diff() etc which will be more precise
	 *
	 * should only be used for < month (due to the different month lenghts it gets fuzzy)
	 *
	 * @param mixed $start (db format or timestamp)
	 * @param mixex §end (db format or timestamp)
	 * @return integer: the distance in seconds
	 */
	public static function difference($startTime = null, $endTime = null, $options = array()) {
		if (!is_int($startTime)) {
			$startTime = strtotime($startTime);
		}
		if (!is_int($endTime)) {
			$endTime = strtotime($endTime);
		}
		//@FIXME: make it work for > month
		return abs($endTime - $startTime);
	}

	/**
	 * Calculate the age using start and optional end date.
	 * End date defaults to current date.
	 *
	 * @param start date (if empty, use today)
	 * @param end date (if empty, use today)
	 * start and end cannot be both empty!
	 * @param accuracy (year only = 0, incl months/days = 2)
	 * if > 0, returns array!!! ('days'=>x,'months'=>y,'years'=>z)
	 *
	 * does this work too?
	 *  $now = mktime(0,0,0,date("m"),date("d"),date("Y"));
	 *  $birth = mktime(0,0,0, $monat, $tag, $jahr);
	 *  $age = intval(($now - $birth) / (3600 * 24 * 365));
	 * @return integer age (0 if both timestamps are equal or empty, -1 on invalid dates)
	 */
	public static function age($start = null, $end = null, $accuracy = 0) {
		$age = 0;
		if (empty($start) && empty($end) || $start == $end) {
			return 0;
		}

		if (empty($start)) {
			list($yearS, $monthS, $dayS) = explode('-', date(FORMAT_DB_DATE));
		} else {
			$startDate = self::fromString($start);
			$yearS = date('Y', $startDate);
			$monthS = date('m', $startDate);
			$dayS = date('d', $startDate);
			if (!checkdate($monthS, $dayS, $yearS)) {
				return -1;
			}
		}
		if (empty($end)) {
			list($yearE, $monthE, $dayE) = explode('-', date(FORMAT_DB_DATE));
		} else {
			$endDate = self::fromString($end);
			$yearE = date('Y', $endDate);
			$monthE = date('m', $endDate);
			$dayE = date('d', $endDate);
			if (!checkdate($monthE, $dayE, $yearE)) {
				return -1;
			}
		}

		//$startDate = mktime(0,0,0, $monthS, $dayS, $yearS);
		//$endDate = mktime(0,0,0, $monthE, $dayE, $yearE);
		//$age = intval(($endDate - $startDate) / (3600 * 24 * 365));
		//$age = self::timef($endDate-$startDate, 'Y'); # !!! timef function

		$nTag = $dayE;
		$nMonat = $monthE;
		$nJahr = $yearE;
		$gTag = $dayS;
		$gMonat = $monthS;
		$gJahr = $yearS;
		$gDate = mktime(0, 0, 0, $gTag, $gMonat, $gJahr);

		if (($nMonat > $gMonat)||(($nMonat == $gMonat)&&($nTag > $gTag))||(($nMonat == $gMonat)&&($nTag == $gTag))) {
			$age = $nJahr - $gJahr; // is correct if one already had his birthday this year
		} else {
			$age = $nJahr - $gJahr - 1; // is correct if one didnt have his birthday yet in this year
		}
		return $age;
		//TODO: test this short method
		//return (date("Y",time()) - $val);
	}

	/**
	 * Try to return the age only with the year available
	 * can be e.g. 22/23
	 *
	 * @param integer $year
	 * @param integer $month (optional)
	 * @return integer Age
	 */
	public static function ageByYear($year, $month = null) {
		if ($month === null) {
			$maxAge = self::age(mktime(0, 0, 0, 1, 1, $year));
			$minAge = self::age(mktime(23, 59, 59, 12, 31, $year));
			$ages = array_unique(array($minAge, $maxAge));
			return implode('/', $ages);
		}
		if (date('n') == $month) {
			$maxAge = self::age(mktime(0, 0, 0, $month, 1, $year));
			$minAge = self::age(mktime(23, 59, 59, $month, self::daysInMonth($year, $month), $year));

			$ages = array_unique(array($minAge, $maxAge));
			return implode('/', $ages);
		}
		return self::age(mktime(0, 0, 0, $month, 1, $year));
	}

	/**
	 * @param integer $year
	 * @param integer $sign
	 * @return mixed
	 */
	public static function ageByHoroscope($year, $sign) {
		App::uses('ZodiacLib', 'Tools.Misc');
		$Zodiac = new ZodiacLib();
		$range = $Zodiac->getRange($sign);

		if ($sign == ZodiacLib::SIGN_CAPRICORN) {
			// undefined
			return array(date('Y') - $year - 1, date('Y') - $year);
		}
		if ($range[0][0] > date('m') || ($range[0][0] == date('m') && $range[0][1] > date('d'))) {
			// not over
			return date('Y') - $year - 1;
		}
		if ($range[1][0] < date('m') || ($range[1][0] == date('m') && $range[1][1] <= date('d'))) {
			// over
			return date('Y') - $year;
		}
		return array(date('Y') - $year - 1, date('Y') - $year);
	}

	/**
	 * Rounded age depended on steps (e.g. age 16 with steps = 10 => "11-20")
	 * //FIXME
	 * //TODO: move to helper?
	 *
	 * @param integer $year
	 * @param integer $month
	 * @param integer $day
	 * @param integer $steps
	 * @return mixed
	 */
	public static function ageRange($year, $month = null, $day = null, $steps = 1) {
		if ($month == null && $day == null) {
			$age = date('Y') - $year - 1;
		} elseif ($day == null) {
			if ($month >= date('m'))
				$age = date('Y') - $year - 1;
			else
				$age = date('Y') - $year;
		} else {
			if ($month > date('m') || ($month == date('m') && $day > date('d')))
				$age = date('Y') - $year - 1;
			else
				$age = date('Y') - $year;
		}
		if ($age % $steps == 0) {
			$lowerRange = $age - $steps + 1;
			$upperRange = $age;
		} else {
			$lowerRange = $age - ($age % $steps) + 1;
			$upperRange = $age - ($age % $steps) + $steps;
		}
		if ($lowerRange == $upperRange) {
			return $upperRange;
		}
		return array($lowerRange, $upperRange);
	}

	/**
	 * Return the days of a given month.
	 *
	 * @param integer $year
	 * @param integer $month
	 */
	public static function daysInMonth($year, $month) {
		return date("t", mktime(0, 0, 0, $month, 1, $year));
	}

	/**
	 * Calendar Week (current week of the year).
	 * //TODO: use timestamp - or make the function able to use timestamps at least (besides dateString)
	 *
	 * date('W', $time) returns ISO6801 week number.
	 * Exception: Dates of the calender week of the previous year return 0. In this case the cweek of the
	 * last week of the previous year should be used.
	 *
	 * @param date in DB format - if none is passed, current day is used
	 * @param integer $relative - weeks relative to the date (+1 next, -1 previous etc)
	 * @return string
	 */
	public static function cWeek($dateString = null, $relative = 0) {
		//$time = self::fromString($dateString);
		if (!empty($dateString)) {
			$date = explode(' ', $dateString);
			list ($y, $m, $d) = explode('-', $date[0]);
			$t = mktime(0, 0, 0, $m, $d, $y);
		} else {
			$d = date('d');
			$m = date('m');
			$y = date('Y');
			$t = time();
		}

		$relative = (int)$relative;
		if ($relative != 0) {
			$t += WEEK * $relative;	// 1day * 7 * relativeWeeks
		}

		if (($kw = date('W', $t)) === 0) {
			$kw = 1 + date($t - DAY * date('w', $t), 'W');
			$y--;
		}
		//echo "Der $d.$m.$y liegt in der Kalenderwoche $kw/$y";

		return $kw . '/' . $y;
	}

	/**
	 * Return the timestamp to a day in a specific cweek
	 * 0=sunday to 7=saturday (default)
	 *
	 * @return timestamp of the weekDay
	 * @FIXME: offset
	 * not needed, use localDate!
	 */
	public static function cWeekDay($cweek, $year, $day, $offset = 0) {
		$cweekBeginning = self::cweekBeginning($year, $cweek);
		return $cweekBeginning + $day * DAY;
	}

	/**
	 * @FIXME ???
	 * Get number of days since the start of the week.
	 * 1 = monday, 7 = sunday ? should be 0=sunday to 7=saturday (default)
	 * @param integer $num Number of day.
	 * @return integer Days since the start of the week.
	 */
	public static function cWeekMod($num, $offset = 0) {
		$base = 7;
		return ($num - $base * floor($num / $base));
	}

	/**
	 * Calculate the beginning of a calenderweek
	 * if no cweek is given get the beginning of the first week of the year
	 *
	 * @param year (format xxxx)
	 * @param cweek (optional, defaults to first, range 1...52/53)
	 */
	public static function cWeekBeginning($year, $cweek = null) {
		if ((int)$cweek <= 1 || (int)$cweek > self::cweeks($year)) {
			$first = mktime(0, 0, 0, 1, 1, $year);
			$wtag = date('w', $first);

			if ($wtag <= 4) {
				/*Donnerstag oder kleiner: auf den Montag zurückrechnen.*/
				$firstmonday = mktime(0, 0, 0, 1, 1 - ($wtag - 1), $year);
			} elseif ($wtag != 1) {
				/*auf den Montag nach vorne rechnen.*/
				$firstmonday = mktime(0, 0, 0, 1, 1 + (7 - $wtag + 1), $year);
			} else {
				$firstmonday = $first;
			}
			return $firstmonday;
		}
		$monday = strtotime($year . 'W' . str_pad($cweek, 2, '0', STR_PAD_LEFT) . '1');
		return $monday;
	}

	/**
	 * Calculate the ending of a calenderweek
	 * if no cweek is given get the ending of the last week of the year
	 *
	 * @param year (format xxxx)
	 * @param cweek (optional, defaults to last, range 1...52/53)
	 */
	public static function cWeekEnding($year, $cweek = null) {
		if ((int)$cweek < 1 || (int)$cweek >= self::cweeks($year)) {
			return self::cweekBeginning($year + 1) - 1;
		}
		return self::cweekBeginning($year, intval($cweek) + 1) - 1;
	}

	/**
	 * Calculate the amount of calender weeks in a year
	 *
	 * @param year (format xxxx, defaults to current year)
	 * @return integer: 52 or 53
	 */
	public static function cWeeks($year = null) {
		if ($year === null) {
			$year = date('Y');
		}
		return date('W', mktime(23, 59, 59, 12, 28, $year));
	}

	/**
	 * @param year (format xxxx, defaults to current year)
	 * @return boolean Success
	 */
	public static function isLeapYear($year) {
		if ($year % 4 != 0) {
			return false;
		}
		if ($year % 400 == 0) {
			return true;
		}
		if ($year > 1582 && $year % 100 == 0) {
			// if gregorian calendar (>1582), century not-divisible by 400 is not leap
			return false;
		}
		return true;
	}

	/**
	 * Handles month/year increment calculations in a safe way, avoiding the pitfall of "fuzzy" month units.
	 *
	 * @param mixed $startDate Either a date string or a DateTime object
	 * @param integer $years Years to increment/decrement
	 * @param integer $months Months to increment/decrement
	 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
	 * @return object DateTime with incremented/decremented month/year values.
	 */
	public static function incrementDate($startDate, $years = 0, $months = 0, $days = 0, $timezone = null) {
		if (!is_object($startDate)) {
			$startDate = new DateTime($startDate);
			$startDate->setTimezone($timezone ? new DateTimeZone($timezone) : self::timezone());
		}
		$startingTimeStamp = $startDate->getTimestamp();
		// Get the month value of the given date:
		$monthString = date('Y-m', $startingTimeStamp);
		// Create a date string corresponding to the 1st of the give month,
		// making it safe for monthly/yearly calculations:
		$safeDateString = "first day of $monthString";

		// offset is wrong
		$months++;
		// Increment date by given month/year increments:
		$incrementedDateString = "$safeDateString $months month $years year";
		$newTimeStamp = strtotime($incrementedDateString) + $days * DAY;
		$newDate = DateTime::createFromFormat('U', $newTimeStamp);
		return $newDate;
	}

	/**
	 * Get the age bounds (min, max) as timestamp that would result in the given age(s)
	 * note: expects valid age (> 0 and < 120)
	 *
	 * @param $firstAge
	 * @param $secondAge (defaults to first one if not specified)
	 * @return array('min'=>$min, 'max'=>$max);
	 */
	public static function ageBounds($firstAge, $secondAge = null, $returnAsString = false, $relativeTime = null) {
		if ($secondAge === null) {
			$secondAge = $firstAge;
		}
		//TODO: other relative time then today should work as well
		$Date = new DateTime($relativeTime !== null ? $relativeTime : 'now');

		$max = mktime(23, 23, 59, $Date->format('m'), $Date->format('d'), $Date->format('Y') - $firstAge);
		$min = mktime(0, 0, 1, $Date->format('m'), $Date->format('d') + 1, $Date->format('Y') - $secondAge - 1);

		if ($returnAsString) {
			$max = date(FORMAT_DB_DATE, $max);
			$min = date(FORMAT_DB_DATE, $min);
		}
		return array('min' => $min, 'max' => $max);
	}

	/**
	 * For birthdays etc
	 *
	 * @param date
	 * @param string days with +-
	 * @param options
	 */
	public static function isInRange($dateString, $seconds, $options = array()) {
		//$newDate = is_int($dateString) ? $dateString : strtotime($dateString);
		//$newDate += $seconds;
		$newDate = time();
		return self::difference($dateString, $newDate) <= $seconds;
	}

	/**
	 * Outputs Date(time) Sting nicely formatted (+ localized!)
	 *
	 * @param string $dateString,
	 * @param string $format (YYYY-MM-DD, DD.MM.YYYY)
	 * @param array $options
		* - timezone: User's timezone
		* - default (defaults to "-----")
	 */
	public static function localDate($dateString = null, $format = null, $options = array()) {
		$defaults = array('default' => '-----', 'timezone' => null);
		$options = array_merge($defaults, $options);

		if ($options['timezone'] === null && strlen($dateString) === 10) {
			$options['timezone'] = date_default_timezone_get();
		}
		if ($dateString === null) {
			$dateString = time();
		}
		$date = self::fromString($dateString, $options['timezone']);

		if ($date === null || $date === false || $date <= 0) {
			return $options['default'];
		}
		if ($format === null) {
			if (is_int($dateString) || strpos($dateString, ' ') !== false) {
				$format = FORMAT_LOCAL_YMDHM;
			} else {
				$format = FORMAT_LOCAL_YMD;
			}
		}
		return parent::_strftime($format, $date);
	}

	/**
	 * Outputs Date(time) Sting nicely formatted
	 *
	 * @param string $dateString,
	 * @param string $format (YYYY-MM-DD, DD.MM.YYYY)
	 * @param array $options
		* - timezone: User's timezone
		* - default (defaults to "-----")
	 */
	public static function niceDate($dateString = null, $format = null, $options = array()) {
		$defaults = array('default' => '-----', 'timezone' => null);
		$options = array_merge($defaults, $options);

		if ($options['timezone'] === null && strlen($dateString) === 10) {
			$options['timezone'] = date_default_timezone_get();
		}
		if ($dateString === null) {
			$dateString = time();
		}
		$date = self::fromString($dateString, $options['timezone']);

		if ($date === null || $date === false || $date <= 0) {
			return $options['default'];
		}

		if ($format === null) {
			if (is_int($dateString) || strpos($dateString, ' ') !== false) {
				$format = FORMAT_NICE_YMDHM;
			} else {
				$format = FORMAT_NICE_YMD;
			}
		}

		$ret = date($format, $date);

		if (!empty($options['oclock']) && $options['oclock']) {
			switch ($format) {
				case FORMAT_NICE_YMDHM:
				case FORMAT_NICE_YMDHMS:
				case FORMAT_NICE_YMDHM:
				case FORMAT_NICE_HM:
				case FORMAT_NICE_HMS:
					$ret .= ' ' . __('o\'clock');
					break;
			}
		}

		return $ret;
	}

	/**
	 * Return the translation to a specific week day
	 *
	 * @param integer $day:
	 * 0=sunday to 7=saturday (default numbers)
	 * @param boolean $abbr (if abbreviation should be returned)
	 * @param offset: 0-6 (defaults to 0) [1 => 1=monday to 7=sunday]
	 * @return string translatedText
	 */
	public static function day($day, $abbr = false, $offset = 0) {
		$days = array(
			'long' => array(
				'Sunday',
				'Monday',
				'Tuesday',
				'Wednesday',
				'Thursday',
				'Friday',
				'Saturday'
			),
			'short' => array(
				'Sun',
				'Mon',
				'Tue',
				'Wed',
				'Thu',
				'Fri',
				'Sat'
			)
		);
		$day = (int) $day;
		//pr($day);
		if ($offset) {
			$day = ($day + $offset) % 7;
		}
		//pr($day);
		if ($abbr) {
			return __($days['short'][$day]);
		}
		return __($days['long'][$day]);
	}

	/**
	 * Return the translation to a specific week day
	 *
	 * @param integer $month:
	 * 1..12
	 * @param boolean $abbr (if abbreviation should be returned)
	 * @param array $options
	 * - appendDot (only for 3 letter abbr; defaults to false)
	 * @return string translatedText
	 */
	public static function month($month, $abbr = false, $options = array()) {
		$months = array(
			'long' => array(
				'January',
				'February',
				'March',
				'April',
				'May',
				'June',
				'July',
				'August',
				'September',
				'October',
				'November',
				'December'
			),
			'short' => array(
				'Jan',
				'Feb',
				'Mar',
				'Apr',
				'May',
				'Jun',
				'Jul',
				'Aug',
				'Sep',
				'Oct',
				'Nov',
				'Dec'
			),
		);
		$month = (int) ($month - 1);
		if (!$abbr) {
			return __($months['long'][$month]);
		}
		$monthName = __($months['short'][$month]);
		if (!empty($options['appendDot']) && strlen(__($months['long'][$month])) > 3) {
			$monthName .= '.';
		}
		return $monthName;
	}

	/**
	 * Months
	 *
	 * @return array (for forms etc)
	 */
	public static function months($monthKeys = array(), $options = array()) {
		if (!$monthKeys) {
			$monthKeys = range(1, 12);
		}
		$res = array();
		$abbr = isset($options['abbr']) ? $options['abbr'] : false;
		foreach ($monthKeys as $key) {
			$res[str_pad($key, 2, '0', STR_PAD_LEFT)] = self::month($key, $abbr, $options);
		}
		return $res;
	}

	/**
	 * Weekdays
	 *
	 * @return array (for forms etc)
	 */
	public static function days($dayKeys = array(), $options = array()) {
		if (!$dayKeys) {
			$dayKeys = range(0, 6);
		}
		$res = array();
		$abbr = isset($options['abbr']) ? $options['abbr'] : false;
		$offset = isset($options['offset']) ? $options['offset'] : 0;
		foreach ($dayKeys as $key) {
			$res[$key] = self::day($key, $abbr, $offset);
		}
		return $res;
	}

	/**
	 * Can convert time from one unit to another
	 *
	 * @param integer INT | time
	 * @param from CHAR
	 * @param to CHAR
	 * @param options: acc=>INT [accuracy], showZero=>BOOL, returnArray=>BOOL
	 * @return mixed
	 */
	public static function convertTime($int, $from, $to, $options = array()) {
		$accuracy = 0;	// 0 = only the "to"-element, 1..n = higher accurancy
		$showZero = false;	// show only the non-zero elements
		$returnArray = false;	// return as array instead of as string
		if (!empty($options)) {
			if (isset($options['acc'])) {
				$accuracy = (int)$options['acc'];
			}
			if (isset($options['showZero'])) {
				$showZero = (int)$options['showZero'];
			}
			if (isset($options['returnArray'])) {
				$returnArray = $options['returnArray'];
			}
		}

		$times = array(
			's' => '0',
			'm' => '1',
			'h' => '2',
			'd' => '3',
			'w' => '4',
			'm' => '5',
			'y' => '6',
		);
		$options = array(
			'0' => array(
				'steps' => array('1' => 60, '2' => 3600, '3' => 86400, '4' => 86400 * 7, '5' => 86400 * 30, '6' => 86400 * 365),
				'down' => 0,
				'up' => 60,
				'short' => 's',
				'long' => 'seconds'
			),
			'1' => array(
				'steps' => array('0' => 60, '2' => 60, '3' => 60 * 24, '4' => 60 * 24 * 7, '5' => 60 * 24 * 30, '6' => 60 * 24 * 365),
				'down' => 60,
				'up' => 60,
				'short' => 'm',
				'long' => 'minutes'
			),
			'2' => array(
				'steps' => array('0' => 3600, '1' => 60, '3' => 24, '4' => 24 * 7, '5' => 24 * 30, '6' => 24 * 365),
				'down' => 60,
				'up' => 24,
				'short' => 'h',
				'long' => 'hours'
			),
			'3' => array(
				'steps' => array('0' => 86400, '1' => 3600, '2' => 24, '4' => 7, '5' => 30, '6' => 365),
				'down' => 24,
				'up' => 7,
				'short' => 'd',
				'long' => 'days'
			),
			'4' => array(
				'steps' => array('0' => 86400 * 7, '1' => 60 * 24 * 7, '2' => 24 * 7, '3' => 7, '5' => 4.2, '6' => 52),
				'down' => 7,
				'up' => 4.2,
				'short' => 'w',
				'long' => 'weeks'
			),
			'5' => array(
				'steps' => array('0' => 86400 * 30, '1' => 60 * 24 * 30, '2' => 24 * 30, '3' => 30, '4' => 4.2, '6' => 12),
				'down' => 4.2,
				'up' => 12,
				'short' => 'm',
				'long' => 'months'
			),
			'6' => array(
				'steps' => array('0' => 86400 * 365, '1' => 60 * 24 * 365, '2' => 24 * 365, '3' => 365, '4' => 52, '5' => 12),
				'down' => 12,
				'up' => 0,
				'short' => 'y',
				'long' => 'years'
			),
		);

		if (array_key_exists($from, $times) && array_key_exists($to, $times)) {
			$begin = $times[$from];
			$end = $times[$to];
		}

		$minutes = $int;
		if ($minutes < 60) {
			return $minutes . 'min';
		}

		$calculated = floor($minutes / 60) . "h " . ($minutes % 60) . "min";

		if ($returnArray) {
			// return as array
		} else {
			// convert to the desired string
		}

		return $calculated;
	}

	/**
	 * Returns the difference between a time and now in a "fuzzy" way.
	 * Note that unlike [span], the "local" timestamp will always be the
	 * current time. Displaying a fuzzy time instead of a date is usually
	 * faster to read and understand.
	 *
	 * $span = fuzzy(time() - 10); // "moments ago"
	 * $span = fuzzy(time() + 20); // "in moments"
	 *
	 * @param integer "remote" timestamp
	 * @return string
	 */
	public static function fuzzy($timestamp) {
		// Determine the difference in seconds
		$offset = abs(time() - $timestamp);

		return self::fuzzyFromOffset($offset, $timestamp <= time());
	}

	/**
	 * @param integer $offset in seconds
	 * @param boolean $past (defaults to null: return plain text)
	 * - new: if not boolean but a string use this as translating text
	 * @return string text (i18n!)
	 */
	public static function fuzzyFromOffset($offset, $past = null) {
		if ($offset <= MINUTE) {
			$span = 'moments';
		} elseif ($offset < (MINUTE * 20)) {
			$span = 'a few minutes';
		} elseif ($offset < HOUR) {
			$span = 'less than an hour';
		} elseif ($offset < (HOUR * 4)) {
			$span = 'a couple of hours';
		} elseif ($offset < DAY) {
			$span = 'less than a day';
		} elseif ($offset < (DAY * 2)) {
			$span = 'about a day';
		} elseif ($offset < (DAY * 4)) {
			$span = 'a couple of days';
		} elseif ($offset < WEEK) {
			$span = 'less than a week';
		} elseif ($offset < (WEEK * 2)) {
			$span = 'about a week';
		} elseif ($offset < MONTH) {
			$span = 'less than a month';
		} elseif ($offset < (MONTH * 2)) {
			$span = 'about a month';
		} elseif ($offset < (MONTH * 4)) {
			$span = 'a couple of months';
		} elseif ($offset < YEAR) {
			$span = 'less than a year';
		} elseif ($offset < (YEAR * 2)) {
			$span = 'about a year';
		} elseif ($offset < (YEAR * 4)) {
			$span = 'a couple of years';
		} elseif ($offset < (YEAR * 8)) {
			$span = 'a few years';
		} elseif ($offset < (YEAR * 12)) {
			$span = 'about a decade';
		} elseif ($offset < (YEAR * 24)) {
			$span = 'a couple of decades';
		} elseif ($offset < (YEAR * 64)) {
			$span = 'several decades';
		} else {
			$span = 'a long time';
		}
		if ($past === true) {
			// This is in the past
			return __('%s ago', __($span));
		}
		if ($past === false) {
			// This in the future
			return __('in %s', __($span));
		}
		if ($past !== null) {
			// Custom translation
			return __($past, __($span));
		}
		return __($span);
	}

	/**
	 * Time length to human readable format.
	 *
	 * @param integer $seconds
	 * @param string format: format
	 * @param options
	 * - boolean v: verbose
	 * - boolean zero: if false: 0 days 5 hours => 5 hours etc.
	 * - int: accuracy (how many sub-formats displayed?) //TODO
	 * 2009-11-21 ms
	 * @see timeAgoInWords()
	 */
	public static function lengthOfTime($seconds, $format = null, $options = array()) {
		$defaults = array('verbose' => true, 'zero' => false, 'separator' => ', ', 'default' => '');
		$ret = '';
			$j = 0;

		$options = array_merge($defaults, $options);

		if (!$options['verbose']) {
			$s = array(
				'm' => 'mth',
				'd' => 'd',
				'h' => 'h',
				'i' => 'm',
				's' => 's'
			);
			$p = $s;
		} else {
			$s = array(
		'm' => ' ' . __('Month'), # translated
				'd' => ' ' . __('Day'),
				'h' => ' ' . __('Hour'),
				'i' => ' ' . __('Minute'),
				's' => ' ' . __('Second'),
			);
			$p = array(
		'm' => ' ' . __('Months'), # translated
				'd' => ' ' . __('Days'),
				'h' => ' ' . __('Hours'),
				'i' => ' ' . __('Minutes'),
				's' => ' ' . __('Seconds'),
			);
		}

		if (!isset($format)) {
			//if (floor($seconds / MONTH) > 0) $format = 'Md';
			if (floor($seconds / DAY) > 0) $format = 'Dh';
			elseif (floor($seconds / 3600) > 0) $format = 'Hi';
			elseif (floor($seconds / 60) > 0) $format = 'Is';
			else $format = 'S';
		}

		for ($i = 0; $i < mb_strlen($format); $i++) {
			switch (mb_substr($format, $i, 1)) {
			case 'D':
				$str = floor($seconds / 86400);
				break;
			case 'd':
				$str = floor($seconds / 86400 % 30);
				break;
			case 'H':
				$str = floor($seconds / 3600);
				break;
			case 'h':
				$str = floor($seconds / 3600 % 24);
				break;
			case 'I':
				$str = floor($seconds / 60);
				break;
			case 'i':
				$str = floor($seconds / 60 % 60);
				break;
			case 'S':
				$str = $seconds;
				break;
			case 's':
				$str = floor($seconds % 60);
				break;
			default:
				return '';
				break;
			}

			if ($str > 0 || $j > 0 || $options['zero'] || $i == mb_strlen($format) - 1) {
				if ($j > 0) {
					$ret .= $options['separator'];
				}

				$j++;

				$x = mb_strtolower(mb_substr($format, $i, 1));

				if ($str == 1) {
					$ret .= $str . $s[$x];
				} else {
					$title = $p[$x];
					if (!empty($options['plural'])) {
						if (mb_substr($title, -1, 1) === 'e') {
							$title .= $options['plural'];
						}
					}
					$ret .= $str . $title;
				}
			}
		}
		return $ret;
	}

	/**
	 * Time relative to NOW in human readable format - absolute (negative as well as positive)
	 * //TODO: make "now" adjustable
	 *
	 * @param mixed $datestring
	 * @param string format: format
	 * @param options
	 * - default, separator
	 * - boolean zero: if false: 0 days 5 hours => 5 hours etc.
	 * - verbose/past/future: string with %s or boolean true/false
	 */
	public static function relLengthOfTime($dateString, $format = null, $options = array()) {
		if ($dateString !== null) {
			$timezone = null;
			$sec = time() - self::fromString($dateString, $timezone);
			$type = ($sec > 0) ? -1 : (($sec < 0) ? 1 : 0);
			$sec = abs($sec);
		} else {
			$sec = 0;
			$type = 0;
		}

		$defaults = array(
			'verbose' => __('justNow'), 'zero' => false, 'separator' => ', ',
			'future' => __('In %s'), 'past' => __('%s ago'), 'default' => '');
		$options = array_merge($defaults, $options);

		$ret = self::lengthOfTime($sec, $format, $options);

		if ($type == 1) {
			if ($options['future'] !== false) {
				return sprintf($options['future'], $ret);
			}
			return array('future' => $ret);
		}
		if ($type == -1) {
			if ($options['past'] !== false) {
				return sprintf($options['past'], $ret);
			}
			return array('past' => $ret);
		}
		if ($options['verbose'] !== false) {
			return $options['verbose'];
		}
		return $options['default'];
	}

	/**
	 * Convenience method to convert a given date
	 *
	 * @param string
	 * @param string
	 * @param integer $timezone User's timezone
	 * @return string Formatted date
	 */
	public static function convertDate($oldDateString, $newDateFormatString, $timezone = null) {
		$Date = new DateTime($oldDateString, $timezone);
		return $Date->format($newDateFormatString);
	}

	/**
	 * Returns true if given datetime string was day before yesterday.
	 *
	 * @param string $dateString Datetime string or Unix timestamp
	 * @param integer $timezone User's timezone
	 * @return boolean True if datetime string was day before yesterday
	 */
	public static function wasDayBeforeYesterday($dateString, $timezone = null) {
		$date = self::fromString($dateString, $timezone);
		return date(FORMAT_DB_DATE, $date) == date(FORMAT_DB_DATE, time() - 2 * DAY);
	}

	/**
	 * Returns true if given datetime string is the day after tomorrow.
	 *
	 * @param string $dateString Datetime string or Unix timestamp
	 * @param integer $timezone User's timezone
	 * @return boolean True if datetime string is day after tomorrow
	 */
	public static function isDayAfterTomorrow($dateString, $timezone = null) {
		$date = self::fromString($dateString, $timezone);
		return date(FORMAT_DB_DATE, $date) == date(FORMAT_DB_DATE, time() + 2 * DAY);
	}

	/**
	 * Returns true if given datetime string is not today AND is in the future.
	 *
	 * @param string $dateString Datetime string or Unix timestamp
	 * @param integer $timezone User's timezone
	 * @return boolean True if datetime is not today AND is in the future
	 */
	public static function isNotTodayAndInTheFuture($dateString, $timezone = null) {
		$date = self::fromString($dateString, $timezone);
		return date(FORMAT_DB_DATE, $date) > date(FORMAT_DB_DATE, time());
	}

	/**
	 * Returns true if given datetime string is not now AND is in the future.
	 *
	 * @param string $dateString Datetime string or Unix timestamp
	 * @param integer $timezone User's timezone
	 * @return boolean True if datetime is not today AND is in the future
	 */
	public static function isInTheFuture($dateString, $timezone = null) {
		$date = self::fromString($dateString, $timezone);
		return date(FORMAT_DB_DATETIME, $date) > date(FORMAT_DB_DATETIME, time());
	}

	/**
	 * Try to parse date from various input formats
	 * - DD.MM.YYYY, DD/MM/YYYY, YYYY-MM-DD, YYYY, YYYY-MM, ...
	 * - i18n: Today, Yesterday, Tomorrow
	 *
	 * @param string $date to parse
	 * @param format to parse (null = auto)
	 * @param type
	 * - start: first second of this interval
	 * - end: last second of this interval
	 * @return integer timestamp
	 */
	public static function parseLocalizedDate($date, $format = null, $type = 'start') {
		$date = trim($date);
		$i18n = array(
			strtolower(__('Today')) => array('start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y')))),
			strtolower(__('Tomorrow')) => array('start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y')) + DAY), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y')) + DAY)),
			strtolower(__('Yesterday')) => array('start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y')) - DAY), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y')) - DAY)),
			strtolower(__('The day after tomorrow')) => array('start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y')) + 2 * DAY), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y')) + 2 * DAY)),
			strtolower(__('The day before yesterday')) => array('start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 2 * DAY), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y')) - 2 * DAY)),
		);
		if (isset($i18n[strtolower($date)])) {
			return $i18n[strtolower($date)][$type];
		}

		if ($format) {
			$res = DateTime::createFromFormat($format, $date);
			$res = $res->format(FORMAT_DB_DATE) . ' ' . ($type === 'end' ? '23:59:59' : '00:00:00');
			return $res;
		}

		if (strpos($date, '.') !== false) {
			$explode = explode('.', $date, 3);
			$explode = array_reverse($explode);
		} elseif (strpos($date, '/') !== false) {
			$explode = explode('/', $date, 3);
			$explode = array_reverse($explode);
		} elseif (strpos($date, '-') !== false) {
			$explode = explode('-', $date, 3);
		} else {
			$explode = array($date);
		}
		if (isset($explode)) {
			for ($i = 0; $i < count($explode); $i++) {
				$explode[$i] = str_pad($explode[$i], 2, '0', STR_PAD_LEFT);
			}
			$explode[0] = str_pad($explode[0], 4, '20', STR_PAD_LEFT);

			if (count($explode) === 3) {
				return implode('-', $explode) . ' ' . ($type === 'end' ? '23:59:59' : '00:00:00');
			}
			if (count($explode) === 2) {
				return implode('-', $explode) . '-' . ($type === 'end' ? self::daysInMonth($explode[0], $explode[1]) : '01') . ' ' . ($type === 'end' ? '23:59:59' : '00:00:00');
			}
			return $explode[0] . '-' . ($type === 'end' ? '12' : '01') . '-' . ($type === 'end' ? '31' : '01') . ' ' . ($type === 'end' ? '23:59:59' : '00:00:00');
		}

		return false;
	}

	/**
	 * Parse a period (from ... to)
	 *
	 * @param string $searchString to parse
	 * @param array $options
	 * - separator (defaults to space [ ])
	 * - format (defaults to Y-m-d H:i:s)
	 * @return array period [0=>min, 1=>max]
	 */
	public static function period($string, $options = array()) {
		if (strpos($string, ' ') !== false) {
			$filters = explode(' ', $string);
			$filters = array(array_shift($filters), array_pop($filters));
		} else {
			$filters = array($string, $string);
		}
		$min = $filters[0];
		$max = $filters[1];

		//$x = preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date, $dateParts);

		//$x = Datetime::createFromFormat('Y-m-d', $string);
		//die(returns($x));

		//$actualDateTime = new DateTime($min);
		//$actualDateTime->add(new DateInterval('P1M'));

		$min = self::parseLocalizedDate($min);
		$max = self::parseLocalizedDate($max, null, 'end');

		//die($actualDateTime->format('Y-m-d'));

		//$searchParameters['conditions']['Coupon.date'] = $actualDateTime->format('Y-m-d');

		/*
		if ($min == $max) {
			if (strlen($max) > 8) {
				$max = date(FORMAT_DB_DATE, strtotime($max)+DAY);
			} elseif (strlen($max) > 5) {
				$max = date(FORMAT_DB_DATE, strtotime($max)+MONTH);
			} else {
				$max = date(FORMAT_DB_DATE, strtotime($max)+YEAR+MONTH);
			}

		}
		$min = date(FORMAT_DB_DATE, strtotime($min));
		$max = date(FORMAT_DB_DATE, strtotime($max));
		*/
		return array($min, $max);
	}

	/**
	 * Return SQL snippet for a period (beginning till end).
	 *
	 * @param string $searchString to parse
	 * @param string $fieldname (Model.field)
	 * @param array $options (see TimeLib::period)
	 * @return string query SQL Query
	 */
	public static function periodAsSql($string, $fieldName, $options = array()) {
		$period = self::period($string, $options);
		return self::daysAsSql($period[0], $period[1], $fieldName);
	}

	/**
	 * Hours, minutes
	 * e.g. 9.3 => 9.5
	 *
	 * @return float
	 */
	public static function standardToDecimalTime($value) {
		$base = (int)$value;
		$tmp = $value - $base;

		$tmp *= 100;
		$tmp *= 1 / 60;

		$value = $base + $tmp;
		return $value;
	}

	/**
	 * Hours, minutes
	 * e.g. 9.5 => 9.3
	 * with pad=2: 9.30
	 *
	 * @return string
	 */
	public static function decimalToStandardTime($value, $pad = null, $decPoint = '.') {
		$base = (int)$value;
		$tmp = $value - $base;

		$tmp /= 1 / 60;
		$tmp /= 100;

		$value = $base + $tmp;
		if ($pad === null) {
			return $value;
		}
		return number_format($value, $pad, $decPoint, '');
	}

	/**
	 * Parse 2,5 - 2.5 2:30 2:31:58 or even 2011-11-12 10:10:10
	 * now supports negative values like -2,5 -2,5 -2:30 -:30 or -4
	 *
	 * @param string
	 * @return integer: seconds
	 */
	public static function parseTime($duration, $allowed = array(':', '.', ',')) {
		if (empty($duration)) {
			return 0;
		}
		$parts = explode(' ', $duration);
		$duration = array_pop($parts);

		if (strpos($duration, '.') !== false && in_array('.', $allowed)) {
			$duration = self::decimalToStandardTime($duration, 2, ':');
		} elseif (strpos($duration, ',') !== false && in_array(',', $allowed)) {
			$duration = str_replace(',', '.', $duration);
			$duration = self::decimalToStandardTime($duration, 2, ':');
		}

		// now there is only the time schema left...
		$pieces = explode(':', $duration, 3);
		$res = 0;
		$hours = abs((int)$pieces[0]) * HOUR;
		//echo pre($hours);
		$isNegative = (strpos((string)$pieces[0], '-') !== false ? true : false);

		if (count($pieces) === 3) {
			$res += $hours + ((int)$pieces[1]) * MINUTE + ((int)$pieces[2]) * SECOND;
		} elseif (count($pieces) === 2) {
			$res += $hours + ((int)$pieces[1]) * MINUTE;
		} else {
			$res += $hours;
		}
		if ($isNegative) {
			return -$res;
		}
		return $res;
	}

	/**
	 * Parse 2022-11-12 or 12.11.2022 or even 12.11.22
	 *
	 * @param string $date
	 * @return integer: seconds
	 */
	public static function parseDate($date, $allowed = array('.', '-')) {
		$datePieces = explode(' ', $date, 2);
		$date = array_shift($datePieces);

		if (strpos($date, '.') !== false) {
			$pieces = explode('.', $date);
			$year = $pieces[2];
			if (strlen($year) === 2) {
				if ($year < 50) {
					$year = '20' . $year;
				} else {
					$year = '19' . $year;
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
	 * Return strings like 2:30 (later //TODO: or 2:33:99) from seconds etc
	 *
	 * @param integer: seconds
	 * @return string
	 */
	public static function buildTime($duration, $mode = 'H:MM') {
		if ($duration < 0) {
			$duration = abs($duration);
			$isNegative = true;
		}

		$minutes = $duration % HOUR;
		$hours = ($duration - $minutes) / HOUR;
		$res = (int)$hours . ':' . str_pad(intval($minutes / MINUTE), 2, '0', STR_PAD_LEFT);
		if (strpos($mode, 'SS') !== false) {
			//TODO
		}
		if (!empty($isNegative)) {
			$res = '-' . $res;
		}
		return $res;
	}

	/**
	 * Return strings like 2:33:99 from seconds etc
	 *
	 * @param integer: seconds
	 * @return string
	 */
	public static function buildDefaultTime($duration) {
		$minutes = $duration % HOUR;
		$duration = $duration - $minutes;
		$hours = $duration / HOUR;

		$seconds = $minutes % MINUTE;
		return self::pad($hours) . ':' . self::pad($minutes / MINUTE) . ':' . self::pad($seconds / SECOND);
	}

	public static function pad($value, $length = 2) {
		return str_pad(intval($value), $length, '0', STR_PAD_LEFT);
	}

}
