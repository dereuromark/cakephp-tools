<?php

namespace Tools\I18n;

use Cake\Chronos\ChronosDate;
use Cake\I18n\Date as CakeDate;
use DateTimeInterface;
use DateTimeZone;

class Date extends CakeDate {

	/**
	 * Create a new Immutable Date instance.
	 *
	 * Dates do not have time or timezone components exposed. Internally
	 * ChronosDate wraps a PHP DateTimeImmutable but limits modifications
	 * to only those that operate on day values.
	 *
	 * By default, dates will be calculated from the server's default timezone.
	 * You can use the `timezone` parameter to use a different timezone. Timezones
	 * are used when parsing relative date expressions like `today` and `yesterday`
	 * but do not participate in parsing values like `2022-01-01`.
	 *
	 * @param \Cake\Chronos\ChronosDate|\DateTimeInterface|array|string $time Fixed or relative time
	 * @param \DateTimeZone|string|null $timezone The time zone used for 'now'
	 */
	public function __construct(
		ChronosDate|DateTimeInterface|array|string $time = 'now',
		DateTimeZone|string|null $timezone = null,
	) {
		if (is_array($time)) {
			$format = '';
			if (
				isset($time['year'], $time['month'], $time['day']) &&
				(is_numeric($time['year']) && is_numeric($time['month']) && is_numeric($time['day']))
			) {
				$format = sprintf('%d-%02d-%02d', $time['year'], $time['month'], $time['day']);
			}

			$time = $format;
		}

		parent::__construct($time, $timezone);
	}

}
