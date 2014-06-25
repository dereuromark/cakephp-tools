<?php

App::uses('DatetimeHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');

/**
 * Datetime Test Case
 *
 */
class DatetimeHelperTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->Datetime = new DatetimeHelper(new View(null));
		$this->Datetime->Html = new HtmlHelper(new View(null));
	}

	/**
	 * Test user age
	 *
	 * @return void
	 */
	public function testUserAge() {
		$res = $this->Datetime->userAge((date('Y') - 4) . '-01-01');
		$this->assertTrue($res >= 3 && $res <= 5);

		$res = $this->Datetime->userAge('2023-01-01');
		$this->assertSame('---', $res);

		$res = $this->Datetime->userAge('1903-01-01');
		$this->assertSame('---', $res);

		$res = $this->Datetime->userAge('1901-01-01');
		$this->assertSame('---', $res);
	}

	/**
	 * Tests that calling a CakeTime method works.
	 *
	 * @return void
	 */
	public function testTimeAgoInWords() {
		$res = $this->Datetime->timeAgoInWords(date(FORMAT_DB_DATETIME, time() - 4 * DAY - 5 * HOUR));
		$this->debug($res);
	}

	/**
	 * DatetimeHelperTest::testLocalDateMarkup()
	 *
	 * @return void
	 */
	public function testLocalDateMarkup() {
		$result = $this->Datetime->localDateMarkup('2014-11-12 22:11:18');
		$expected = '<span>12.11.2014, 22:11</span>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * DatetimeHelperTest::testNiceDateMarkup()
	 *
	 * @return void
	 */
	public function testNiceDateMarkup() {
		$result = $this->Datetime->niceDateMarkup('2014-11-12 22:11:18');
		$expected = '<span>12.11.2014, 22:11</span>';
		$this->assertEquals($expected, $result);
	}

	/**
	 * DatetimeHelperTest::testPublished()
	 *
	 * @return void
	 */
	public function testPublished() {
		$result = $this->Datetime->published(date(FORMAT_DB_DATETIME, time() + DAY));
		$expected = 'class="published notyet';
		$this->assertContains($expected, $result);

		$result = $this->Datetime->published(date(FORMAT_DB_DATETIME, time() - DAY));
		$expected = 'class="published already';
		$this->assertContains($expected, $result);
	}

	/**
	 * DatetimeHelperTest::testTimezones()
	 *
	 * @return void
	 */
	public function testTimezones() {
		$result = $this->Datetime->timezones();
		$this->assertTrue(!empty($result));
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Datetime);
	}

}
