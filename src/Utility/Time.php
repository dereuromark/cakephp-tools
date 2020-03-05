<?php

namespace Tools\Utility;

use Cake\Chronos\MutableDate;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\Time as CakeTime;
use DateInterval;
use DateTime;

/**
 * Extend CakeTime with a few important improvements:
 * - correct timezones for date only input and therefore unchanged day here
 */
class Time extends CakeTime {

	/**
	 * @param string|int|\DateTimeInterface|array|null $time Fixed or relative time
	 * @param \DateTimeZone|string|null $tz The timezone for the instance
	 */
	public function __construct($time = null, $tz = null) {
		if (is_array($time)) {
			$value = $time + ['hour' => 0, 'minute' => 0, 'second' => 0];

			$format = '';
			if (
				isset($value['year'], $value['month'], $value['day']) &&
				(is_numeric($value['year']) && is_numeric($value['month']) && is_numeric($value['day']))
			) {
				$format .= sprintf('%d-%02d-%02d', $value['year'], $value['month'], $value['day']);
			}

			if (isset($value['meridian'])) {
				$value['hour'] = strtolower($value['meridian']) === 'am' ? $value['hour'] : $value['hour'] + 12;
			}
			$format .= sprintf(
				'%s%02d:%02d:%02d',
				empty($format) ? '' : ' ',
				$value['hour'],
				$value['minute'],
				$value['second']
			);

			$time = $format;
		}

		parent::__construct($time, $tz);
	}

	/**
	 * Detect if a timezone has a DST
	 *
	 * @param string|\DateTimeZone|null $timezone User's timezone string or DateTimeZone object
	 * @return bool
	 */
	public function hasDaylightSavingTime($timezone = null) {
		$timezone = $this->safeCreateDateTimeZone($timezone);
		// a date outside of DST
		$offset = $timezone->getOffset(new CakeTime('@' . mktime(0, 0, 0, 2, 1, (int)date('Y'))));
		$offset = $offset / HOUR;

		// a date inside of DST
		$offset2 = $timezone->getOffset(new CakeTime('@' . mktime(0, 0, 0, 8, 1, (int)date('Y'))));
		$offset2 = $offset2 / HOUR;

		return abs($offset2 - $offset) > 0;
	}

	/**
	 * Calculate the difference between two dates
	 *
	 * TODO: deprecate in favor of DateTime::diff() etc which will be more precise
	 *
	 * should only be used for < month (due to the different month lenghts it gets fuzzy)
	 *
	 * @param mixed $startTime (db format or timestamp)
	 * @param mixed|null $endTime (db format or timestamp)
	 * @param array $options
	 * @return int The distance in seconds
	 */
	public static function difference($startTime, $endTime = null, array $options = []) {
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
	 * Calculates the age using start and optional end date.
	 * Both dates default to current date. Note that start needs
	 * to be before end for a valid result.
	 *
	 * @param int|string|\DateTimeInterface $start Start date (if empty, use today)
	 * @param int|string|\DateTimeInterface|null $end End date (if empty, use today)
	 * @return int Age (0 if both timestamps are equal or empty, -1 on invalid dates)
	 */
	public static function age($start, $end = null) {
		if (empty($start) && empty($end) || $start == $end) {
			return 0;
		}

		if (is_int($start)) {
			$start = date(FORMAT_DB_DATE, $start);
		}
		if (is_int($end)) {
			$end = date(FORMAT_DB_DATE, $end);
		}

		$startDate = $start;
		if (!is_object($start)) {
			$startDate = new CakeTime($start);
		}

		$endDate = $end;
		if (!is_object($end)) {
			$endDate = new CakeTime($end);
		}

		if ($startDate > $endDate) {
			return -1;
		}

		$oDateInterval = $endDate->diff($startDate);

		return $oDateInterval->y;
	}

	/**
	 * Returns the age only with the year available
	 * can be e.g. 22/23
	 *
	 * @param int $year
	 * @param int|null $month (optional)
	 * @return int|string Age
	 */
	public static function ageByYear($year, $month = null) {
		if ($month === null) {
			$maxAge = static::age(mktime(0, 0, 0, 1, 1, $year));
			$minAge = static::age(mktime(23, 59, 59, 12, 31, $year));
			$ages = array_unique([$minAge, $maxAge]);
			return implode('/', $ages);
		}
		if ((int)date('n') === $month) {
			$maxAge = static::age(mktime(0, 0, 0, $month, 1, $year));
			$minAge = static::age(mktime(23, 59, 59, $month, static::daysInMonth($year, $month), $year));

			$ages = array_unique([$minAge, $maxAge]);
			return implode('/', $ages);
		}

		return static::age(mktime(0, 0, 0, $month, 1, $year));
	}

	/**
	 * Rounded age depended on steps (e.g. age 16 with steps = 10 => "11-20")
	 * //FIXME
	 * //TODO: move to helper?
	 *
	 * @param int $year
	 * @param int|null $month
	 * @param int|null $day
	 * @param int $steps
	 * @return mixed
	 */
	public static function ageRange($year, $month = null, $day = null, $steps = 1) {
		if ($month == null && $day == null) {
			$age = (int)date('Y') - $year - 1;
		} elseif ($day == null) {
			if ($month >= (int)date('m')) {
				$age = (int)date('Y') - $year - 1;
			} else {
				$age = (int)date('Y') - $year;
			}
		} else {
			if ($month > (int)date('m') || ($month == (int)date('m') && $day > (int)date('d'))) {
				$age = (int)date('Y') - $year - 1;
			} else {
				$age = (int)date('Y') - $year;
			}
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
		return [$lowerRange, $upperRange];
	}

	/**
	 * Return the days of a given month.
	 *
	 * @param int $year
	 * @param int $month
	 * @return int Days
	 */
	public static function daysInMonth($year, $month) {
		return (int)date('t', mktime(0, 0, 0, $month, 1, $year));
	}

	/**
	 * Calendar Week (current week of the year).
	 * //TODO: use timestamp - or make the function able to use timestamps at least (besides dateString)
	 *
	 * date('W', $time) returns ISO6801 week number.
	 * Exception: Dates of the calender week of the previous year return 0. In this case the cweek of the
	 * last week of the previous year should be used.
	 *
	 * @param mixed|null $dateString In DB format - if none is passed, current day is used
	 * @param int $relative - weeks relative to the date (+1 next, -1 previous etc)
	 * @return string
	 */
	public static function cWeek($dateString = null, $relative = 0) {
		if ($dateString) {
			$date = explode(' ', $dateString);
			list($y, $m, $d) = explode('-', $date[0]);
			$t = mktime(0, 0, 0, (int)$m, (int)$d, (int)$y);
		} else {
			$y = date('Y');
			$t = time();
		}

		$relative = (int)$relative;
		if ($relative !== 0) {
			$t += WEEK * $relative;	// 1day * 7 * relativeWeeks
		}

		$kw = (int)date('W', $t);
		if ($kw === 0) {
			$kw = 1 + (int)date('W', $t - DAY * (int)date('w', $t));
			$y--;
		}

		return str_pad((string)$kw, 2, '0', STR_PAD_LEFT) . '/' . $y;
	}

	/**
	 * Get number of days since the start of the week.
	 * 0=sunday to 7=saturday (default)
	 *
	 * @param int $num Number of day.
	 * @return int Days since the start of the week.
	 */
	public static function cWeekMod($num) {
		$base = 6;
		return (int)($num - $base * floor($num / $base));
	}

	/**
	 * Calculate the beginning of a calender week
	 * if no calendar week is given get the beginning of the first week of the year
	 *
	 * @param int $year (format xxxx)
	 * @param int $cWeek (optional, defaults to first, range 1...52/53)
	 * @return int Timestamp
	 */
	public static function cWeekBeginning($year, $cWeek = 0) {
		if ($cWeek <= 1 || $cWeek > static::cWeeks($year)) {
			$first = mktime(0, 0, 0, 1, 1, $year);
			$wtag = (int)date('w', $first);

			if ($wtag <= 4) {
				/* Thursday or less: back to Monday */
				$firstmonday = mktime(0, 0, 0, 1, 1 - ($wtag - 1), $year);
			} elseif ($wtag != 1) {
				/* Back to Monday */
				$firstmonday = mktime(0, 0, 0, 1, 1 + (7 - $wtag + 1), $year);
			} else {
				$firstmonday = $first;
			}
			return $firstmonday;
		}
		$monday = strtotime($year . 'W' . static::pad((string)$cWeek) . '1');
		return $monday;
	}

	/**
	 * Calculate the ending of a calenderweek
	 * if no cweek is given get the ending of the last week of the year
	 *
	 * @param int $year (format xxxx)
	 * @param int $cWeek (optional, defaults to last, range 1...52/53)
	 * @return int Timestamp
	 */
	public static function cWeekEnding($year, $cWeek = 0) {
		if ($cWeek < 1 || $cWeek >= static::cWeeks($year)) {
			return static::cWeekBeginning($year + 1) - 1;
		}
		return static::cWeekBeginning($year, $cWeek + 1) - 1;
	}

	/**
	 * Calculate the amount of calender weeks in a year
	 *
	 * @param int|null $year (format xxxx, defaults to current year)
	 * @return int Amount of weeks - 52 or 53
	 */
	public static function cWeeks($year = null) {
		if ($year === null) {
			$year = date('Y');
		}
		return (int)date('W', mktime(23, 59, 59, 12, 28, $year));
	}

	/**
	 * Handles month/year increment calculations in a safe way, avoiding the pitfall of "fuzzy" month units.
	 *
	 * @param mixed $startDate Either a date string or a DateTime object
	 * @param int $years Years to increment/decrement
	 * @param int $months Months to increment/decrement
	 * @param int $days Days
	 * @param string|\DateTimeZone|int|null $timezone Timezone string or DateTimeZone object
	 * @return object DateTime with incremented/decremented month/year values.
	 */
	public function incrementDate($startDate, $years = 0, $months = 0, $days = 0, $timezone = null) {
		$dateTime = $startDate;
		if (!is_object($startDate)) {
			$dateTime = new CakeTime($startDate);
			if ($timezone) {
				$dateTime->setTimezone($this->safeCreateDateTimeZone($timezone));
			}
		}
		$startingTimeStamp = $dateTime->getTimestamp();
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
		$newDate = DateTime::createFromFormat('U', (string)$newTimeStamp);

		return $newDate;
	}

	/**
	 * Get the age bounds (min, max) as timestamp that would result in the given age(s)
	 * note: expects valid age (> 0 and < 120)
	 *
	 * @param int $firstAge
	 * @param int|null $secondAge (defaults to first one if not specified)
	 * @param bool $returnAsString
	 * @param string|null $relativeTime
	 * @return array Array('min'=>$min, 'max'=>$max);
	 */
	public static function ageBounds($firstAge, $secondAge = null, $returnAsString = false, $relativeTime = null) {
		if ($secondAge === null) {
			$secondAge = $firstAge;
		}
		//TODO: other relative time then today should work as well
		$date = new CakeTime($relativeTime !== null ? $relativeTime : 'now');

		$max = mktime(23, 23, 59, (int)$date->format('m'), (int)$date->format('d'), (int)$date->format('Y') - $firstAge);
		$min = mktime(0, 0, 1, (int)$date->format('m'), (int)$date->format('d') + 1, (int)$date->format('Y') - $secondAge - 1);

		if ($returnAsString) {
			$max = date(FORMAT_DB_DATE, $max);
			$min = date(FORMAT_DB_DATE, $min);
		}
		return ['min' => $min, 'max' => $max];
	}

	/**
	 * For birthdays etc
	 *
	 * @param string $dateString
	 * @param int $seconds
	 * @return bool Success
	 */
	public static function isInRange($dateString, $seconds) {
		$newDate = time();
		return static::difference($dateString, $newDate) <= $seconds;
	}

	/**
	 * Outputs Date(time) Sting nicely formatted (+ localized!)
	 *
	 * Options:
	 * - timezone: User's timezone
	 * - default: Default string (defaults to "-----")
	 * - oclock: Set to true to append oclock string
	 *
	 * @param string|null $dateString
	 * @param string|null $format Format (YYYY-MM-DD, DD.MM.YYYY)
	 * @param array $options
	 * @return string
	 */
	public static function localDate($dateString, $format = null, array $options = []) {
		$defaults = ['default' => '-----', 'timezone' => null];
		$options += $defaults;

		if ($options['timezone'] === null && strlen($dateString) === 10) {
			$options['timezone'] = static::_getDefaultOutputTimezone();
		}
		if ($dateString === null) {
			$dateString = time();
		}
		if ($options['timezone']) {
			$options['timezone'] = static::safeCreateDateTimeZone($options['timezone']);
		}
		$date = new CakeTime($dateString, $options['timezone']);
		$date = $date->format('U');

		if ($date <= 0) {
			return $options['default'];
		}

		if ($format === null) {
			if (is_int($dateString) || strpos($dateString, ' ') !== false) {
				$format = FORMAT_LOCAL_YMDHM;
			} else {
				$format = FORMAT_LOCAL_YMD;
			}
		}

		$date = static::_strftime($format, (int)$date);

		if (!empty($options['oclock'])) {
			switch ($format) {
				case FORMAT_LOCAL_YMDHM:
				case FORMAT_LOCAL_YMDHMS:
				case FORMAT_LOCAL_HM:
				case FORMAT_LOCAL_HMS:
					$date .= ' ' . __d('tools', 'o\'clock');
					break;
			}
		}
		return $date;
	}

	/**
	 * Multibyte wrapper for strftime.
	 *
	 * Handles utf8_encoding the result of strftime when necessary.
	 *
	 * @param string $format Format string.
	 * @param int $date Timestamp to format.
	 * @return string formatted string with correct encoding.
	 */
	protected static function _strftime($format, $date) {
		$format = strftime($format, $date);
		$encoding = Configure::read('App.encoding');

		if (!empty($encoding) && $encoding === 'UTF-8') {
			$valid = mb_check_encoding($format, $encoding);
			if (!$valid) {
				$format = utf8_encode($format);
			}
		}
		return $format;
	}

	/**
	 * Outputs Date(time) Sting nicely formatted
	 *
	 * Options:
	 * - timezone: User's timezone
	 * - default: Default string (defaults to "-----")
	 * - oclock: Set to true to append oclock string
	 *
	 * @param string|\Cake\I18n\I18nDateTimeInterface|null $dateString
	 * @param string|null $format Format (YYYY-MM-DD, DD.MM.YYYY)
	 * @param array $options Options
	 * @return string
	 */
	public static function niceDate($dateString, $format = null, array $options = []) {
		$defaults = ['default' => '-----', 'timezone' => null];
		$options += $defaults;

		if ($options['timezone'] === null) {
			$options['timezone'] = static::_getDefaultOutputTimezone();
		}

		if ($options['timezone']) {
			$options['timezone'] = static::safeCreateDateTimeZone($options['timezone']);
		}

		if ($dateString === null) {
			return $options['default'];
		}

		if (!is_object($dateString)) {
			if (strlen($dateString) === 10) {
				$date = new Date($dateString);
			} else {
				$date = new CakeTime($dateString);
			}
		} else {
			$date = $dateString;
		}

		if ($format === null) {
			if ($date instanceof MutableDate) {
				$format = FORMAT_NICE_YMD;
			} else {
				$format = FORMAT_NICE_YMDHM;
			}
		}

		$date = $date->timezone($options['timezone']);
		$ret = $date->format($format);

		if (!empty($options['oclock'])) {
			switch ($format) {
				case FORMAT_NICE_YMDHM:
				case FORMAT_NICE_YMDHMS:
				case FORMAT_NICE_HM:
				case FORMAT_NICE_HMS:
					$ret .= ' ' . __d('tools', 'o\'clock');
					break;
			}
		}

		return $ret;
	}

	/**
	 * Takes time as hh:mm:ss or YYYY-MM-DD hh:mm:ss
	 *
	 * @param string $time
	 * @return string Time in format hh:mm
	 */
	public static function niceTime($time) {
		if (($pos = strpos($time, ' ')) !== false) {
			$time = substr($time, $pos + 1);
		}
		return substr($time, 0, 5);
	}

	/**
	 * @return string
	 */
	protected static function _getDefaultOutputTimezone() {
		return Configure::read('App.defaultOutputTimezone') ?: date_default_timezone_get();
	}

	/**
	 * Return the translation to a specific week day
	 *
	 * @param int $day
	 * 0=sunday to 7=saturday (default numbers)
	 * @param bool $abbr (if abbreviation should be returned)
	 * @param int $offset int 0-6 (defaults to 0) [1 => 1=monday to 7=sunday]
	 * @return string translatedText
	 */
	public static function dayName($day, $abbr = false, $offset = 0) {
		$days = [
			'long' => [
				'Sunday',
				'Monday',
				'Tuesday',
				'Wednesday',
				'Thursday',
				'Friday',
				'Saturday',
			],
			'short' => [
				'Sun',
				'Mon',
				'Tue',
				'Wed',
				'Thu',
				'Fri',
				'Sat',
			],
		];
		$day = (int)$day;
		if ($offset) {
			$day = ($day + $offset) % 7;
		}
		if ($abbr) {
			return __d('tools', $days['short'][$day]);
		}
		return __d('tools', $days['long'][$day]);
	}

	/**
	 * Return the translation to a specific week day
	 *
	 * @param int $month
	 * 1..12
	 * @param bool $abbr (if abbreviation should be returned)
	 * @param array $options
	 * - appendDot (only for 3 letter abbr; defaults to false)
	 * @return string translatedText
	 */
	public static function monthName($month, $abbr = false, array $options = []) {
		$months = [
			'long' => [
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
				'December',
			],
			'short' => [
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
				'Dec',
			],
		];
		$month = (int)($month - 1);
		if (!$abbr) {
			return __d('tools', $months['long'][$month]);
		}
		$monthName = __d('tools', $months['short'][$month]);
		if (!empty($options['appendDot']) && strlen(__d('tools', $months['long'][$month])) > 3) {
			$monthName .= '.';
		}
		return $monthName;
	}

	/**
	 * Months
	 *
	 * Options:
	 * - abbr
	 *
	 * @param int[] $monthKeys
	 * @param array $options
	 * @return string[]
	 */
	public static function monthNames(array $monthKeys = [], array $options = []) {
		if (!$monthKeys) {
			$monthKeys = range(1, 12);
		}
		$res = [];
		$abbr = isset($options['abbr']) ? $options['abbr'] : false;
		foreach ($monthKeys as $key) {
			$res[static::pad((string)$key)] = static::monthName($key, $abbr, $options);
		}
		return $res;
	}

	/**
	 * Weekdays
	 *
	 * @param int[] $dayKeys
	 * @param array $options
	 * @return string[]
	 */
	public static function dayNames(array $dayKeys = [], array $options = []) {
		if (!$dayKeys) {
			$dayKeys = range(0, 6);
		}
		$res = [];
		$abbr = isset($options['abbr']) ? $options['abbr'] : false;
		$offset = isset($options['offset']) ? $options['offset'] : 0;
		foreach ($dayKeys as $key) {
			$res[$key] = static::dayName($key, $abbr, $offset);
		}
		return $res;
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
	 * @param int $timestamp "remote" timestamp
	 * @return string
	 */
	public static function fuzzy($timestamp) {
		// Determine the difference in seconds
		$offset = abs(time() - $timestamp);

		return static::fuzzyFromOffset($offset, $timestamp <= time());
	}

	/**
	 * @param int $offset in seconds
	 * @param bool|string|null $past (defaults to null: return plain text)
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
			return __d('tools', '%s ago', __d('tools', $span));
		}
		if ($past === false) {
			// This in the future
			return __d('tools', 'in %s', __d('tools', $span));
		}
		if ($past !== null) {
			// Custom translation
			return __d('tools', $past, __d('tools', $span));
		}
		return __d('tools', $span);
	}

	/**
	 * Time length to human readable format.
	 *
	 * @param int $seconds
	 * @param string|null $format
	 * @param array $options
	 * - boolean v: verbose
	 * - boolean zero: if false: 0 days 5 hours => 5 hours etc.
	 * - int: accuracy (how many sub-formats displayed?) //TODO
	 * @return string
	 * @see timeAgoInWords()
	 */
	public static function lengthOfTime($seconds, $format = null, array $options = []) {
		$defaults = ['verbose' => true, 'zero' => false, 'separator' => ', ', 'default' => ''];
		$options += $defaults;

		if (!$options['verbose']) {
			$s = [
				'm' => 'mth',
				'd' => 'd',
				'h' => 'h',
				'i' => 'm',
				's' => 's',
			];
			$p = $s;
		} else {
			$s = [
		'm' => ' ' . __d('tools', 'Month'), # translated
				'd' => ' ' . __d('tools', 'Day'),
				'h' => ' ' . __d('tools', 'Hour'),
				'i' => ' ' . __d('tools', 'Minute'),
				's' => ' ' . __d('tools', 'Second'),
			];
			$p = [
		'm' => ' ' . __d('tools', 'Months'), # translated
				'd' => ' ' . __d('tools', 'Days'),
				'h' => ' ' . __d('tools', 'Hours'),
				'i' => ' ' . __d('tools', 'Minutes'),
				's' => ' ' . __d('tools', 'Seconds'),
			];
		}

		if (!isset($format)) {
			if (floor($seconds / DAY) > 0) {
				$format = 'Dh';
			} elseif (floor($seconds / 3600) > 0) {
				$format = 'Hi';
			} elseif (floor($seconds / 60) > 0) {
				$format = 'Is';
			} else {
				$format = 'S';
			}
		}

		$ret = '';
		$j = 0;
		$length = mb_strlen($format);
		for ($i = 0; $i < $length; $i++) {
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
			}

			if ($str > 0 || $j > 0 || $options['zero'] || $i === mb_strlen($format) - 1) {
				if ($j > 0) {
					$ret .= $options['separator'];
				}

				$j++;

				$x = mb_strtolower(mb_substr($format, $i, 1));

				if ($str === 1) {
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
	 * @param mixed $date
	 * @param string|null $format Format
	 * @param array $options Options
	 * - default, separator
	 * - boolean zero: if false: 0 days 5 hours => 5 hours etc.
	 * - verbose/past/future: string with %s or boolean true/false
	 * @return string|array
	 */
	public static function relLengthOfTime($date, $format = null, array $options = []) {
		$dateTime = $date;
		if ($date !== null && !is_object($date)) {
			$dateTime = static::parse($date);
		}

		if ($dateTime !== null) {
			$date = $dateTime->format('U');
			$sec = time() - $date;
			$type = ($sec > 0) ? -1 : (($sec < 0) ? 1 : 0);
			$sec = abs($sec);
		} else {
			$sec = 0;
			$type = 0;
		}

		$defaults = [
			'verbose' => __d('tools', 'justNow'), 'zero' => false, 'separator' => ', ',
			'future' => __d('tools', 'In %s'), 'past' => __d('tools', '%s ago'), 'default' => ''];
		$options += $defaults;

		$ret = static::lengthOfTime($sec, $format, $options);

		if ($type == 1) {
			if ($options['future'] !== false) {
				return sprintf($options['future'], $ret);
			}
			return ['future' => $ret];
		}
		if ($type == -1) {
			if ($options['past'] !== false) {
				return sprintf($options['past'], $ret);
			}
			return ['past' => $ret];
		}
		if ($options['verbose'] !== false) {
			return $options['verbose'];
		}
		return $options['default'];
	}

	/**
	 * Returns true if given datetime string was day before yesterday.
	 *
	 * @param \Cake\Chronos\ChronosInterface $date Datetime
	 * @return bool True if datetime string was day before yesterday
	 */
	public static function wasDayBeforeYesterday($date) {
		return $date->toDateString() === static::now()->subDays(2)->toDateString();
	}

	/**
	 * Returns true if given datetime string is the day after tomorrow.
	 *
	 * @param \Cake\Chronos\ChronosInterface $date Datetime
	 * @return bool True if datetime string is day after tomorrow
	 */
	public static function isDayAfterTomorrow($date) {
		return $date->toDateString() === static::now()->addDays(2)->toDateString();
	}

	/**
	 * Returns true if given datetime string is not today AND is in the future.
	 *
	 * @param string $dateString Datetime string or Unix timestamp
	 * @param string|\DateTimeZone|null $timezone User's timezone
	 * @return bool True if datetime is not today AND is in the future
	 */
	public static function isNotTodayAndInTheFuture($dateString, $timezone = null) {
		$date = new CakeTime($dateString, $timezone);
		$date = $date->format('U');
		return date(FORMAT_DB_DATE, (int)$date) > date(FORMAT_DB_DATE, time());
	}

	/**
	 * Returns true if given datetime string is not now AND is in the future.
	 *
	 * @param string $dateString Datetime string or Unix timestamp
	 * @param string|\DateTimeZone|null $timezone User's timezone
	 * @return bool True if datetime is not today AND is in the future
	 */
	public static function isInTheFuture($dateString, $timezone = null) {
		$date = new CakeTime($dateString, $timezone);
		$date = $date->format('U');
		return date(FORMAT_DB_DATETIME, (int)$date) > date(FORMAT_DB_DATETIME, time());
	}

	/**
	 * Try to parse date from various input formats
	 * - DD.MM.YYYY, DD/MM/YYYY, YYYY-MM-DD, YYYY, YYYY-MM, ...
	 * - i18n: Today, Yesterday, Tomorrow
	 *
	 * @param string $date to parse
	 * @param string|null $format Format to parse (null = auto)
	 * @param string $type
	 * - start: first second of this interval
	 * - end: last second of this interval
	 * @return string timestamp
	 */
	public static function parseLocalizedDate($date, $format = null, $type = 'start') {
		$date = trim($date);
		$i18n = [
			strtolower(__d('tools', 'Today')) => ['start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'))), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')))],
			strtolower(__d('tools', 'Tomorrow')) => ['start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')) + DAY), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')) + DAY)],
			strtolower(__d('tools', 'Yesterday')) => ['start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')) - DAY), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')) - DAY)],
			strtolower(__d('tools', 'The day after tomorrow')) => ['start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')) + 2 * DAY), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')) + 2 * DAY)],
			strtolower(__d('tools', 'The day before yesterday')) => ['start' => date(FORMAT_DB_DATETIME, mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')) - 2 * DAY), 'end' => date(FORMAT_DB_DATETIME, mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')) - 2 * DAY)],
		];
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
			$explode = [$date];
		}
		if ($explode) {
			$count = count($explode);
			for ($i = 0; $i < $count; $i++) {
				$explode[$i] = static::pad($explode[$i]);
			}
			$explode[0] = static::pad($explode[0], 4, '20');

			if (count($explode) === 3) {
				return implode('-', $explode) . ' ' . ($type === 'end' ? '23:59:59' : '00:00:00');
			}
			if (count($explode) === 2) {
				return implode('-', $explode) . '-' . ($type === 'end' ? static::daysInMonth((int)$explode[0], (int)$explode[1]) : '01') . ' ' . ($type === 'end' ? '23:59:59' : '00:00:00');
			}
			return $explode[0] . '-' . ($type === 'end' ? '12' : '01') . '-' . ($type === 'end' ? '31' : '01') . ' ' . ($type === 'end' ? '23:59:59' : '00:00:00');
		}

		return '';
	}

	/**
	 * Parse a period (from ... to)
	 *
	 * @param string $searchString Search string to parse
	 * @param array $options
	 * - separator (defaults to space [ ])
	 * - format (defaults to Y-m-d H:i:s)
	 * @return array period [0=>min, 1=>max]
	 */
	public static function period($searchString, array $options = []) {
		if (strpos($searchString, ' ') !== false) {
			$filters = explode(' ', $searchString);
			$filters = [array_shift($filters), array_pop($filters)];
		} else {
			$filters = [$searchString, $searchString];
		}
		$min = $filters[0];
		$max = $filters[1];

		$min = static::parseLocalizedDate($min);
		$max = static::parseLocalizedDate($max, null, 'end');

		return [$min, $max];
	}

	/**
	 * Return SQL snippet for a period (beginning till end).
	 *
	 * @param string $searchString to parse
	 * @param string $fieldName (Model.field)
	 * @param array $options (see Time::period)
	 * @return string query SQL Query
	 */
	public static function periodAsSql($searchString, $fieldName, array $options = []) {
		$period = static::period($searchString, $options);
		return static::daysAsSql($period[0], $period[1], $fieldName);
	}

	/**
	 * Returns a partial SQL string to search for all records between two dates.
	 *
	 * @param int|string|\DateTime $begin UNIX timestamp, strtotime() valid string or DateTime object
	 * @param int|string|\DateTime $end UNIX timestamp, strtotime() valid string or DateTime object
	 * @param string $fieldName Name of database field to compare with
	 * @param string|\DateTimeZone|null $timezone Timezone string or DateTimeZone object
	 * @return string Partial SQL string.
	 */
	public static function daysAsSql($begin, $end, $fieldName, $timezone = null) {
		$begin = new CakeTime($begin, $timezone);
		$begin = $begin->format('U');
		$end = new CakeTime($end, $timezone);
		$end = $end->format('U');
		$begin = date('Y-m-d', (int)$begin) . ' 00:00:00';
		$end = date('Y-m-d', (int)$end) . ' 23:59:59';

		return "($fieldName >= '$begin') AND ($fieldName <= '$end')";
	}

	/**
	 * Returns a partial SQL string to search for all records between two times
	 * occurring on the same day.
	 *
	 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
	 * @param string $fieldName Name of database field to compare with
	 * @param string|\DateTimeZone|null $timezone Timezone string or DateTimeZone object
	 * @return string Partial SQL string.
	 */
	public static function dayAsSql($dateString, $fieldName, $timezone = null) {
		return static::daysAsSql($dateString, $dateString, $fieldName, $timezone);
	}

	/**
	 * Hours, minutes
	 * e.g. 9.3 => 9.5
	 *
	 * @param int $value
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
	 * @param int|float $value
	 * @param int|null $pad
	 * @param string $decPoint
	 * @return string
	 */
	public static function decimalToStandardTime($value, $pad = null, $decPoint = '.') {
		$base = (int)$value;
		$tmp = $value - $base;

		$tmp /= 1 / 60;
		$tmp /= 100;

		$value = $base + $tmp;
		if ($pad === null) {
			return (string)$value;
		}
		return number_format($value, $pad, $decPoint, '');
	}

	/**
	 * Parse 2,5 - 2.5 2:30 2:31:58 or even 2011-11-12 10:10:10
	 * now supports negative values like -2,5 -2,5 -2:30 -:30 or -4
	 *
	 * @param string $duration
	 * @param string[] $allowed
	 * @return int Seconds
	 */
	public static function parseLocalTime($duration, array $allowed = [':', '.', ',']) {
		if (empty($duration)) {
			return 0;
		}
		$parts = explode(' ', $duration);
		$duration = array_pop($parts);

		if (strpos($duration, '.') !== false && in_array('.', $allowed, true)) {
			$duration = static::decimalToStandardTime((float)$duration, 2, ':');
		} elseif (strpos($duration, ',') !== false && in_array(',', $allowed, true)) {
			$duration = str_replace(',', '.', $duration);
			$duration = static::decimalToStandardTime((float)$duration, 2, ':');
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
	 * @param string[] $allowed
	 * @return int Seconds
	 */
	public static function parseLocalDate($date, array $allowed = ['.', '-']) {
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
			$date = mktime(0, 0, 0, (int)$pieces[1], (int)$pieces[0], (int)$year);
		} elseif (strpos($date, '-') !== false) {
			//$pieces = explode('-', $date);
			$date = strtotime($date);
		} else {
			return 0;
		}
		return $date;
	}

	/**
	 * Returns nicely formatted duration difference
	 * as string like 2:30 (H:MM) or 2:30:06 (H:MM:SS) etc.
	 * Note that the more than days is currently not supported accurately.
	 *
	 * E.g. for days and hours set format to: $d:$H
	 *
	 * @param int|\DateInterval $duration Duration in seconds or as DateInterval object
	 * @param string $format Defaults to hours, minutes and seconds
	 * @return string Time
	 */
	public static function duration($duration, $format = '%h:%I:%S') {
		if (!$duration instanceof \DateInterval) {
			$d1 = new CakeTime();
			$d2 = new CakeTime();
			$d2->add(new DateInterval('PT' . $duration . 'S'));

			$duration = $d2->diff($d1);
		}
		if (stripos($format, 'd') === false && $duration->d) {
			$duration->h += $duration->d * 24;
		}
		if (stripos($format, 'h') === false && $duration->h) {
			$duration->i += $duration->h * 60;
		}
		if (stripos($format, 'i') === false && $duration->i) {
			$duration->s += $duration->m * 60;
		}

		return $duration->format($format);
	}

	/**
	 * Returns nicely formatted duration difference
	 * as string like 2:30 or 2:30:06.
	 * Note that the more than hours is currently not supported.
	 *
	 * Note that duration with DateInterval supports only values < month with accuracy,
	 * as it approximates month as "30".
	 *
	 * @param int|\DateInterval $duration Duration in seconds or as DateInterval object
	 * @param string $format Defaults to hours and minutes
	 * @return string Time
	 * @deprecated Use duration() instead?
	 */
	public static function buildTime($duration, $format = 'H:MM:SS') {
		if ($duration instanceof \DateInterval) {
			$m = $duration->invert ? -1 : 1;

			$duration = ($duration->y * YEAR) +
			($duration->m * MONTH) +
			($duration->d * DAY) +
			($duration->h * HOUR) +
			($duration->i * MINUTE) +
			$duration->s;
			$duration *= $m;
		}

		if ($duration < 0) {
			$duration = abs($duration);
			$isNegative = true;
		}

		$minutes = $duration % HOUR;
		$hours = ($duration - $minutes) / HOUR;

		$res = [];
		if (strpos($format, 'H') !== false) {
			$res[] = (int)$hours . ':' . static::pad((string)($minutes / MINUTE));
		} else {
			$res[] = (int)($minutes / MINUTE);
		}

		if (strpos($format, 'SS') !== false) {
			$seconds = $duration % MINUTE;
			$res[] = static::pad((string)$seconds);
		}

		$res = implode(':', $res);

		if (!empty($isNegative)) {
			$res = '-' . $res;
		}
		return $res;
	}

	/**
	 * Return strings like 2:33:99 from seconds etc
	 *
	 * @param int $duration Duration in seconds
	 * @return string Time
	 */
	public static function buildDefaultTime($duration): string {
		$minutes = $duration % HOUR;
		$duration -= $minutes;
		$hours = $duration / HOUR;

		$seconds = $minutes % MINUTE;
		return static::pad((string)$hours) . ':' . static::pad((string)($minutes / MINUTE)) . ':' . static::pad((string)($seconds / SECOND));
	}

	/**
	 * @param string $value
	 * @param int $length
	 * @param string $string
	 * @return string
	 */
	public static function pad($value, $length = 2, $string = '0') {
		return str_pad((string)(int)$value, $length, $string, STR_PAD_LEFT);
	}

}
