<?php

/**
 * TODO: include all helper methods
 *
 *
 * 2011-03-03 ms
 */
class DatetimeLib {

	protected $userOffset = null;
	protected $daylightSavings = false;

	public function __construct() {
		$i18n = Configure::read('Localization');
		if (!empty($i18n['time_offset'])) {
			$this->userOffset = (int)$i18n['time_offset'];
		}
		if (!empty($i18n['daylight_savings'])) {
			$this->daylightSavings = (bool)$i18n['daylight_savings'];
		}
	}



/** custom stuff **/

	/**
	 * calculate the difference between two dates
	 * should only be used for < month (due to the different month lenghts it gets fuzzy)
	 * @param mixed $start (db format or timestamp)
	 * @param mixex §end (db format or timestamp)
	 * @return int: the distance in seconds
	 * 2011-03-03 ms
	 */
	public function difference($startTime = null, $endTime = null, $options = array()) {
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
	 * @param start date (if empty, use today)
	 * @param end date (if empty, use today)
	 * start and end cannot be both empty!
	 * @param accuracy (year only = 0, incl months/days = 2)
	 * if > 0, returns array!!! ('days'=>x,'months'=>y,'years'=>z)
	 *
	 * does this work too?
	 $now = mktime(0,0,0,date("m"),date("d"),date("Y"));
	 $birth = mktime(0,0,0,$monat,$tag,$jahr);
	 $age   = intval(($now - $birth) / (3600 * 24 * 365));
	 * @return int age (0 if both timestamps are equal or empty, -1 on invalid dates)
	 * 2009-03-12 ms
	 */
	public function age($start = null, $end = null, $accuracy = 0) {
		$age = 0;
		if (empty($start) && empty($end) || $start == $end) {
			return 0;
		}

		if (empty($start)) {
			list($yearS, $monthS, $dayS) = explode('-', date(FORMAT_DB_DATE));
		} else {
			$startDate = $this->fromString($start);
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
			$endDate = $this->fromString($end);
			$yearE = date('Y', $endDate);
			$monthE = date('m', $endDate);
			$dayE = date('d', $endDate);
			if (!checkdate($monthE, $dayE, $yearE)) {
				return -1;
			}
		}

		//$startDate = mktime(0,0,0,$monthS,$dayS,$yearS);
		//$endDate = mktime(0,0,0,$monthE,$dayE,$yearE);
		//$age = intval(($endDate - $startDate) / (3600 * 24 * 365));
		//$age = $this->timef($endDate-$startDate, 'Y'); # !!! timef function

		$n_tag = $dayE;
		$n_monat = $monthE;
		$n_jahr = $yearE;
		$g_tag = $dayS;
		$g_monat = $monthS;
		$g_jahr = $yearS;
		$g_date = mktime(0, 0, 0, $g_tag, $g_monat, $g_jahr);

		if (($n_monat>$g_monat)||(($n_monat == $g_monat)&&($n_tag>$g_tag))||(($n_monat == $g_monat)&&($n_tag==$g_tag))) {
			$age = $n_jahr-$g_jahr; // is correct if one already had his birthday this year
		} else {
			$age = $n_jahr-$g_jahr-1; // is correct if one didnt have his birthday yet in this year
		}
		return $age;

		//TODO: test this short method
		//return (date("Y",time()) - $val);
	}

	/**
	 * try to return the age only with the year available
	 * can be e.g. 22/23
	 * @param int $year
	 * @param int $month (optional)
	 * 2011-03-11 ms
	 */
	public function ageByYear($year, $month = null) {
		if ($month === null) {
			$maxAge = $this->age(mktime(0,0,0,1,1,$year));
			$minAge = $this->age(mktime(23,59,59,12,31,$year));
			$ages = array_unique(array($minAge, $maxAge));
			return implode('/', $ages);
		}
		if (date('n') == $month) {
			$maxAge = $this->age(mktime(0, 0, 0, $month, 1, $year));
			$minAge = $this->age(mktime(23, 59, 59, $month, $this->daysInMonth($year, $month), $year));

			$ages = array_unique(array($minAge, $maxAge));
			return implode('/', $ages);
		}
		return $this->age(mktime(0, 0, 0, $month, 1, $year));
	}

	/**
	 * 2011-11-22 lb
	 */
	public function ageByHoroscope($year, $sign) {
		App::uses('ZodiacLib', 'Tools.Misc');
		$Zodiac = new ZodiacLib();
		$range = $Zodiac->getRange($sign);

		// undefined
		if ($sign == ZodiacLib::SIGN_CAPRICORN) {
			return array(date('Y') - $year - 1, date('Y') - $year);
		}
		// not over
		elseif($range[0][0] > date('m') || ($range[0][0] == date('m') && $range[0][1] > date('d'))) {
			return date('Y') - $year - 1;
		}
		// over
		elseif ($range[1][0] < date('m') || ($range[1][0] == date('m') && $range[1][1] <= date('d'))) {
			return date('Y') - $year;
		} else {
			return array(date('Y') - $year - 1, date('Y') - $year);
		}
	}

	/**
	 * rounded age depended on steps (e.g. age 16 with steps = 10 => "11-20")
	 * @FIXME
	 * TODO: move to helper?
	 * 2011-04-07 ms
	 */
	public function ageRange($year, $month = null, $day = null, $steps = 1) {
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
		if ($lowerRange == $upperRange)
			return $upperRange;
		else
			return array($lowerRange, $upperRange);
	}

	/**
	 * return the days of a given month
	 * @param int $year
	 * @param int $month
	 * 2011-11-03 ms
	 */
	public static function daysInMonth($year, $month) {
		return date("t", mktime(0, 0, 0, $month, 1, $year));
	}


	/**
	 * Calendar Week (current week of the year)
	 * @param date in DB format - if none is passed, current day is used
	 * @param int relative - weeks relative to the date (+1 next, -1 previous etc)
	 * @TODO: use timestamp - or make the function able to use timestamps at least (besides dateString)
	 *
	 * Mit date('W', $time) (großes W!) bekommst die ISO6801-Wochennummer des angegebenen Zeitpunkts, das entspricht der Europäischen Kalenderwoche - mit einer Ausnahme: Daten die zur letzten Kalenderwoche des vorherigen Jahres gehören, liefern die 0 zurück; in dem Fall solltest du dann die KW des 31.12. des Vorjahres ermitteln.
	 */
	public function cweek($dateString = null, $relative = 0) {
		//$time = $this->fromString($dateString);
		if (!empty($dateString)) {
			//pr ($dateString);
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
			$t += WEEK*$relative;	// 1day * 7 * relativeWeeks
		}

		if (0==($kw=date('W', $t))) {
				$kw = 1+date($t-DAY*date('w', $t), 'W');
				$y--;
		}
		//echo "Der $d.$m.$y liegt in der Kalenderwoche $kw/$y";

		return $kw.'/'.$y;
	}

	/**
	 * return the timestamp to a day in a specific cweek
	 * 0=sunday to 7=saturday (default)
	 * @return timestamp of the weekDay
	 * @FIXME: offset
	 * not needed, use localDate!
	 */
	public function cweekDay($cweek, $year, $day, $offset = 0) {
		$cweekBeginning = $this->cweekBeginning($year, $cweek);
		return $cweekBeginning + $day * DAY;
	}

	/**
	 * @FIXME ???
	 * Get number of days since the start of the week.
	 * 1 = monday, 7 = sunday ? should be 0=sunday to 7=saturday (default)
	 * @param int $num Number of day.
	 * @return int Days since the start of the week.
	 */
	public function cweekMod($num, $offset = 0) {
		$base = 7;
		return ($num - $base*floor($num/$base));
	}

	/**
	 * calculate the beginning of a calenderweek
	 * if no cweek is given get the beginning of the first week of the year
	 * @param year (format xxxx)
	 * @param cweek (optional, defaults to first, range 1...52/53)
	 * 2011-03-07 ms
	 */
	public function cweekBeginning($year, $cweek = null) {
		if ((int)$cweek <= 1 || (int)$cweek > self::cweeks($year)) {
			$first = mktime(0,0,0,1,1, $year);
			$wtag = date('w', $first);

			if ($wtag<=4) {
				/*Donnerstag oder kleiner: auf den Montag zurückrechnen.*/
				$firstmonday = mktime(0,0,0,1,1-($wtag-1),$year);
			} elseif ($wtag!=1) {
				/*auf den Montag nach vorne rechnen.*/
				$firstmonday = mktime(0,0,0,1,1+(7-$wtag+1),$year);
			} else {
				$firstmonday = $first;
			}
			return $firstmonday;
		}
		$monday = strtotime($year.'W'.str_pad($cweek, 2, '0', STR_PAD_LEFT).'1');
		return $monday;
	}

	/**
	 * calculate the ending of a calenderweek
	 * if no cweek is given get the ending of the last week of the year
	 * @param year (format xxxx)
	 * @param cweek (optional, defaults to last, range 1...52/53)
	 * 2011-03-07 ms
	 */
	public function cweekEnding($year, $cweek = null) {
		if ((int)$cweek < 1 || (int)$cweek >= self::cweeks($year)) {
			return self::cweekBeginning($year+1)-1;
		}
		return self::cweekBeginning($year, intval($cweek)+1)-1;
	}

	/**
	 * calculate the amount of calender weeks in a year
	 * @param year (format xxxx, defaults to current year)
	 * @return int: 52 or 53
	 * 2011-03-07 ms
	 */
	public function cweeks($year = null) {
		if ($year === null) {
			$year = date('Y');
		}
		return date('W', mktime(23, 59, 59, 12, 28, $year));
	}

	/**
	 * @param year (format xxxx, defaults to current year)
	 * @return bool $success
	 * 2012-02-17 ms
	 */
	public function isLeapYear($year) {
		if ($year % 4 != 0) {
			return false;
		}
		if ($year % 400 == 0) {
			return true;
		} 
		if ($year > 1582 && $year % 100 == 0) {
		  # if gregorian calendar (>1582), century not-divisible by 400 is not leap
			return false;
		}
		return true;
	}

	/**
	 * get the age bounds (min, max) as timestamp that would result in the given age(s)
	 * note: expects valid age (> 0 and < 120)
	 * @param $firstAge
	 * @param $secondAge (defaults to first one if not specified)
	 * @return array('min'=>$min, 'max'=>$max);
	 * 2011-07-06 ms
	 */
	public function ageBounds($firstAge, $secondAge = null, $returnAsString = false, $relativeTime = null) {
		if ($secondAge === null) {
			$secondAge = $firstAge;
		}
		//TODO: other relative time then today should work as well

		$max = mktime(23, 23, 59, date('m'), date('d'), date('Y')-$firstAge); // time()-($firstAge+1)*YEAR;
		$min = mktime(0, 0, 1, date('m'), date('d')+1, date('Y')-$secondAge-1); // time()+DAY-$secondAge*YEAR;

		if ($returnAsString) {
			$max = date(FORMAT_DB_DATE, $max);
			$min = date(FORMAT_DB_DATE, $min);
		}
		return array('min'=>$min, 'max'=>$max);
	}


	/**
	 * for birthdays etc
	 * @param date
	 * @param string days with +-
	 * @param options
	 * 2011-03-03 ms
	 */
	public function isInRange($dateString, $seconds, $options = array()) {
		//$newDate = is_int($dateString) ? $dateString : strtotime($dateString);
		//$newDate += $seconds;
		$newDate = time();
		return $this->difference($dateString, $newDate) <= $seconds;
	}

	/**
	 * outputs Date(time) Sting nicely formatted (+ localized!)
	 * @param string $dateString,
	 * @param string $format (YYYY-MM-DD, DD.MM.YYYY)
 	 * @param array $options
		* - userOffset: User's offset from GMT (in hours)
		* - default (defaults to "-----")
 	 * 2009-03-31 ms
	 */
	public function localDate($dateString = null, $format = null, $options = array()) {
		$defaults = array('default'=>'-----', 'userOffset'=>$this->userOffset);
		$options = array_merge($defaults, $options);

		$date = null;
		if ($dateString !== null) {
			$date = $this->fromString($dateString, $options['userOffset']);
		}
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
		return strftime($format, $date);
	}


	/**
	 * outputs Date(time) Sting nicely formatted
	 * @param string $dateString,
	 * @param string $format (YYYY-MM-DD, DD.MM.YYYY)
 	 * @param array $options
		* - userOffset: User's offset from GMT (in hours)
		* - default (defaults to "-----")
 	 * 2009-03-31 ms
	 */
	public function niceDate($dateString = null, $format = null, $options = array()) {
		$defaults = array('default'=>'-----', 'userOffset'=>$this->userOffset);
		$options = array_merge($defaults, $options);

		$date = null;
		if ($dateString !== null) {
			$date = $this->fromString($dateString, $options['userOffset']);
		}
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
					$ret .= ' '.__('o\'clock');
					break;
			}
		}

		return $ret;
	}
	
	/**
	 * return the translation to a specific week day
	 * @param int $day:
	 * 0=sunday to 7=saturday (default numbers)
	 * @param bool $abbr (if abbreviation should be returned)
	 * @param offset: 0-6 (defaults to 0) [1 => 1=monday to 7=sunday]
	 * @return string $translatedText
	 * 2011-12-07 ms
	 */
	public function day($day, $abbr = false, $offset = 0) {
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
		pr($day);
		if ($offset) {
			$day = ($day + $offset) % 7; 
		}
		pr($day);
		if ($abbr) {
			return __($days['short'][$day]);
		}
		return __($days['long'][$day]);
	}
	
	/**
	 * return the translation to a specific week day
	 * @param int $month:
	 * 1..12
	 * @param bool $abbr (if abbreviation should be returned)
	 * @param array $options
	 * - appendDot (only for 3 letter abbr; defaults to false)
	 * @return string $translatedText
	 * 2011-12-07 ms
	 */
	public function month($month, $abbr = false, $options = array()) {
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
	 * @return array (for forms etc)
	 */
	public function months($monthKeys = array(), $options = array()) {
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
	 * weekdays
	 * @return array (for forms etc)
	 */
	public function days($dayKeys = array(), $options = array()) {
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
	 * can convert time from one unit to another
	 * @param int INT | time
	 * @param from CHAR
	 * @param to CHAR
	 * @param options: acc=>INT [accuracy], showZero=>BOOL, returnArray=>BOOL
	 * 2008-11-06 ms
	 */
	public function convertTime($int, $from, $to, $options = array()) {
		$accuracy = 0;	# 0 = only the "to"-element, 1..n = higher accurancy
		$showZero = false;	# show only the non-zero elements
		$returnArray = false;	# return as array instead of as string
		if (!empty($options)) {
			if (isset($options['acc'])) {
				$accuracy = (int)$options['acc'];
			}
			if (isset($options['showZero'])) {
				$showZero = (int)$options['showZero'];
			}
			if (isset($options['returnArray'])) {
				$return = ($options['returnArray']===true?true:false);
			}
		}

		$times = array(
			's'=>'0',
			'm'=>'1',
			'h'=>'2',
			'd'=>'3',
			'w'=>'4',
			'm'=>'5',
			'y'=>'6',
		);
		$options = array(
			'0'=>array(
				'steps'=>array('1'=>60,'2'=>3600,'3'=>86400,'4'=>86400*7,'5'=>86400*30,'6'=>86400*365),
				'down'=>0,
				'up'=>60,
				'short'=>'s',
				'long'=>'seconds'
			),
			'1'=>array(
				'steps'=>array('0'=>60,'2'=>60,'3'=>60*24,'4'=>60*24*7,'5'=>60*24*30,'6'=>60*24*365),
				'down'=>60,
				'up'=>60,
				'short'=>'m',
				'long'=>'minutes'
			),
			'2'=>array(
				'steps'=>array('0'=>3600,'1'=>60,'3'=>24,'4'=>24*7,'5'=>24*30,'6'=>24*365),
				'down'=>60,
				'up'=>24,
				'short'=>'h',
				'long'=>'hours'
			),
			'3'=>array(
				'steps'=>array('0'=>86400,'1'=>3600,'2'=>24,'4'=>7,'5'=>30,'6'=>365),
				'down'=>24,
				'up'=>7,
				'short'=>'d',
				'long'=>'days'
			),
			'4'=>array(
				'steps'=>array('0'=>86400*7,'1'=>60*24*7,'2'=>24*7,'3'=>7,'5'=>4.2,'6'=>52),
				'down'=>7,
				'up'=>4.2,
				'short'=>'w',
				'long'=>'weeks'
			),
			'5'=>array(
				'steps'=>array('0'=>86400*30,'1'=>60*24*30,'2'=>24*30,'3'=>30,'4'=>4.2,'6'=>12),
				'down'=>4.2,
				'up'=>12,
				'short'=>'m',
				'long'=>'months'
			),
			'6'=>array(
				'steps'=>array('0'=>86400*365,'1'=>60*24*365,'2'=>24*365,'3'=>365,'4'=>52,'5'=>12),
				'down'=>12,
				'up'=>0,
				'short'=>'y',
				'long'=>'years'
			),
		);

		//echo $options[0]['steps']['4'];

		if (array_key_exists($from,$times) && array_key_exists($to,$times)) {
			$begin = $times[$from];
			$end = $times[$to];
			//echo $begin-$end.BR;
		}

		$minuten = $int;
		if ($minuten<60) {
			return $minuten.'min';
		}

		$calculated = floor($minuten/60)."h ".($minuten%60)."min";



		if ($returnArray) {
			// return as array
		} else {
			// convert to the desired string
		}

		return $calculated;
	}



	/**
	 * @deprecated
	 * NICHT TESTEN!
	 */
	public function otherOne() {	
		$day = floor($anz_sekunden/86400);
		$hours = floor(($anz_sekunden-(floor($anz_sekunden/86400)*86400))/3600);
		$minutes = floor(($anz_sekunden-(floor($anz_sekunden/3600)*3600))/60);
		$seconds = floor($anz_sekunden-(floor($anz_sekunden/60))*60);
	
		if ($day < 10) {
			$day = '0'.$day;
		}
		if ($hours < 10) {
			$hours = '0'.$hours;
		}
		if ($minutes < 10) {
			$minutes = '0'.$minutes;
		}
		if ($seconds < 10) {
			$seconds = '0'.$seconds;
		}
	
		if ($day > 0) {
			$zeit_ausgabe = $day.":".$hours.":".$minutes.":".$seconds;
		} else {
			$zeit_ausgabe = $hours.":".$minutes.":".$seconds." h";
		}

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
	 * @param   integer  "remote" timestamp
	 * @return  string
	 */
	public function fuzzy($timestamp) {
		// Determine the difference in seconds
		$offset = abs(time() - $timestamp);

		return $this->fuzzyFromOffset($offset, $timestamp <= time());
	}

	/**
	 * @param int $offset in seconds
	 * @param boolean $past (defaults to null: return plain text)
	 * - new: if not boolean but a string use this as translating text
	 * @return string $text (i18n!)
	 * 2011-03-06 ms
	 */
	public function fuzzyFromOffset($offset, $past = null) {
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
		} elseif ($past === false) {
			// This in the future
			return __('in %s', __($span));
		} elseif ($past !== null) {
			// Custom translation
			return __($past, __($span));
		}
		return __($span);
	}


	/**
	 * time length to human readable format
	 * @param int $seconds
	 * @param string format: format
	 * @param options
	 * - boolean v: verbose
	 * - boolean zero: if false: 0 days 5 hours => 5 hours etc.
	 * - int: accuracy (how many sub-formats displayed?) //TODO
	 * 2009-11-21 ms
	 * @see timeAgoInWords()
	 */
	public function lengthOfTime($seconds, $format = null, $options = array()) {
		$defaults = array('verbose'=>true, 'zero'=>false, 'separator'=>', ', 'default'=>'');
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
		'm' => ' '.__('Month'), # translated
				'd' => ' '.__('Day'),
				'h' => ' '.__('Hour'),
				'i' => ' '.__('Minute'),
				's' => ' '.__('Second'),
			);
			$p = array (
		'm' => ' '.__('Months'), # translated
				'd' => ' '.__('Days'),
				'h' => ' '.__('Hours'),
				'i' => ' '.__('Minutes'),
				's' => ' '.__('Seconds'),
			);
		}

		if (!isset($format)) {
			//if (floor($seconds / MONTH) > 0) $format = "Md";
			if (floor($seconds / DAY) > 0) $format = "Dh";
			elseif (floor($seconds / 3600) > 0) $format = "Hi";
			elseif (floor($seconds / 60) > 0) $format = "Is";
			else $format = "S";
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
				return "";
				break;
			}

			if ($str > 0 || $j > 0 || $options['zero'] || $i == mb_strlen($format) - 1) {
				if ($j>0) {
					$ret .= $options['separator'];
				}

				$j++;

				$x = mb_strtolower(mb_substr($format, $i, 1));

				if ($str == 1) {
					$ret .= $str . $s[$x];
				} else {
					$title = $p[$x];
					if (!empty($options['plural'])) {
						if (mb_substr($title, -1, 1) == 'e') {
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
	 * time relative to NOW in human readable format - absolute (negative as well as positive)
	 * //TODO: make "now" adjustable
	 * @param mixed $datestring
	 * @param string format: format
	 * @param options
	 * - default, separator
	 * - boolean zero: if false: 0 days 5 hours => 5 hours etc.
	 * - verbose/past/future: string with %s or boolean true/false
	 * 2009-11-21 ms
	 */
	public function relLengthOfTime($dateString, $format = null, $options = array()) {
		if ($dateString != null) {
			$userOffset = null;
			$sec = time() - $this->fromString($dateString, $userOffset);
			$type = ($sec > 0)?-1:(($sec < 0)?1:0);
			$sec = abs($sec);
		} else {
			$sec = 0;
			$type = 0;
		}

		$defaults = array('verbose'=>__('justNow'), 'zero'=>false,'separator'=>', ', 'future'=>__('In %s'), 'past'=>__('%s ago'),'default'=>'');
		$options = array_merge($defaults, $options);

		$ret = $this->lengthOfTime($sec, $format, $options);

		if ($type == 1) {
			if ($options['future'] !== false) {
				return sprintf($options['future'], $ret);
			}
			return array('future'=>$ret);
		} elseif ($type == -1) {
			if ($options['past'] !== false) {
				return sprintf($options['past'], $ret);
			}
			return array('past'=>$ret);
		} else {
			if ($options['verbose'] !== false) {
				return $options['verbose'];
			}
			//return array('now'=>true);
		}
		return $options['default'];
	}


/**
 * Returns true if given datetime string was day before yesterday.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string was day before yesterday
 */
	public function wasDayBeforeYesterday($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date(FORMAT_DB_DATE, $date) == date(FORMAT_DB_DATE, time()-2*DAY);
	}

/**
 * Returns true if given datetime string is the day after tomorrow.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string is day after tomorrow
 */
	public function isDayAfterTomorrow($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date(FORMAT_DB_DATE, $date) == date(FORMAT_DB_DATE, time()+2*DAY);
	}

/**
 * Returns true if given datetime string is not today AND is in the future.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime is not today AND is in the future
 */
	public function isNotTodayAndInTheFuture($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date(FORMAT_DB_DATE, $date) > date(FORMAT_DB_DATE, time());
	}


/**
 * Returns true if given datetime string is not now AND is in the future.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime is not today AND is in the future
 */
	public function isInTheFuture($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date(FORMAT_DB_DATETIME, $date) > date(FORMAT_DB_DATETIME, time());
	}



/** CORE TIME HELPER **/

/**
 * Converts a string representing the format for the function strftime and returns a
 * windows safe and i18n aware format.
 *
 * @param string $format Format with specifiers for strftime function.
 *    Accepts the special specifier %S which mimics th modifier S for date()
 * @param string UNIX timestamp
 * @return string windows safe and date() function compatible format for strftime
 * @access public
 */
	public function convertSpecifiers($format, $time = null) {
		if (!$time) {
			$time = time();
		}
		$this->__time = $time;
		return preg_replace_callback('/\%(\w+)/', array($this, '__translateSpecifier'), $format);
	}

/**
 * Auxiliary function to translate a matched specifier element from a regular expresion into
 * a windows safe and i18n aware specifier
 *
 * @param array $specifier match from regular expression
 * @return string converted element
 * @access private
 */
	public function __translateSpecifier($specifier) {
		switch ($specifier[1]) {
			case 'a':
				$abday = __c('abday', 5);
				if (is_array($abday)) {
					return $abday[date('w', $this->__time)];
				}
				break;
			case 'A':
				$day = __c('day',5);
				if (is_array($day)) {
					return $day[date('w', $this->__time)];
				}
				break;
			case 'c':
				$format = __c('d_t_fmt',5);
				if ($format != 'd_t_fmt') {
					return $this->convertSpecifiers($format, $this->__time);
				}
				break;
			case 'C':
				return sprintf("%02d", date('Y', $this->__time) / 100);
			case 'D':
				return '%m/%d/%y';
			case 'e':
				if (DS === '/') {
					return '%e';
				}
				$day = date('j', $this->__time);
				if ($day < 10) {
					$day = ' ' . $day;
				}
				return $day;
			case 'eS' :
				return date('jS', $this->__time);
			case 'b':
			case 'h':
				$months = __c('abmon', 5);
				if (is_array($months)) {
					return $months[date('n', $this->__time) -1];
				}
				return '%b';
			case 'B':
				$months = __c('mon',5);
				if (is_array($months)) {
					return $months[date('n', $this->__time) -1];
				}
				break;
			case 'n':
				return "\n";
			case 'p':
			case 'P':
				$default = array('am' => 0, 'pm' => 1);
				$meridiem = $default[date('a',$this->__time)];
				$format = __c('am_pm', 5);
				if (is_array($format)) {
					$meridiem = $format[$meridiem];
					return ($specifier[1] == 'P') ? strtolower($meridiem) : strtoupper($meridiem);
				}
				break;
			case 'r':
				$complete = __c('t_fmt_ampm', 5);
				if ($complete != 't_fmt_ampm') {
					return str_replace('%p',$this->__translateSpecifier(array('%p', 'p')),$complete);
				}
				break;
			case 'R':
				return date('H:i', $this->__time);
			case 't':
				return "\t";
			case 'T':
				return '%H:%M:%S';
			case 'u':
				return ($weekDay = date('w', $this->__time)) ? $weekDay : 7;
			case 'x':
				$format = __c('d_fmt', 5);
				if ($format != 'd_fmt') {
					return $this->convertSpecifiers($format, $this->__time);
				}
				break;
			case 'X':
				$format = __c('t_fmt',5);
				if ($format != 't_fmt') {
					return $this->convertSpecifiers($format, $this->__time);
				}
				break;
		}
		return $specifier[0];
	}

/**
 * Converts given time (in server's time zone) to user's local time, given his/her offset from GMT.
 *
 * @param string $serverTime UNIX timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string UNIX timestamp
 * @access public
 */
	public function convert($serverTime, $userOffset) {
		$serverOffset = $this->serverOffset();
		$gmtTime = $serverTime - $serverOffset;
		$userTime = $gmtTime + $userOffset * (60*60);
		return $userTime;
	}

/**
 * Returns server's offset from GMT in seconds.
 *
 * @return int Offset
 * @access public
 */
	public function serverOffset() {
		return date('Z', time());
	}

/**
 * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
 *
 * @param string $dateString Datetime string
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Parsed timestamp
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function fromString($dateString, $userOffset = null) {
		if (empty($dateString)) {
			return false;
		}
		if (is_integer($dateString) || is_numeric($dateString)) {
			$date = intval($dateString);
		} else {
			$date = strtotime($dateString);
		}
		if ($userOffset !== null) {
			return $this->convert($date, $userOffset);
		}
		if ($date === -1) {
			return false;
		}
		return $date;
	}

/**
 * Returns a nicely formatted date string for given Datetime string.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Formatted date string
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function nice($dateString = null, $userOffset = null) {
		if ($dateString != null) {
			$date = $this->fromString($dateString, $userOffset);
		} else {
			$date = time();
		}
		$format = $this->convertSpecifiers('%a, %b %eS %Y, %H:%M', $date);
		return strftime($format, $date);
	}

/**
 * Returns a formatted descriptive date string for given datetime string.
 *
 * If the given date is today, the returned string could be "Today, 16:54".
 * If the given date was yesterday, the returned string could be "Yesterday, 16:54".
 * If $dateString's year is the current year, the returned string does not
 * include mention of the year.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Described, relative date string
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function niceShort($dateString = null, $userOffset = null) {
		$date = $dateString ? $this->fromString($dateString, $userOffset) : time();

		$y = $this->isThisYear($date) ? '' : ' %Y';

		if ($this->isToday($dateString, $userOffset)) {
			$ret = __('Today, %s', strftime("%H:%M", $date));
		} elseif ($this->wasYesterday($dateString, $userOffset)) {
			$ret = __('Yesterday, %s', strftime("%H:%M", $date));
		} else {
			$format = $this->convertSpecifiers("%b %eS{$y}, %H:%M", $date);
			$ret = strftime($format, $date);
		}

		return $ret;
	}

/**
 * Returns a partial SQL string to search for all records between two dates.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param string $end Datetime string or Unix timestamp
 * @param string $fieldName Name of database field to compare with
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Partial SQL string.
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function daysAsSql($begin, $end, $fieldName, $userOffset = null) {
		$begin = $this->fromString($begin, $userOffset);
		$end = $this->fromString($end, $userOffset);
		$begin = date('Y-m-d', $begin) . ' 00:00:00';
		$end = date('Y-m-d', $end) . ' 23:59:59';

		return "($fieldName >= '$begin') AND ($fieldName <= '$end')";
	}

/**
 * Returns a partial SQL string to search for all records between two times
 * occurring on the same day.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param string $fieldName Name of database field to compare with
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Partial SQL string.
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function dayAsSql($dateString, $fieldName, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return $this->daysAsSql($dateString, $dateString, $fieldName);
	}

	/**
	 * try to parse date from various input formats
	 * - DD.MM.YYYY, DD/MM/YYYY, YYYY-MM-DD, YYYY, YYYY-MM, ... 
	 * - i18n: Today, Yesterday, Tomorrow
	 * @param string $date to parse
	 * @param format to parse (null = auto)
	 * @param type
	 * - start: first second of this interval
	 * - end: last second of this interval
	 * @return int timestamp
	 * 2011-11-19 ms
	 */
	public function parseDate($date, $format = null, $type = 'start') {
		$date = trim($date);
		$i18n = array(
			strtolower(__('Today')) => array('start'=>date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))), 'end'=>date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y')))),
			strtolower(__('Tomorrow')) => array('start'=>date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))+DAY), 'end'=>date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y'))+DAY)),
			strtolower(__('Yesterday')) => array('start'=>date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))-DAY), 'end'=>date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y'))-DAY)), 
			strtolower(__('The day after tomorrow')) => array('start'=>date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))+2*DAY), 'end'=>date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y'))*2*DAY)),
			strtolower(__('The day before yesterday')) => array('start'=>date(FORMAT_DB_DATETIME, mktime(0, 0, 0, date('m'), date('d'), date('Y'))-2*DAY), 'end'=>date(FORMAT_DB_DATETIME, mktime(23, 59, 59, date('m'), date('d'), date('Y'))-2*DAY)),
		);
		if (isset($i18n[strtolower($date)])) {
			return $i18n[strtolower($date)][$type];
		}
		
		if ($format) {
			$res = DateTime::createFromFormat($format, $date);
			$res = $res->format(FORMAT_DB_DATE).' '.($type=='end'?'23:59:59':'00:00:00');
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
				return implode('-', $explode).' '.($type=='end'?'23:59:59':'00:00:00');
			} elseif (count($explode) === 2) {
				return implode('-', $explode).'-'.($type=='end'?$this->daysInMonth($explode[0], $explode[1]):'01').' '.($type=='end'?'23:59:59':'00:00:00');
			} else {
				return $explode[0].'-'.($type=='end'?'12':'01').'-'.($type=='end'?'31':'01').' '.($type=='end'?'23:59:59':'00:00:00');
			}
		}

		return false;
	}

	/**
	 * @param string $searchString to parse
	 * @param array $options
	 * - separator (defaults to space [ ])
	 * - format (defaults to Y-m-d H:i:s)
	 * @return array $period [0=>min, 1=>max]
	 * 2011-11-18 ms
	 */
	public function period($string, $options = array()) {
		if (strpos($string, ' ') !== false) {
			$filters = explode(' ', $string);
			$filters = array(array_shift($filters), array_pop($filters));
		} else {
			$filters = array($string, $string);
		}
		$min = $filters[0]; 
		$max = $filters[1];

		//$x = preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date, $date_parts);
		
		//$x = Datetime::createFromFormat('Y-m-d', $string);
		//die(returns($x));
		
		//$actualDateTime = new DateTime($min);
		//$actualDateTime->add(new DateInterval('P1M'));
		
		$min = $this->parseDate($min);
		$max = $this->parseDate($max, null, 'end');
		
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
	 * @param string $searchString to parse
	 * @param string $fieldname (Model.field)
	 * @param array $options (see DatetimeLib::period)
	 * @return string $query SQL Query
	 * 2011-11-18 ms
	 */
	public function periodAsSql($string, $fieldName, $options = array()) {
		$period = $this->period($string, $options);
		return $this->daysAsSql($period[0], $period[1], $fieldName);
	}
	

/**
 * Returns true if given datetime string is today.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string is today
 * @access public
 */
	public function isToday($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date('Y-m-d', $date) == date('Y-m-d', time());
	}

/**
 * Returns true if given datetime string is within this week
 * @param string $dateString
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string is within current week
 * @access public
 * @link http://book.cakephp.org/view/1472/Testing-Time
 */
	public function isThisWeek($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date('W Y', $date) == date('W Y', time());
	}

/**
 * Returns true if given datetime string is within this month
 * @param string $dateString
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string is within current month
 * @access public
 * @link http://book.cakephp.org/view/1472/Testing-Time
 */
	public function isThisMonth($dateString, $userOffset = null) {
		$date = $this->fromString($dateString);
		return date('m Y',$date) == date('m Y', time());
	}

/**
 * Returns true if given datetime string is within current year.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @return boolean True if datetime string is within current year
 * @access public
 * @link http://book.cakephp.org/view/1472/Testing-Time
 */
	public function isThisYear($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return  date('Y', $date) == date('Y', time());
	}

/**
 * Returns true if given datetime string was yesterday.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string was yesterday
 * @access public
 * @link http://book.cakephp.org/view/1472/Testing-Time
 *
 */
	public function wasYesterday($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date('Y-m-d', $date) == date('Y-m-d', strtotime('yesterday'));
	}

/**
 * Returns true if given datetime string is tomorrow.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string was yesterday
 * @access public
 * @link http://book.cakephp.org/view/1472/Testing-Time
 */
	public function isTomorrow($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date('Y-m-d', $date) == date('Y-m-d', strtotime('tomorrow'));
	}

/**
 * Returns the quarter
 *
 * @param string $dateString
 * @param boolean $range if true returns a range in Y-m-d format
 * @return boolean True if datetime string is within current week
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function toQuarter($dateString, $range = false) {
		$time = $this->fromString($dateString);
		$date = ceil(date('m', $time) / 3);

		if ($range === true) {
			$range = 'Y-m-d';
		}

		if ($range !== false) {
			$year = date('Y', $time);

			switch ($date) {
				case 1:
					$date = array($year.'-01-01', $year.'-03-31');
					break;
				case 2:
					$date = array($year.'-04-01', $year.'-06-30');
					break;
				case 3:
					$date = array($year.'-07-01', $year.'-09-30');
					break;
				case 4:
					$date = array($year.'-10-01', $year.'-12-31');
					break;
			}
		}
		return $date;
	}

/**
 * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP function strtotime().
 *
 * @param string $dateString Datetime string to be represented as a Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return integer Unix timestamp
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function toUnix($dateString, $userOffset = null) {
		return $this->fromString($dateString, $userOffset);
	}

/**
 * Returns a date formatted for Atom RSS feeds.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Formatted date string
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function toAtom($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date('Y-m-d\TH:i:s\Z', $date);
	}

/**
 * Formats date for RSS feeds
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Formatted date string
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function toRSS($dateString, $userOffset = null) {
		$date = $this->fromString($dateString, $userOffset);
		return date("r", $date);
	}

/**
 * Returns either a relative date or a formatted date depending
 * on the difference between the current time and given datetime.
 * $datetime should be in a <i>strtotime</i> - parsable format, like MySQL's datetime datatype.
 *
 * ### Options:
 *
 * - `format` => a fall back format if the relative time is longer than the duration specified by end
 * - `end` => The end of relative time telling
 * - `userOffset` => Users offset from GMT (in hours)
 *
 * Relative dates look something like this:
 *	3 weeks, 4 days ago
 *	15 seconds ago
 *
 * Default date formatting is d/m/yy e.g: on 18/2/09
 *
 * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
 * like 'Posted ' before the function output.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param array $options Default format if timestamp is used in $dateString
 * @return string Relative time string.
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function timeAgoInWords($dateTime, $options = array()) {
		$userOffset = null;
		if (is_array($options) && isset($options['userOffset'])) {
			$userOffset = $options['userOffset'];
		}
		$now = time();
		if (!is_null($userOffset)) {
			$now = $this->convert(time(), $userOffset);
		}
		$inSeconds = $this->fromString($dateTime, $userOffset);
		$backwards = ($inSeconds > $now);

		$format = 'j/n/y';
		$end = '+1 month';

		if (is_array($options)) {
			if (isset($options['format'])) {
				$format = $options['format'];
				unset($options['format']);
			}
			if (isset($options['end'])) {
				$end = $options['end'];
				unset($options['end']);
			}
		} else {
			$format = $options;
		}

		if ($backwards) {
			$futureTime = $inSeconds;
			$pastTime = $now;
		} else {
			$futureTime = $now;
			$pastTime = $inSeconds;
		}
		$diff = $futureTime - $pastTime;

		// If more than a week, then take into account the length of months
		if ($diff >= 604800) {
			$current = array();
			$date = array();

			list($future['H'], $future['i'], $future['s'], $future['d'], $future['m'], $future['Y']) = explode('/', date('H/i/s/d/m/Y', $futureTime));

			list($past['H'], $past['i'], $past['s'], $past['d'], $past['m'], $past['Y']) = explode('/', date('H/i/s/d/m/Y', $pastTime));
			$years = $months = $weeks = $days = $hours = $minutes = $seconds = 0;

			if ($future['Y'] == $past['Y'] && $future['m'] == $past['m']) {
				$months = 0;
				$years = 0;
			} else {
				if ($future['Y'] == $past['Y']) {
					$months = $future['m'] - $past['m'];
				} else {
					$years = $future['Y'] - $past['Y'];
					$months = $future['m'] + ((12 * $years) - $past['m']);

					if ($months >= 12) {
						$years = floor($months / 12);
						$months = $months - ($years * 12);
					}

					if ($future['m'] < $past['m'] && $future['Y'] - $past['Y'] == 1) {
						$years --;
					}
				}
			}

			if ($future['d'] >= $past['d']) {
				$days = $future['d'] - $past['d'];
			} else {
				$daysInPastMonth = date('t', $pastTime);
				$daysInFutureMonth = date('t', mktime(0, 0, 0, $future['m'] - 1, 1, $future['Y']));

				if (!$backwards) {
					$days = ($daysInPastMonth - $past['d']) + $future['d'];
				} else {
					$days = ($daysInFutureMonth - $past['d']) + $future['d'];
				}

				if ($future['m'] != $past['m']) {
					$months --;
				}
			}

			if ($months == 0 && $years >= 1 && $diff < ($years * 31536000)) {
				$months = 11;
				$years --;
			}

			if ($months >= 12) {
				$years = $years + 1;
				$months = $months - 12;
			}

			if ($days >= 7) {
				$weeks = floor($days / 7);
				$days = $days - ($weeks * 7);
			}
		} else {
			$years = $months = $weeks = 0;
			$days = floor($diff / 86400);

			$diff = $diff - ($days * 86400);

			$hours = floor($diff / 3600);
			$diff = $diff - ($hours * 3600);

			$minutes = floor($diff / 60);
			$diff = $diff - ($minutes * 60);
			$seconds = $diff;
		}
		$relativeDate = '';
		$diff = $futureTime - $pastTime;

		if ($diff > abs($now - $this->fromString($end))) {
			$relativeDate = __('on %s', date($format, $inSeconds));
		} else {
			if ($years > 0) {
				// years and months and days
				$relativeDate .= ($relativeDate ? ', ' : '') . $years . ' ' . __n('year', 'years', $years);
				$relativeDate .= $months > 0 ? ($relativeDate ? ', ' : '') . $months . ' ' . __n('month', 'months', $months) : '';
				$relativeDate .= $weeks > 0 ? ($relativeDate ? ', ' : '') . $weeks . ' ' . __n('week', 'weeks', $weeks) : '';
				$relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '') . $days . ' ' . __n('day', 'days', $days) : '';
			} elseif (abs($months) > 0) {
				// months, weeks and days
				$relativeDate .= ($relativeDate ? ', ' : '') . $months . ' ' . __n('month', 'months', $months);
				$relativeDate .= $weeks > 0 ? ($relativeDate ? ', ' : '') . $weeks . ' ' . __n('week', 'weeks', $weeks) : '';
				$relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '') . $days . ' ' . __n('day', 'days', $days) : '';
			} elseif (abs($weeks) > 0) {
				// weeks and days
				$relativeDate .= ($relativeDate ? ', ' : '') . $weeks . ' ' . __n('week', 'weeks', $weeks);
				$relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '') . $days . ' ' . __n('day', 'days', $days) : '';
			} elseif (abs($days) > 0) {
				// days and hours
				$relativeDate .= ($relativeDate ? ', ' : '') . $days . ' ' . __n('day', 'days', $days);
				$relativeDate .= $hours > 0 ? ($relativeDate ? ', ' : '') . $hours . ' ' . __n('hour', 'hours', $hours) : '';
			} elseif (abs($hours) > 0) {
				// hours and minutes
				$relativeDate .= ($relativeDate ? ', ' : '') . $hours . ' ' . __n('hour', 'hours', $hours);
				$relativeDate .= $minutes > 0 ? ($relativeDate ? ', ' : '') . $minutes . ' ' . __n('minute', 'minutes', $minutes) : '';
			} elseif (abs($minutes) > 0) {
				// minutes only
				$relativeDate .= ($relativeDate ? ', ' : '') . $minutes . ' ' . __n('minute', 'minutes', $minutes);
			} else {
				// seconds only
				$relativeDate .= ($relativeDate ? ', ' : '') . $seconds . ' ' . __n('second', 'seconds', $seconds);
			}

			if (!$backwards) {
				$relativeDate = __('%s ago', $relativeDate);
			}
		}
		return $relativeDate;
	}

/**
 * Alias for timeAgoInWords
 *
 * @param mixed $dateTime Datetime string (strtotime-compatible) or Unix timestamp
 * @param mixed $options Default format string, if timestamp is used in $dateTime, or an array of options to be passed
 *   on to timeAgoInWords().
 * @return string Relative time string.
 * @see TimeHelper::timeAgoInWords
 * @access public
 * @deprecated This method alias will be removed in future versions.
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function relativeTime($dateTime, $options = array()) {
		return $this->timeAgoInWords($dateTime, $options);
	}

/**
 * Returns true if specified datetime was within the interval specified, else false.
 *
 * @param mixed $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @param mixed $dateString the datestring or unix timestamp to compare
 * @param int $userOffset User's offset from GMT (in hours)
 * @return bool
 * @access public
 * @link http://book.cakephp.org/view/1472/Testing-Time
 */
	public function wasWithinLast($timeInterval, $dateString, $userOffset = null) {
		$tmp = str_replace(' ', '', $timeInterval);
		if (is_numeric($tmp)) {
			$timeInterval = $tmp . ' ' . __('days');
		}

		$date = $this->fromString($dateString, $userOffset);
		$interval = $this->fromString('-'.$timeInterval);

		if ($date >= $interval && $date <= time()) {
			return true;
		}

		return false;
	}

/**
 * Returns gmt, given either a UNIX timestamp or a valid strtotime() date string.
 *
 * @param string $dateString Datetime string
 * @return string Formatted date string
 * @access public
 * @link http://book.cakephp.org/view/1471/Formatting
 */
	public function gmt($string = null) {
		if ($string != null) {
			$string = $this->fromString($string);
		} else {
			$string = time();
		}
		$string = $this->fromString($string);
		$hour = intval(date("G", $string));
		$minute = intval(date("i", $string));
		$second = intval(date("s", $string));
		$month = intval(date("n", $string));
		$day = intval(date("j", $string));
		$year = intval(date("Y", $string));

		return gmmktime($hour, $minute, $second, $month, $day, $year);
	}

/**
 * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
 * This function also accepts a time string and a format string as first and second parameters.
 * In that case this function behaves as a wrapper for TimeHelper::i18nFormat()
 *
 * @param string $format date format string (or a DateTime string)
 * @param string $dateString Datetime string (or a date format string)
 * @param boolean $invalid flag to ignore results of fromString == false
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Formatted date string
 * @access public
 */
	public function format($format, $date = null, $invalid = false, $userOffset = null) {
		$time = $this->fromString($date, $userOffset);
		$_time = $this->fromString($format, $userOffset);

		if (is_numeric($_time) && $time === false) {
			$format = $date;
			return $this->i18nFormat($_time, $format, $invalid, $userOffset);
		}
		if ($time === false && $invalid !== false) {
			return $invalid;
		}
		return date($format, $time);
	}

/**
 * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
 * It take in account the default date format for the current language if a LC_TIME file is used.
 *
 * @param string $dateString Datetime string
 * @param string $format strftime format string.
 * @param boolean $invalid flag to ignore results of fromString == false
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Formatted and translated date string @access public
 * @access public
 */
	public function i18nFormat($date, $format = null, $invalid = false, $userOffset = null) {
		$date = $this->fromString($date, $userOffset);
		if ($date === false && $invalid !== false) {
			return $invalid;
		}
		if (empty($format)) {
			$format = '%x';
		}
		$format = $this->convertSpecifiers($format, $date);
		return strftime($format, $date);
	}

}

