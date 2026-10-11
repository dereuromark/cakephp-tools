<?php

namespace Tools\I18n;

use Cake\Chronos\Chronos;
use Cake\Chronos\ChronosDate;
use Cake\Core\Configure;
use Cake\I18n\Date as CakeDate;
use Cake\I18n\DateTime as CakeDateTime;
use DateInterval;
use DateTime as NativeDateTime;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
use InvalidArgumentException;
use RuntimeException;

/**
 * Extend CakeTime with a few important improvements:
 * - correct timezones for date only input and therefore unchanged day here
 */
class DateTime extends CakeDateTime {

	/**
	 * @var int
	 */
	final protected const DURATION_SECOND = 1;

	/**
	 * @var int
	 */
	final protected const DURATION_MINUTE = 60;

	/**
	 * @var int
	 */
	final protected const DURATION_HOUR = 3600;

	/**
	 * @var int
	 */
	final protected const DURATION_DAY = 86400;

	/**
	 * @var int
	 */
	final protected const DURATION_WEEK = 604800;

	/**
	 * @var int
	 */
	final protected const DURATION_MONTH = 2592000;

	/**
	 * @var int
	 */
	final protected const DURATION_YEAR = 31536000;

	/**
	 * @param \Cake\Chronos\Chronos|\Cake\Chronos\ChronosDate|\DateTimeInterface|array|string|int|null $time Fixed or relative time
	 * @param \DateTimeZone|string|null $timezone The timezone for the instance
	 */
	public function __construct(
		Chronos|ChronosDate|DateTimeInterface|array|string|int|null $time = 'now',
		DateTimeZone|string|null $timezone = null,
	) {
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
				$value['second'],
			);

			$time = $format;
		}

		parent::__construct($time, $timezone);
	}

	/**
	 * Detect if a timezone has a DST
	 *
	 * @param \DateTimeZone|string|null $timezone User's timezone string or DateTimeZone object
	 * @return bool
	 */
	public function hasDaylightSavingTime($timezone = null): bool {
		$timezone = $this->safeCreateDateTimeZone($timezone);
		// a date outside of DST
		$offset = $timezone->getOffset(new NativeDateTime('@' . mktime(0, 0, 0, 2, 1, (int)date('Y'))));
		$offset /= static::DURATION_HOUR;

		// a date inside of DST
		$offset2 = $timezone->getOffset(new NativeDateTime('@' . mktime(0, 0, 0, 8, 1, (int)date('Y'))));
		$offset2 /= static::DURATION_HOUR;

		return abs($offset2 - $offset) > 0;
	}

	/**
	 * Calculate the difference between two dates
	 *
	 * TODO: deprecate in favor of DateTime::diff() etc which will be more precise
	 *
	 * should only be used for < month (due to the different month lengths it gets fuzzy)
	 *
	 * @param mixed $startTime (db format or timestamp)
	 * @param mixed $endTime (db format or timestamp)
	 * @param array<string, mixed> $options
	 * @return int The distance in seconds
	 */
	public static function difference($startTime, $endTime, array $options = []): int {
		if ($startTime instanceof DateTimeInterface) {
			$startTime = $startTime->getTimestamp();
		}
		if ($endTime instanceof DateTimeInterface) {
			$endTime = $endTime->getTimestamp();
		}

		if (!is_int($startTime)) {
			$startTime = strtotime((string)$startTime);
		}
		if (!is_int($endTime)) {
			$endTime = strtotime((string)$endTime);
		}

		//@FIXME: make it work for > month
		return (int)abs($endTime - $startTime);
	}

	/**
	 * Calculates the age using start and optional end date.
	 * Both dates default to current date. Note that start needs
	 * to be before end for a valid result.
	 *
	 * @param \DateTimeInterface|string|int $start Start date (if empty, use today)
	 * @param \DateTimeInterface|string|int|null $end End date (if empty, use today)
	 * @return int Age (0 if both timestamps are equal or empty, -1 on invalid dates)
	 */
	public static function age($start, $end = null): int {
		if (!$start && !$end || $start == $end) {
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
			$startDate = new CakeDateTime($start);
		}

		$endDate = $end;
		if (!is_object($end)) {
			$endDate = new CakeDateTime($end);
		}

		if ($startDate > $endDate) {
			return -1;
		}

		if ($startDate instanceof CakeDate) {
			$startDate = $startDate->toNative();
		}
		/**
		 * @var \DateTimeInterface $startDate
		 * @var \DateTimeInterface $endDate
		 */
		$oDateInterval = $endDate->diff($startDate);

		return $oDateInterval->y;
	}

	/**
	 * Returns the age only with the year available
	 * can be e.g. 22/23
	 *
	 * @param int $year
	 * @param int|null $month (optional)
	 * @return string Age
	 */
	public static function ageByYear(int $year, ?int $month = null): string {
		if ($month === null) {
			$maxAge = static::age((int)mktime(0, 0, 0, 1, 1, $year));
			$minAge = static::age((int)mktime(23, 59, 59, 12, 31, $year));
			$ages = array_unique([$minAge, $maxAge]);

			return implode('/', $ages);
		}
		if ((int)date('n') === $month) {
			$maxAge = static::age((int)mktime(0, 0, 0, $month, 1, $year));
			$minAge = static::age((int)mktime(23, 59, 59, $month, static::daysInMonth($year, $month), $year));

			$ages = array_unique([$minAge, $maxAge]);

			return implode('/', $ages);
		}

		return (string)static::age((int)mktime(0, 0, 0, $month, 1, $year));
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
	 *
	 * @return array<int>|int
	 */
	public static function ageRange(int $year, ?int $month = null, ?int $day = null, int $steps = 1) {
		if ($month == null && $day == null) {
			$age = (int)date('Y') - $year - 1;
		} elseif ($day == null) {
			$age = $month >= (int)date('m') ? (int)date('Y') - $year - 1 : (int)date('Y') - $year;
		} elseif ($month > (int)date('m') || ($month == (int)date('m') && $day > (int)date('d'))) {
			$age = (int)date('Y') - $year - 1;
		} else {
				$age = (int)date('Y') - $year;
		}
		if ($age % $steps === 0) {
			$lowerRange = $age - $steps + 1;
			$upperRange = $age;
		} else {
			$lowerRange = $age - ($age % $steps) + 1;
			$upperRange = $age - ($age % $steps) + $steps;
		}
		if ($lowerRange === $upperRange) {
			return $upperRange;
		}

		return [$lowerRange, $upperRange];
	}

	/**
	 * Return the days of a given month.
	 *
	 * @param int $year
	 * @param int $month
	 *
	 * @return int Days
	 */
	public static function daysInMonth(int $year, int $month): int {
		return (int)date('t', (int)mktime(0, 0, 0, $month, 1, $year));
	}

	/**
	 * Calendar Week (current week of the year).
	 * //TODO: use timestamp - or make the function able to use timestamps at least (besides dateString)
	 *
	 * date('W', $time) returns ISO6801 week number.
	 * Exception: Dates of the calender week of the previous year return 0. In this case the cweek of the
	 * last week of the previous year should be used.
	 *
	 * @param string|null $dateString In DB format - if none is passed, current day is used
	 * @param int $relative - weeks relative to the date (+1 next, -1 previous etc)
	 * @return string
	 */
	public static function cWeek(?string $dateString = null, int $relative = 0): string {
		if ($dateString) {
			$date = explode(' ', $dateString);
			[$y, $m, $d] = explode('-', $date[0]);
			$t = (int)mktime(0, 0, 0, (int)$m, (int)$d, (int)$y);
			$y = (int)$y;
		} else {
			$y = (int)date('Y');
			$t = time();
		}

		$relative = (int)$relative;
		if ($relative !== 0) {
			$t += static::DURATION_WEEK * $relative; // 1day * 7 * relativeWeeks
		}

		$kw = (int)date('W', $t);
		if ($kw === 0) {
			$kw = 1 + (int)date('W', $t - static::DURATION_DAY * (int)date('w', $t));
			$y--;
		}

		return str_pad((string)$kw, 2, '0', STR_PAD_LEFT) . '/' . $y;
	}

	/**
	 * Get number of days since the start of the week.
	 * 0=sunday to 7=saturday (default)
	 *
	 * @param int $num Number of day.
	 *
	 * @return int Days since the start of the week.
	 */
	public static function cWeekMod(int $num): int {
		$base = 6;

		return (int)($num - $base * floor($num / $base));
	}

	/**
	 * Calculate the beginning of a calender week
	 * if no calendar week is given get the beginning of the first week of the year
	 *
	 * @param int $year (format xxxx)
	 * @param int $cWeek (optional, defaults to first, range 1...52/53)
	 *
	 * @return int Timestamp
	 */
	public static function cWeekBeginning(int $year, int $cWeek = 0): int {
		if ($cWeek <= 1 || $cWeek > static::cWeeks($year)) {
			$first = (int)mktime(0, 0, 0, 1, 1, $year);
			$weekDay = (int)date('w', $first);

			if ($weekDay <= 4) {
				/* Thursday or less: back to Monday */
				$firstMonday = (int)mktime(0, 0, 0, 1, 1 - ($weekDay - 1), $year);
			} else {
				/* Back to Monday */
				$firstMonday = (int)mktime(0, 0, 0, 1, 1 + (7 - $weekDay + 1), $year);
			}

			return $firstMonday;
		}
		$monday = strtotime($year . 'W' . static::pad((string)$cWeek) . '1');
		if ($monday === false) {
			throw new InvalidArgumentException('Not a valid strtotime() value');
		}

		return $monday;
	}

	/**
	 * Calculate the ending of a calenderweek
	 * if no cweek is given get the ending of the last week of the year
	 *
	 * @param int $year (format xxxx)
	 * @param int $cWeek (optional, defaults to last, range 1...52/53)
	 *
	 * @return int Timestamp
	 */
	public static function cWeekEnding(int $year, int $cWeek = 0): int {
		if ($cWeek < 1 || $cWeek >= static::cWeeks($year)) {
			return static::cWeekBeginning($year + 1) - 1;
		}

		return static::cWeekBeginning($year, $cWeek + 1) - 1;
	}

	/**
	 * Calculate the amount of calender weeks in a year
	 *
	 * @param int|null $year (format xxxx, defaults to current year)
	 *
	 * @return int Amount of weeks - 52 or 53
	 */
	public static function cWeeks(?int $year = null): int {
		if ($year === null) {
			$year = (int)date('Y');
		}

		return (int)date('W', (int)mktime(23, 59, 59, 12, 28, $year));
	}

	/**
	 * Handles month/year increment calculations in a safe way, avoiding the pitfall of "fuzzy" month units.
	 *
	 * @param mixed $startDate Either a date string or a DateTime object
	 * @param int $years Years to increment/decrement
	 * @param int $months Months to increment/decrement
	 * @param int $days Days
	 * @param \DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
	 * @return object DateTime with incremented/decremented month/year values.
	 */
	public function incrementDate($startDate, $years = 0, $months = 0, $days = 0, $timezone = null) {
		$dateTime = $startDate;
		if (!is_object($startDate)) {
			$dateTime = new CakeDateTime($startDate);
			if ($timezone) {
				$dateTime = $dateTime->setTimezone($this->safeCreateDateTimeZone($timezone));
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
		$newTimeStamp = strtotime($incrementedDateString) + $days * static::DURATION_DAY;

		return static::createFromFormat('U', (string)$newTimeStamp);
	}

	/**
	 * Get the age bounds (min, max) as timestamp that would result in the given age(s)
	 * note: expects valid age (> 0 and < 120)
	 *
	 * @param int $firstAge
	 * @param int|null $secondAge (defaults to first one if not specified)
	 * @param bool $returnAsString
	 * @param string|null $relativeTime
	 *
	 * @return array Array('min'=>$min, 'max'=>$max);
	 */
	public static function ageBounds(int $firstAge, ?int $secondAge = null, bool $returnAsString = false, ?string $relativeTime = null): array {
		if ($secondAge === null) {
			$secondAge = $firstAge;
		}
		//TODO: other relative time then today should work as well
		$date = new CakeDateTime($relativeTime ?? 'now');

		$max = (int)mktime(23, 23, 59, (int)$date->format('m'), (int)$date->format('d'), (int)$date->format('Y') - $firstAge);
		$min = (int)mktime(0, 0, 1, (int)$date->format('m'), (int)$date->format('d') + 1, (int)$date->format('Y') - $secondAge - 1);

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
	 *
	 * @return bool Success
	 */
	public static function isInRange(string $dateString, int $seconds): bool {
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
	 * @deprecated Not timezone aware
	 * @param \Cake\I18n\DateTime|string|null $dateString
	 * @param string|null $format Format (YYYY-MM-DD, DD.MM.YYYY)
	 * @param array<string, mixed> $options
	 * @return string
	 *
	 */
	public static function localDate($dateString, ?string $format = null, array $options = []) {
		$defaults = [
			'default' => '-----',
			'timezone' => null,
			'language' => 'en',
			'oclock' => null,
		];
		$options += $defaults;

		/** @var \DateTimeZone|string|null $timezone */
		$timezone = $options['timezone'];
		if ($timezone === null) {
			$timezone = static::_getDefaultOutputTimezone();
		}
		if ($dateString === null) {
			$dateString = time();
		}
		if ($timezone) {
			$timezone = static::safeCreateDateTimeZone($timezone);
		}
		$date = new self($dateString, $timezone);
		if ($format === null) {
			$format = is_int($dateString) || (is_string($dateString) && str_contains($dateString, ' ')) ? 'd.m.Y, H:i' : 'd.m.Y';
		}

		$date = static::formatLocalized($date, $format, $options['language']);

		if ($options['oclock'] && str_contains($format, 'H:i')) {
			$date .= ' ' . __d('tools', 'o\'clock');
		}

		return $date;
	}

	/**
	 * @deprecated Not timezone aware
	 * @param \DateTimeInterface|\Cake\Chronos\Chronos $dt
	 * @param string $format
	 * @param string $language
	 *
	 * @return string
	 *
	 */
	public static function formatLocalized($dt, string $format, string $language = 'en'): string {
		$curTz = $dt->getTimezone();
		if ($curTz->getName() === 'Z') {
			// INTL don't know Z
			$curTz = new DateTimeZone('UTC');
		}

		$formatPattern = strtr($format, [
			'D' => '{#1}',
			'l' => '{#2}',
			'M' => '{#3}',
			'F' => '{#4}',
		]);
		$strDate = $dt->format($formatPattern);
		$regEx = '~\{#\d\}~';
		while (preg_match($regEx, $strDate, $match)) {
			$IntlFormat = strtr($match[0], [
				'{#1}' => 'E',
				'{#2}' => 'EEEE',
				'{#3}' => 'MMM',
				'{#4}' => 'MMMM',
			]);
			$fmt = datefmt_create(
				$language,
				IntlDateFormatter::FULL,
				IntlDateFormatter::FULL,
				$curTz,
				IntlDateFormatter::GREGORIAN,
				$IntlFormat,
			);
			$replace = $fmt ? (string)datefmt_format($fmt, $dt) : '???';
			$strDate = str_replace($match[0], $replace, $strDate);
		}

		return $strDate;
	}

	/**
	 * Outputs Date(time) Sting nicely formatted
	 *
	 * Options:
	 * - timezone: User's timezone
	 * - default: Default string (defaults to "-----")
	 * - oclock: Set to true to append oclock string
	 *
	 * @param \Cake\I18n\DateTime|string|null $dateString
	 * @param string|null $format Format (YYYY-MM-DD, DD.MM.YYYY)
	 * @param array<string, mixed> $options Options
	 * @return string
	 */
	public static function niceDate($dateString, $format = null, array $options = []) {
		$defaults = [
			'default' => '-----',
			'timezone' => null,
		];
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
			if (is_string($dateString) && strlen($dateString) === 10) {
				$date = new CakeDate($dateString);
			} else {
				$date = new static($dateString);
			}
		} else {
			$date = $dateString;
		}

		if ($format === null) {
			$format = $date instanceof CakeDateTime ? FORMAT_NICE_YMDHM : FORMAT_NICE_YMD;
		}

		if ($options['timezone'] && !($date instanceof CakeDate)) {
			$date = $date->setTimezone($options['timezone']);
		}
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
		$pos = strpos($time, ' ');
		if ($pos !== false) {
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
	public static function dayName($day, $abbr = false, $offset = 0): string {
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
	 * @param array<string, mixed> $options
	 * - appendDot (only for 3 letter abbr; defaults to false)
	 * @return string translatedText
	 */
	public static function monthName($month, $abbr = false, array $options = []): string {
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
	 * @param array<int> $monthKeys
	 * @param array<string, mixed> $options
	 * @return array<string>
	 */
	public static function monthNames(array $monthKeys = [], array $options = []) {
		if (!$monthKeys) {
			$monthKeys = range(1, 12);
		}
		$res = [];
		$abbr = $options['abbr'] ?? false;
		foreach ($monthKeys as $key) {
			$res[static::pad((string)$key)] = static::monthName($key, $abbr, $options);
		}

		return $res;
	}

	/**
	 * Weekdays
	 *
	 * @param array<int> $dayKeys
	 * @param array<string, mixed> $options
	 * @return array<string>
	 */
	public static function dayNames(array $dayKeys = [], array $options = []) {
		if (!$dayKeys) {
			$dayKeys = range(0, 6);
		}
		$res = [];
		$abbr = $options['abbr'] ?? false;
		$offset = $options['offset'] ?? 0;
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
	 * @param string|bool|null $past (defaults to null: return plain text)
	 * - new: if not boolean but a string use this as translating text
	 * @return string text (i18n!)
	 */
	public static function fuzzyFromOffset($offset, $past = null) {
		if ($offset <= static::DURATION_MINUTE) {
			$span = 'moments';
		} elseif ($offset < (static::DURATION_MINUTE * 20)) {
			$span = 'a few minutes';
		} elseif ($offset < static::DURATION_HOUR) {
			$span = 'less than an hour';
		} elseif ($offset < (static::DURATION_HOUR * 4)) {
			$span = 'a couple of hours';
		} elseif ($offset < static::DURATION_DAY) {
			$span = 'less than a day';
		} elseif ($offset < (static::DURATION_DAY * 2)) {
			$span = 'about a day';
		} elseif ($offset < (static::DURATION_DAY * 4)) {
			$span = 'a couple of days';
		} elseif ($offset < static::DURATION_WEEK) {
			$span = 'less than a week';
		} elseif ($offset < (static::DURATION_WEEK * 2)) {
			$span = 'about a week';
		} elseif ($offset < static::DURATION_MONTH) {
			$span = 'less than a month';
		} elseif ($offset < (static::DURATION_MONTH * 2)) {
			$span = 'about a month';
		} elseif ($offset < (static::DURATION_MONTH * 4)) {
			$span = 'a couple of months';
		} elseif ($offset < static::DURATION_YEAR) {
			$span = 'less than a year';
		} elseif ($offset < (static::DURATION_YEAR * 2)) {
			$span = 'about a year';
		} elseif ($offset < (static::DURATION_YEAR * 4)) {
			$span = 'a couple of years';
		} elseif ($offset < (static::DURATION_YEAR * 8)) {
			$span = 'a few years';
		} elseif ($offset < (static::DURATION_YEAR * 12)) {
			$span = 'about a decade';
		} elseif ($offset < (static::DURATION_YEAR * 24)) {
			$span = 'a couple of decades';
		} elseif ($offset < (static::DURATION_YEAR * 64)) {
			$span = 'several decades';
		} else {
			$span = 'a long time';
		}
		if ($past === true) {
			// This is in the past
			return __d('tools', '{0} ago', __d('tools', $span));
		}
		if ($past === false) {
			// This in the future
			return __d('tools', 'in {0}', __d('tools', $span));
		}
		if ($past !== null) {
			// Custom translation
			return __d('tools', $past, __d('tools', $span));
		}

		return __d('tools', $span);
	}

	/**
	 * Ordered duration units and their (approximate) length in seconds, used for
	 * splitting a raw seconds value. Month/year are approximations (30/365 days);
	 * relLengthOfTime() uses calendar-exact DateInterval values instead.
	 *
	 * @var array<string, int>
	 */
	protected const LENGTH_UNITS = [
		'year' => self::DURATION_YEAR,
		'month' => self::DURATION_MONTH,
		'week' => self::DURATION_WEEK,
		'day' => self::DURATION_DAY,
		'hour' => self::DURATION_HOUR,
		'minute' => self::DURATION_MINUTE,
		'second' => self::DURATION_SECOND,
	];

	/**
	 * Time length to human readable format.
	 *
	 * Without an explicit $format, the highest fitting units are used, capped by
	 * the `accuracy` option (default 2), e.g. `1 Week, 2 Days` or `3 Months, 1 Week`.
	 *
	 * @see timeAgoInWords()
	 * @param int $seconds
	 * @param string|null $format Explicit legacy format (e.g. `Dh`); uppercase = total, lowercase = remainder:
	 *   Y/M/W/D/H/I/S totals, m/w/d/h/i/s remainders. Prefer the automatic mode (null).
	 * @param array<string, mixed> $options
	 * - verbose: false for compact suffix output (`4d 1h`)
	 * - zero: if false: 0 days 5 hours => 5 hours etc.
	 * - accuracy: max number of units displayed in automatic mode (default 2)
	 * - context: translation msgctxt for unit words (e.g. `relative` for after-ago wording)
	 * - separator, default
	 * @return string
	 */
	public static function lengthOfTime($seconds, $format = null, array $options = []) {
		$defaults = ['verbose' => true, 'zero' => false, 'separator' => ', ', 'default' => '', 'accuracy' => 2, 'context' => null];
		$options += $defaults;

		$seconds = (int)$seconds;

		if ($format === null) {
			$rest = abs($seconds);
			$units = [];
			foreach (static::LENGTH_UNITS as $unit => $factor) {
				$units[$unit] = intdiv($rest, $factor);
				$rest %= $factor;
			}
			// Avoid `1 Year, 12 Months` style overflow from the 30-day month approximation
			if ($units['month'] > 11 && $units['year'] > 0) {
				$units['year'] += intdiv($units['month'], 12);
				$units['month'] %= 12;
			}

			return static::formatUnits($units, $options);
		}

		return static::formatLegacy($seconds, $format, $options);
	}

	/**
	 * Time relative to a reference point (default: now) in human readable format,
	 * negative (past) as well as positive (future).
	 *
	 * Calendar-exact: the difference is calculated on the actual dates (DateInterval),
	 * so months and years respect calendar lengths and leap years.
	 *
	 * By default the unit words are translated with msgctxt `relative`, allowing
	 * grammatically correct wording after the past/future template (e.g. German
	 * dative `Vor 4 Tagen` via a `relative` context translation of `Days`).
	 *
	 * @param mixed $date
	 * @param string|null $format Explicit legacy format (see lengthOfTime()); prefer the automatic mode (null).
	 * @param array<string, mixed> $options Options
	 * - accuracy: max number of units displayed in automatic mode (default 2)
	 * - from: reference date/time to compare against (default: now)
	 * - context: translation msgctxt for unit words (default `relative`; set null for plain)
	 * - default, separator
	 * - boolean zero: if false: 0 days 5 hours => 5 hours etc.
	 * - verbose/past/future: string with %s or boolean true/false
	 *
	 * @return array|string
	 */
	public static function relLengthOfTime($date, ?string $format = null, array $options = []) {
		$dateTime = $date;
		if ($date !== null && !is_object($date)) {
			$dateTime = static::parse($date);
		}

		$defaults = [
			'verbose' => __d('tools', 'justNow'),
			'zero' => false,
			'separator' => ', ',
			'future' => __d('tools', 'In %s'),
			'past' => __d('tools', '%s ago'),
			'default' => '',
			'accuracy' => 2,
			'context' => 'relative',
			'from' => null,
		];
		$options += $defaults;

		$from = $options['from'] ?? static::now();
		if (!is_object($from)) {
			$from = static::parse($from);
		}
		/** @var \DateTimeInterface $from */

		if ($dateTime !== null) {
			$sec = $from->getTimestamp() - $dateTime->getTimestamp();
			$type = $sec > 0 ? -1 : ($sec < 0 ? 1 : 0);
			$sec = (int)abs($sec);
		} else {
			$sec = 0;
			$type = 0;
		}

		if ($type === 0) {
			if ($options['verbose'] !== false) {
				return $options['verbose'];
			}

			return $options['default'];
		}

		if ($format === null) {
			/** @var \DateTimeInterface $dateTime */
			// Second precision: strip microseconds so e.g. `in 4 days, 5 hours`
			// does not truncate down to `4 days, 4 hours` against now().
			$fromClean = new NativeDateTime($from->format('Y-m-d H:i:s'), $from->getTimezone() ?: null);
			$dateClean = new NativeDateTime($dateTime->format('Y-m-d H:i:s'), $dateTime->getTimezone() ?: null);
			$interval = $fromClean->diff($dateClean);
			$units = [
				'year' => $interval->y,
				'month' => $interval->m,
				'week' => intdiv($interval->d, 7),
				'day' => $interval->d % 7,
				'hour' => $interval->h,
				'minute' => $interval->i,
				'second' => $interval->s,
			];
			$ret = static::formatUnits($units, $options);
		} else {
			$ret = static::formatLegacy($sec, $format, $options);
		}

		if ($type === 1) {
			if ($options['future'] !== false) {
				return sprintf($options['future'], $ret);
			}

			return ['future' => $ret];
		}

		if ($options['past'] !== false) {
			return sprintf($options['past'], $ret);
		}

		return ['past' => $ret];
	}

	/**
	 * Render a split units array (year...second => amount) into a human readable
	 * string, starting at the highest non-zero unit and capped by `accuracy`.
	 *
	 * @param array<string, int> $units
	 * @param array<string, mixed> $options See lengthOfTime().
	 * @return string
	 */
	protected static function formatUnits(array $units, array $options): string {
		$accuracy = max(1, (int)$options['accuracy']);

		$display = [];
		$started = false;
		foreach ($units as $unit => $amount) {
			// Skip leading zero units (except a final zero-seconds-only value)
			if (!$started && $amount === 0 && $unit !== 'second') {
				continue;
			}
			$started = true;
			if ($amount === 0 && !$options['zero'] && $display) {
				continue;
			}
			$display[] = $amount . static::unitWord($unit, $amount, $options);
			if (count($display) >= $accuracy) {
				break;
			}
		}

		if (!$display) {
			if (trim((string)$options['default']) !== '') {
				return $options['default'];
			}
			$display[] = 0 . static::unitWord('second', 0, $options);
		}

		return implode($options['separator'], $display);
	}

	/**
	 * Translated word for a unit amount, singular/plural aware.
	 *
	 * With a `context` option set, the plural word is translated with that msgctxt
	 * (`__dx()`), so translations can differ by grammatical position (e.g. German
	 * `Tage` vs dative `Tagen` after `Vor ...`). Untranslated contexts fall back
	 * to the plain English msgid.
	 *
	 * @param string $unit One of year, month, week, day, hour, minute, second.
	 * @param int $amount
	 * @param array<string, mixed> $options See lengthOfTime().
	 * @return string
	 */
	protected static function unitWord(string $unit, int $amount, array $options): string {
		if (!$options['verbose']) {
			$compact = [
				'year' => 'y',
				'month' => 'mth',
				'week' => 'w',
				'day' => 'd',
				'hour' => 'h',
				'minute' => 'm',
				'second' => 's',
			];

			return $compact[$unit];
		}

		$word = ucfirst($unit);
		if ($amount !== 1) {
			$word .= 's';
		}

		if (!empty($options['context'])) {
			return ' ' . __dx('tools', $options['context'], $word);
		}

		$title = __d('tools', $word);
		if ($amount !== 1 && !empty($options['plural']) && mb_substr($title, -1, 1) === 'e') {
			$title .= $options['plural'];
		}

		return ' ' . $title;
	}

	/**
	 * Legacy format-character engine for explicit $format strings.
	 *
	 * Uppercase = total amount, lowercase = remainder within the next higher unit:
	 * Y (years), M/m (months), W/w (weeks), D/d (days), H/h (hours), I/i (minutes), S/s (seconds).
	 *
	 * @param int $seconds
	 * @param string $format
	 * @param array<string, mixed> $options See lengthOfTime().
	 * @return string
	 */
	protected static function formatLegacy(int $seconds, string $format, array $options): string {
		if (!$options['verbose']) {
			$s = [
				'y' => 'y',
				'm' => 'mth',
				'w' => 'w',
				'd' => 'd',
				'h' => 'h',
				'i' => 'm',
				's' => 's',
			];
			$p = $s;
		} else {
			$s = [
				'y' => ' ' . __d('tools', 'Year'), # translated
				'm' => ' ' . __d('tools', 'Month'),
				'w' => ' ' . __d('tools', 'Week'),
				'd' => ' ' . __d('tools', 'Day'),
				'h' => ' ' . __d('tools', 'Hour'),
				'i' => ' ' . __d('tools', 'Minute'),
				's' => ' ' . __d('tools', 'Second'),
			];
			$p = [
				'y' => ' ' . __d('tools', 'Years'), # translated
				'm' => ' ' . __d('tools', 'Months'),
				'w' => ' ' . __d('tools', 'Weeks'),
				'd' => ' ' . __d('tools', 'Days'),
				'h' => ' ' . __d('tools', 'Hours'),
				'i' => ' ' . __d('tools', 'Minutes'),
				's' => ' ' . __d('tools', 'Seconds'),
			];
		}

		$ret = '';
		$j = 0;
		$length = mb_strlen($format);
		for ($i = 0; $i < $length; $i++) {
			switch (mb_substr($format, $i, 1)) {
				case 'Y':
					$str = intdiv($seconds, static::DURATION_YEAR);

					break;
				case 'M':
					$str = intdiv($seconds, static::DURATION_MONTH);

					break;
				case 'm':
					$str = intdiv($seconds, static::DURATION_MONTH) % 12;

					break;
				case 'W':
					$str = intdiv($seconds, static::DURATION_WEEK);

					break;
				case 'w':
					$str = intdiv($seconds % static::DURATION_MONTH, static::DURATION_WEEK);

					break;
				case 'D':
					$str = intdiv($seconds, static::DURATION_DAY);

					break;
				case 'd':
					$str = intdiv($seconds, static::DURATION_DAY) % 30;

					break;
				case 'H':
					$str = intdiv($seconds, static::DURATION_HOUR);

					break;
				case 'h':
					$str = intdiv($seconds, static::DURATION_HOUR) % 24;

					break;
				case 'I':
					$str = intdiv($seconds, static::DURATION_MINUTE);

					break;
				case 'i':
					$str = intdiv($seconds, static::DURATION_MINUTE) % 60;

					break;
				case 'S':
					$str = $seconds;

					break;
				case 's':
					$str = $seconds % 60;

					break;
				default:
					return '';
			}

			if ($str > 0 || $j > 0 || $options['zero'] || $i === $length - 1) {
				if ($j > 0) {
					$ret .= $options['separator'];
				}

				$j++;

				$x = mb_strtolower(mb_substr($format, $i, 1));

				if ($str === 1) {
					$ret .= $str . $s[$x];
				} else {
					$title = $p[$x];
					if (!empty($options['plural']) && mb_substr($title, -1, 1) === 'e') {
						$title .= $options['plural'];
					}
					$ret .= $str . $title;
				}
			}
		}

		return $ret;
	}

	/**
	 * Returns true if given datetime string was day before yesterday.
	 *
	 * @param \Cake\Chronos\Chronos $date Datetime
	 * @return bool True if datetime string was day before yesterday
	 */
	public static function wasDayBeforeYesterday($date): bool {
		return $date->toDateString() === static::now()->subDays(2)->toDateString();
	}

	/**
	 * Returns true if given datetime string is the day after tomorrow.
	 *
	 * @param \Cake\Chronos\Chronos $date Datetime
	 * @return bool True if datetime string is day after tomorrow
	 */
	public static function isDayAfterTomorrow($date): bool {
		return $date->toDateString() === static::now()->addDays(2)->toDateString();
	}

	/**
	 * Returns true if given datetime string is not today AND is in the future.
	 *
	 * @param string $dateString Datetime string or Unix timestamp
	 * @param \DateTimeZone|string|null $timezone User's timezone
	 * @return bool True if datetime is not today AND is in the future
	 */
	public static function isNotTodayAndInTheFuture($dateString, $timezone = null): bool {
		$date = new CakeDateTime($dateString, $timezone);
		$date = $date->format('U');

		return date(FORMAT_DB_DATE, (int)$date) > date(FORMAT_DB_DATE, time());
	}

	/**
	 * Returns true if given datetime string is not now AND is in the future.
	 *
	 * @param \DateTimeInterface|string $date Datetime string or Unix timestamp
	 * @param \DateTimeZone|string|null $timezone User's timezone
	 * @return bool True if datetime is not today AND is in the future
	 */
	public static function isInTheFuture($date, $timezone = null): bool {
		if (!($date instanceof DateTimeInterface)) {
			$date = new CakeDateTime($date, $timezone);
		}
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
	public static function parseLocalizedDate($date, $format = null, $type = 'start'): string {
		$date = trim($date);
		$i18n = [
			strtolower(__d('tools', 'Today')) => ['start' => date(FORMAT_DB_DATETIME, (int)mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'))), 'end' => date(FORMAT_DB_DATETIME, (int)mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')))],
			strtolower(__d('tools', 'Tomorrow')) => ['start' => date(FORMAT_DB_DATETIME, (int)mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')) + static::DURATION_DAY), 'end' => date(FORMAT_DB_DATETIME, (int)mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')) + static::DURATION_DAY)],
			strtolower(__d('tools', 'Yesterday')) => ['start' => date(FORMAT_DB_DATETIME, (int)mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')) - static::DURATION_DAY), 'end' => date(FORMAT_DB_DATETIME, (int)mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')) - static::DURATION_DAY)],
			strtolower(__d('tools', 'The day after tomorrow')) => ['start' => date(FORMAT_DB_DATETIME, (int)mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')) + 2 * static::DURATION_DAY), 'end' => date(FORMAT_DB_DATETIME, (int)mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')) + 2 * static::DURATION_DAY)],
			strtolower(__d('tools', 'The day before yesterday')) => ['start' => date(FORMAT_DB_DATETIME, (int)mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y')) - 2 * static::DURATION_DAY), 'end' => date(FORMAT_DB_DATETIME, (int)mktime(23, 59, 59, (int)date('m'), (int)date('d'), (int)date('Y')) - 2 * static::DURATION_DAY)],
		];
		if (isset($i18n[strtolower($date)])) {
			return $i18n[strtolower($date)][$type];
		}

		if ($format) {
			$res = static::createFromFormat($format, $date);

			return $res->format(FORMAT_DB_DATE) . ' ' . ($type === 'end' ? '23:59:59' : '00:00:00');
		}

		if (str_contains($date, '.')) {
			$explode = explode('.', $date, 3);
			$explode = array_reverse($explode);
		} elseif (str_contains($date, '/')) {
			$explode = explode('/', $date, 3);
			$explode = array_reverse($explode);
		} elseif (str_contains($date, '-')) {
			$explode = explode('-', $date, 3);
		} else {
			$explode = [$date];
		}

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

	/**
	 * Parse a period (from ... to)
	 *
	 * @param string $searchString Search string to parse
	 * @param array<string, mixed> $options
	 * - separator (defaults to space [ ])
	 * - format (defaults to Y-m-d H:i:s)
	 *
	 * @return array period [0=>min, 1=>max]
	 */
	public static function period(string $searchString, array $options = []): array {
		$filters = explode(' ', trim($searchString));
		$min = $filters[0];
		$max = $filters[count($filters) - 1];

		$min = static::parseLocalizedDate($min);
		$max = static::parseLocalizedDate($max, null, 'end');

		return [$min, $max];
	}

	/**
	 * Return SQL snippet for a period (beginning till end).
	 *
	 * @param string $searchString to parse
	 * @param string $fieldName (Model.field)
	 * @param array<string, mixed> $options (see Time::period)
	 *
	 * @return string query SQL Query
	 */
	public static function periodAsSql(string $searchString, string $fieldName, array $options = []): string {
		$period = static::period($searchString, $options);

		return static::daysAsSql($period[0], $period[1], $fieldName);
	}

	/**
	 * Returns a partial SQL string to search for all records between two dates.
	 *
	 * @param \DateTimeInterface|string|int $begin UNIX timestamp, strtotime() valid string or DateTime object
	 * @param \DateTimeInterface|string|int $end UNIX timestamp, strtotime() valid string or DateTime object
	 * @param string $fieldName Name of database field to compare with
	 * @param \DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
	 *
	 * @return string Partial SQL string.
	 */
	public static function daysAsSql(DateTimeInterface|string|int $begin, DateTimeInterface|string|int $end, string $fieldName, DateTimeZone|string|null $timezone = null): string {
		$begin = new CakeDateTime($begin, $timezone);
		$begin = $begin->format('U');
		$end = new CakeDateTime($end, $timezone);
		$end = $end->format('U');
		$begin = date('Y-m-d', (int)$begin) . ' 00:00:00';
		$end = date('Y-m-d', (int)$end) . ' 23:59:59';

		return "($fieldName >= '$begin') AND ($fieldName <= '$end')";
	}

	/**
	 * Returns a partial SQL string to search for all records between two times
	 * occurring on the same day.
	 *
	 * @param \DateTimeInterface|string|int $dateString UNIX timestamp, strtotime() valid string or DateTime object
	 * @param string $fieldName Name of database field to compare with
	 * @param \DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
	 *
	 * @return string Partial SQL string.
	 */
	public static function dayAsSql(DateTimeInterface|string|int $dateString, string $fieldName, DateTimeZone|string|null $timezone = null): string {
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

		return $base + $tmp;
	}

	/**
	 * Hours, minutes
	 * e.g. 9.5 => 9.3
	 * with pad=2: 9.30
	 *
	 * @param float|int $value
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
	 * @param array<string> $allowed
	 * @return int Seconds
	 */
	public static function parseLocalTime($duration, array $allowed = [':', '.', ',']) {
		if (!$duration) {
			return 0;
		}
		$parts = explode(' ', $duration);
		$duration = array_pop($parts);

		if (str_contains($duration, '.') && in_array('.', $allowed, true)) {
			$duration = static::decimalToStandardTime((float)$duration, 2, ':');
		} elseif (str_contains($duration, ',') && in_array(',', $allowed, true)) {
			$duration = str_replace(',', '.', $duration);
			$duration = static::decimalToStandardTime((float)$duration, 2, ':');
		}

		// now there is only the time schema left...
		$pieces = explode(':', $duration, 3);
		$res = 0;
		$hours = abs((int)$pieces[0]) * static::DURATION_HOUR;
		//echo pre($hours);
		$isNegative = (str_contains((string)$pieces[0], '-'));

		if (count($pieces) === 3) {
			$res += $hours + ((int)$pieces[1]) * static::DURATION_MINUTE + ((int)$pieces[2]) * static::DURATION_SECOND;
		} elseif (count($pieces) === 2) {
			$res += $hours + ((int)$pieces[1]) * static::DURATION_MINUTE;
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
	 * @param array<string> $allowed
	 * @return int Seconds
	 */
	public static function parseLocalDate($date, array $allowed = ['.', '-']) {
		$datePieces = explode(' ', $date, 2);
		$date = array_shift($datePieces);

		if (str_contains($date, '.')) {
			$pieces = explode('.', $date);
			$year = $pieces[2];
			if (strlen($year) === 2) {
				$year = $year < 50 ? '20' . $year : '19' . $year;
			}
			$date = mktime(0, 0, 0, (int)$pieces[1], (int)$pieces[0], (int)$year);
		} elseif (str_contains($date, '-')) {
			$date = strtotime($date);
		} else {
			return 0;
		}

		if ($date === false) {
			throw new RuntimeException('Cannot parse date');
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
	 * @param \DateInterval|int $duration Duration in seconds or as DateInterval object
	 * @param string $format Defaults to hours, minutes and seconds
	 * @return string Time
	 */
	public static function duration($duration, $format = '%h:%I:%S') {
		if (!$duration instanceof DateInterval) {
			$d1 = new NativeDateTime();
			$d2 = new NativeDateTime();
			$d2 = $d2->add(new DateInterval('PT' . $duration . 'S'));

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
	 * @deprecated Use duration() instead?
	 * @param \DateInterval|int $duration Duration in seconds or as DateInterval object
	 * @param string $format Defaults to hours and minutes
	 * @return string Time
	 */
	public static function buildTime($duration, $format = 'H:MM:SS') {
		if ($duration instanceof DateInterval) {
			$m = $duration->invert ? -1 : 1;

			$duration = ($duration->y * static::DURATION_YEAR) +
				($duration->m * static::DURATION_MONTH) +
				($duration->d * static::DURATION_DAY) +
				($duration->h * static::DURATION_HOUR) +
				($duration->i * static::DURATION_MINUTE) +
				$duration->s;
			$duration *= $m;
		}

		if ($duration < 0) {
			$duration = abs($duration);
			$isNegative = true;
		}

		$minutes = $duration % static::DURATION_HOUR;
		$hours = ($duration - $minutes) / static::DURATION_HOUR;

		$res = [];
		if (str_contains($format, 'H')) {
			$res[] = (int)$hours . ':' . static::pad((string)($minutes / static::DURATION_MINUTE));
		} else {
			$res[] = (int)($minutes / static::DURATION_MINUTE);
		}

		if (str_contains($format, 'SS')) {
			$seconds = $duration % static::DURATION_MINUTE;
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
		$minutes = $duration % static::DURATION_HOUR;
		$duration -= $minutes;
		$hours = $duration / static::DURATION_HOUR;

		$seconds = $minutes % static::DURATION_MINUTE;

		return static::pad((string)$hours) . ':' . static::pad((string)($minutes / static::DURATION_MINUTE)) . ':' . static::pad((string)($seconds / static::DURATION_SECOND));
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
