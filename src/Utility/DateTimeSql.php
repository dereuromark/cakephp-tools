<?php

namespace Tools\Utility;

use Cake\I18n\DateTime as CakeDateTime;
use DateTimeInterface;
use DateTimeZone;
use Tools\I18n\DateTime;

/**
 * SQL date range builders.
 */
class DateTimeSql {

	/**
	 * Return SQL snippet for a period (beginning till end).
	 *
	 * @param string $searchString to parse
	 * @param string $fieldName (Model.field)
	 * @param array<string, mixed> $options Legacy options passed to DateTime::period(); currently unused.
	 *
	 * @return string query SQL Query
	 */
	public static function periodAsSql(string $searchString, string $fieldName, array $options = []): string {
		$period = DateTime::period($searchString, $options);

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

}
