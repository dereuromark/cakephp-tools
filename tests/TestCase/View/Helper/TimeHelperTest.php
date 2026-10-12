<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\I18n\Date;
use Cake\I18n\DateTime as CakeDateTime;
use Cake\View\View;
use DateTimeImmutable;
use DateTimeZone;
use IntlDateFormatter;
use InvalidArgumentException;
use Shim\TestSuite\TestCase;
use Tools\I18n\DateTime;
use Tools\View\Helper\TimeHelper;

/**
 * Datetime Test Case
 */
class TimeHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\TimeHelper|\Tools\Utility\Time
	 */
	protected $Time;

	/**
	 * @var string
	 */
	protected $locale;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Time = new TimeHelper(new View(null));

		$this->locale = ini_get('intl.default_locale');
		ini_set('intl.default_locale', 'de-DE');
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Time);

		ini_set('intl.default_locale', $this->locale);
	}

	/**
	 * Test calling Utility.Number class
	 *
	 * @return void
	 */
	public function testParentCall() {
		$result = $this->Time->age((date('Y') - 15) . '-01-01');
		$this->assertSame(15, $result);
	}

	/**
	 * Test user age
	 *
	 * @return void
	 */
	public function testUserAge() {
		$res = $this->Time->userAge((date('Y') - 4) . '-01-01');
		$this->assertTrue($res >= 3 && $res <= 5);

		$res = $this->Time->userAge(date('Y') . '-01-01');
		$this->assertSame('', $res);

		$res = $this->Time->userAge('1903-01-01');
		$this->assertSame('', $res);

		$res = $this->Time->userAge('1901-01-01');
		$this->assertSame('', $res);

		$birthYear = 1981;
		$res = $this->Time->userAge(new Date($birthYear . '-02-03'));
		$expectedAge = (int)date('Y') - $birthYear;
		$this->assertTrue($res >= $expectedAge - 1 && $res <= $expectedAge + 1);
	}

	/**
	 * Tests that calling a CakeTime method works.
	 *
	 * @return void
	 */
	public function testTimeAgoInWords() {
		$res = $this->Time->timeAgoInWords(date(FORMAT_DB_DATETIME, time() - 4 * DAY - 5 * HOUR));

		$this->assertNotEmpty($res);
	}

	/**
	 * @return void
	 */
	public function testPublished() {
		$result = $this->Time->published((new DateTime(date(FORMAT_DB_DATETIME)))->addSeconds(1));
		$expected = 'class="published today';
		$this->assertStringContainsString($expected, $result);

		$result = $this->Time->published((new Date(date(FORMAT_DB_DATETIME)))->addDays(1));
		$expected = 'class="published notyet';
		$this->assertStringContainsString($expected, $result);

		$result = $this->Time->published((new DateTime(date(FORMAT_DB_DATETIME)))->subDays(2));
		$expected = 'class="published already';
		$this->assertStringContainsString($expected, $result);

		$result = $this->Time->published(new DateTime('2012-02-03 14:12:10'));
		$this->assertStringContainsString('03.02.2012', $result);
	}

	/**
	 * @return void
	 */
	public function testNice() {
		$result = $this->Time->nice(new DateTime('2012-02-03 14:13:12'));
		$this->assertSame('03.02.2012, 14:13', $result);

		$result = $this->Time->nice('2012-02-03 14:12:11');
		$this->assertSame('03.02.2012, 14:12', $result);

		$result = $this->Time->nice(new Date('2012-02-03'));
		$this->assertSame('03.02.2012', $result);

		$result = $this->Time->nice('2012-02-03');
		$this->assertSame('03.02.2012', $result);
	}

	/**
	 * @return void
	 */
	public function testTimezones() {
		$result = $this->Time->timezones();
		$this->assertTrue(!empty($result));
	}

	/**
	 * @return void
	 */
	public function testExplicitDateAndTimeFormatting(): void {
		$value = new DateTimeImmutable('2012-02-03 23:30:00', new DateTimeZone('UTC'));
		$options = ['timezone' => 'Europe/Berlin', 'locale' => 'de_DE'];
		$this->assertSame('04.02.2012', $this->Time->date($value, 'dd.MM.yyyy', $options));
		$this->assertSame('04.02.2012 00:30', $this->Time->dateTime($value, 'dd.MM.yyyy HH:mm', $options));
		$this->assertSame('00:30', $this->Time->time($value, 'HH:mm', $options));
		$this->assertSame('2012-02-03 23:30:00', $value->format('Y-m-d H:i:s'));
		$this->assertSame('UTC', $value->getTimezone()->getName());
		$this->assertSame('February', $this->Time->date($value, 'MMMM', ['locale' => 'en_US']));
		$this->assertSame('Februar', $this->Time->date($value, 'MMMM', ['locale' => 'de_DE']));
		$this->assertSame('2012-02-03', $this->Time->date($value->getTimestamp(), 'yyyy-MM-dd', ['timezone' => 'UTC']));
	}

	/**
	 * @return void
	 */
	public function testCalendarDatesNeverShiftDay(): void {
		$options = ['timezone' => 'America/Los_Angeles'];
		foreach (['2012-02-03', new Date('2012-02-03')] as $value) {
			$this->assertSame('2012-02-03', $this->Time->date($value, 'yyyy-MM-dd', $options));
			$this->assertSame('2012-02-03 00:00', $this->Time->dateTime($value, 'yyyy-MM-dd HH:mm', $options));
			$this->assertSame('00:00', $this->Time->time($value, 'HH:mm', $options));
		}
	}

	/**
	 * @return void
	 */
	public function testConfiguredFormattingAndNullFallback(): void {
		$helper = new TimeHelper(new View(null), ['outputTimezone' => 'Europe/Berlin', 'default' => 'Unavailable']);
		$value = new CakeDateTime('2012-02-03 23:30:00', 'UTC');
		$this->assertSame('2012-02-04', $helper->date($value, 'yyyy-MM-dd'));
		$this->assertSame('23:30', $helper->time($value, 'HH:mm', ['timezone' => new DateTimeZone('UTC')]));
		foreach (['date', 'dateTime', 'time'] as $method) {
			$this->assertSame('Unavailable', $helper->$method());
			$this->assertSame('', $helper->$method(null, null, ['default' => '']));
			$this->assertSame('n/a', $helper->$method(null, null, ['default' => 'n/a']));
		}
		$this->assertSame('-----', $this->Time->date());
	}

	/**
	 * @return void
	 */
	public function testLocalizedDefaultStyles(): void {
		$options = ['locale' => 'en_US', 'timezone' => 'UTC'];
		$value = new CakeDateTime('2012-02-03 14:13:12', 'UTC');
		$this->assertSame((new Date('2012-02-03'))->i18nFormat(IntlDateFormatter::MEDIUM, 'en_US'), $this->Time->date($value, null, $options));
		$this->assertSame($value->i18nFormat([IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT], null, 'en_US'), $this->Time->dateTime($value, null, $options));
		$this->assertSame($value->i18nFormat([IntlDateFormatter::NONE, IntlDateFormatter::SHORT], null, 'en_US'), $this->Time->time($value, null, $options));
	}

	/**
	 * @return void
	 */
	public function testMarkupUsesOutputTimezoneAndEscapesText(): void {
		CakeDateTime::setTestNow(new CakeDateTime('2012-02-03 23:30:00', 'UTC'));
		try {
			$options = ['timezone' => 'Europe/Berlin'];
			$value = new CakeDateTime('2012-02-03 23:40:00', 'UTC');
			$this->assertSame('<span class="today">2012-02-04</span>', $this->Time->dateMarkup($value, 'yyyy-MM-dd', $options));
			$this->assertSame('<span class="today">2012-02-04 00:40</span>', $this->Time->dateTimeMarkup($value, 'yyyy-MM-dd HH:mm', $options));
			$this->assertSame('<span>2012-02-03</span>', $this->Time->dateMarkup(new Date('2012-02-03'), 'yyyy-MM-dd', $options));
			$this->assertSame('<span>&lt;b&gt;n/a&lt;/b&gt;</span>', $this->Time->dateMarkup(null, null, ['default' => '<b>n/a</b>']));
			$this->assertSame('<span>&lt;b&gt;n/a&lt;/b&gt;</span>', $this->Time->dateTimeMarkup(null, null, ['default' => '<b>n/a</b>']));
			$this->assertSame('<span class="today">&lt;b&gt;2012&lt;/b&gt;</span>', $this->Time->dateMarkup($value, "'<b>'yyyy'</b>'", $options));
		} finally {
			CakeDateTime::setTestNow();
		}
	}

	/**
	 * @return void
	 */
	public function testTimestampZeroAndDaylightSavingTransition(): void {
		$options = ['timezone' => 'Europe/Berlin'];
		$this->assertSame('1970-01-01', $this->Time->date(0, 'yyyy-MM-dd', ['timezone' => 'UTC']));
		$this->assertSame('2026-03-29 01:30', $this->Time->dateTime(new CakeDateTime('2026-03-29 00:30:00', 'UTC'), 'yyyy-MM-dd HH:mm', $options));
		$this->assertSame('2026-03-29 03:30', $this->Time->dateTime(new CakeDateTime('2026-03-29 01:30:00', 'UTC'), 'yyyy-MM-dd HH:mm', $options));
	}

	/**
	 * @return void
	 */
	public function testTodayUsesPreservedObjectTimezone(): void {
		CakeDateTime::setTestNow(new CakeDateTime('2012-02-03 23:30:00', 'UTC'));
		try {
			$helper = new TimeHelper(new View(null), ['outputTimezone' => null]);
			$value = new CakeDateTime('2012-02-04 00:40:00', 'Europe/Berlin');
			$this->assertSame('<span class="today">2012-02-04</span>', $helper->dateMarkup($value, 'yyyy-MM-dd'));
		} finally {
			CakeDateTime::setTestNow();
		}
	}

	/**
	 * @return void
	 */
	public function testMissingValuesUseFallback(): void {
		foreach ([null, '', ' ', '0000-00-00', '0000-00-00 00:00:00'] as $value) {
			foreach (['date', 'dateTime', 'time'] as $method) {
				$this->assertSame('n/a', $this->Time->$method($value, null, ['default' => 'n/a']));
			}
			foreach (['dateMarkup', 'dateTimeMarkup'] as $method) {
				$this->assertSame('<span>n/a</span>', $this->Time->$method($value, null, ['default' => 'n/a']));
			}
		}
	}

	/**
	 * @return void
	 */
	public function testTimestampDefaultsToServerTimezone(): void {
		$timezone = date_default_timezone_get();
		date_default_timezone_set('Europe/Berlin');
		try {
			$helper = new TimeHelper(new View(null), ['outputTimezone' => null]);
			$value = (new CakeDateTime('2012-02-03 23:30:00', 'UTC'))->getTimestamp();
			$this->assertSame('2012-02-04 00:30', $helper->dateTime($value, 'yyyy-MM-dd HH:mm'));
			$this->assertSame('2012-02-04', $helper->date((string)$value, 'yyyy-MM-dd'));
			$this->assertSame('2012-02-03', $helper->date($value, 'yyyy-MM-dd', ['timezone' => 'UTC']));
		} finally {
			date_default_timezone_set($timezone);
		}
	}

	/**
	 * @return void
	 */
	public function testInvalidCalendarDateIsRejected(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->Time->date('2012-02-31');
	}

	/**
	 * @return void
	 */
	public function testInvalidCalendarDateTimeIsRejected(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->Time->dateTime('2012-02-31 14:00:00');
	}

}
