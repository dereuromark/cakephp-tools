<?php

namespace Tools\Test\Utility;

use Tools\TestSuite\TestCase;
use Tools\Utility\Log;

/**
 * LogTest class
 */
class LogTest extends TestCase {
	/**
	 * File path to store log file.
	 *
	 * @var string
	 */
	private const CUSTOM_FILE_PATH = LOGS . 'my_file.log';

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * testLogsStringData method
	 *
	 * @return void
	 */
	public function testLogsStringData() {
		if (file_exists(self::CUSTOM_FILE_PATH)) {
            unlink(self::CUSTOM_FILE_PATH);
        }

		$result = Log::write('It works!', 'my_file');

		$this->assertTrue($result);
		$this->assertFileExists(self::CUSTOM_FILE_PATH);
		$this->assertRegExp(
			'/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: It works!/',
			file_get_contents(self::CUSTOM_FILE_PATH)
		);

		unlink(self::CUSTOM_FILE_PATH);
	}
}
