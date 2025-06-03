<?php

namespace Tools\I18n;

class DateTimeHelper {

	/**
	 * @param array<string, mixed> $time
	 * @return string
	 */
	public static function constructDate(array $time): string {
		$format = '';
		if (
			isset($time['year'], $time['month'], $time['day']) &&
			(is_numeric($time['year']) && is_numeric($time['month']) && is_numeric($time['day']))
		) {
			$format = sprintf('%d-%02d-%02d', $time['year'], $time['month'], $time['day']);
		}

		return $format;
	}

	/**
	 * @param array<string, mixed> $time
	 * @return string
	 */
	public static function constructTime(array $time): string {
		$format = '';
		$time += [
			'minute' => 0,
			'second' => 0,
		];

		if (
			isset($time['hour']) &&
			(is_numeric($time['hour']) && is_numeric($time['minute']) && is_numeric($time['second']))
		) {
			$format = sprintf('%02d:%02d:%02d', $time['hour'], $time['minute'], $time['second']);
		}

		return $format;
	}

	/**
	 * @param array<string, mixed> $time
	 * @return string
	 */
	public static function constructDatetime(array $time): string {
		$date = static::constructDate($time);
		$time = static::constructTime($time);
		if (!$date) {
			return '';
		}
		if (!$time) {
			return $date . ' 00:00:00';
		}

		return $date . ' ' . $time;
	}

}
