<?php

namespace Tools\Test\TestCase\Utility;

use Cake\I18n\DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Shim\TestSuite\TestCase;
use Tools\Utility\DateTimeSql;

class DateTimeSqlTest extends TestCase {

	/**
	 * @return void
	 */
	public function testPeriodAsSql() {
		$values = [
			[__d('tools', 'Today'), "(Model.field >= '" . date(FORMAT_DB_DATE) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE) . " 23:59:59')"],
			[__d('tools', 'Yesterday') . ' ' . __d('tools', 'until') . ' ' . __d('tools', 'Today'), "(Model.field >= '" . date(FORMAT_DB_DATE, time() - DAY) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE) . " 23:59:59')"],
			[__d('tools', 'Today') . ' ' . __d('tools', 'until') . ' ' . __d('tools', 'Tomorrow'), "(Model.field >= '" . date(FORMAT_DB_DATE, time()) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE, time() + DAY) . " 23:59:59')"],
			[__d('tools', 'Yesterday') . ' ' . __d('tools', 'until') . ' ' . __d('tools', 'Tomorrow'), "(Model.field >= '" . date(FORMAT_DB_DATE, time() - DAY) . " 00:00:00') AND (Model.field <= '" . date(FORMAT_DB_DATE, time() + DAY) . " 23:59:59')"],
		];

		foreach ($values as $v) {
			$ret = DateTimeSql::periodAsSql($v[0], 'Model.field');
			$this->assertSame($v[1], $ret);
		}
	}

	/**
	 * @return void
	 */
	public function testRangeInputTypes(): void {
		$expected = "(Posts.created >= '2012-02-03 00:00:00') AND (Posts.created <= '2012-02-05 23:59:59')";
		$this->assertSame($expected, DateTimeSql::daysAsSql('2012-02-03', '2012-02-05', 'Posts.created'));
		$this->assertSame($expected, DateTimeSql::daysAsSql(new DateTimeImmutable('2012-02-03'), new DateTime('2012-02-05'), 'Posts.created'));
		$this->assertSame($expected, DateTimeSql::daysAsSql((new DateTime('2012-02-03'))->getTimestamp(), (new DateTime('2012-02-05'))->getTimestamp(), 'Posts.created'));
	}

	/**
	 * @return void
	 */
	public function testTimezoneKeepsReleasedSqlSemantics(): void {
		$timezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		try {
			$this->assertSame(
				"(Posts.created >= '2012-02-02 00:00:00') AND (Posts.created <= '2012-02-02 23:59:59')",
				DateTimeSql::dayAsSql('2012-02-03 00:30:00', 'Posts.created', new DateTimeZone('Europe/Berlin')),
			);
		} finally {
			date_default_timezone_set($timezone);
		}
	}

}
