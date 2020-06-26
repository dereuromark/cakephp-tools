<?php

namespace Tools\Test\Utility;

use Exception;
use Tools\TestSuite\TestCase;
use Tools\Utility\FileLog;

/**
 * FileLogTest class
 */
class FileLogTest extends TestCase {

	/**
	 * Default filename with path to use in test case.
	 *
	 * @var string
	 */
	const TEST_DEFAULT_FILENAME_STRING = 'custom_log';
	const TEST_DEFAULT_FILEPATH_STRING = LOGS . self::TEST_DEFAULT_FILENAME_STRING . '.log';

	/**
	 * Filename with path to use in string test case.
	 *
	 * @var string
	 */
	const TEST_FILENAME_STRING = 'my_file';
	const TEST_FILEPATH_STRING = LOGS . self::TEST_FILENAME_STRING . '.log';

	/**
	 * Filename with path to use in array test case.
	 *
	 * @var string
	 */
	const TEST_FILENAME_ARRAY1 = 'array_file1';
	const TEST_FILEPATH_ARRAY1 = LOGS . self::TEST_FILENAME_ARRAY1 . '.log';
	const TEST_FILENAME_ARRAY2 = 'array_file2';
	const TEST_FILEPATH_ARRAY2 = LOGS . self::TEST_FILENAME_ARRAY2 . '.log';

	/**
	 * Filename with path to use in object test case.
	 *
	 * @var string
	 */
	const TEST_FILENAME_OBJECT = 'object';
	const TEST_FILEPATH_OBJECT = LOGS . self::TEST_FILENAME_OBJECT . '.log';

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
		if (file_exists(static::TEST_FILEPATH_STRING)) {
			unlink(static::TEST_FILEPATH_STRING);
		}

		$result = FileLog::write('It works!', static::TEST_FILENAME_STRING);

		$this->assertTrue($result);
		$this->assertFileExists(static::TEST_FILEPATH_STRING);
		$this->assertRegExp(
			'/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: It works!/',
			file_get_contents(static::TEST_FILEPATH_STRING)
		);

		unlink(static::TEST_FILEPATH_STRING);
	}

	/**
	 * testLogsArray method
	 *
	 * @return void
	 */
	public function testLogsArray() {
		if (file_exists(static::TEST_FILEPATH_ARRAY1)) {
			unlink(static::TEST_FILEPATH_ARRAY1);
		}
		if (file_exists(static::TEST_FILEPATH_ARRAY2)) {
			unlink(static::TEST_FILEPATH_ARRAY2);
		}

		$result1 = FileLog::write(
			[
				'user' => [
					'id' => 1,
					'firstname' => 'John Doe',
					'email' => 'john.doe@example.com',
				],
			],
			static::TEST_FILENAME_ARRAY1
		);

		$result2 = FileLog::write(
			[
				'user' => [
					'id' => 2,
					'firstname' => 'Jane Doe',
					'email' => 'jane.doe@example.com',
				],
			],
			static::TEST_FILENAME_ARRAY2
		);

		// Assert for `TEST_FILENAME_ARRAY1`
		$this->assertTrue($result1);
		$this->assertFileExists(static::TEST_FILEPATH_ARRAY1);
		$fileContents = file_get_contents(static::TEST_FILEPATH_ARRAY1);
		$this->assertRegExp(
			'/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: Array([\s\S]*)\(([\s\S]*)[user]([\s\S]*)\[id\] => 1/',
			$fileContents
		);

		// Assert for `TEST_FILENAME_ARRAY2`
		$this->assertTrue($result2);
		$this->assertFileExists(static::TEST_FILEPATH_ARRAY2);
		$fileContents = file_get_contents(static::TEST_FILEPATH_ARRAY2);
		$this->assertRegExp(
			'/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: Array([\s\S]*)\(([\s\S]*)[user]([\s\S]*)\[id\] => 2/',
			$fileContents
		);

		unlink(static::TEST_FILEPATH_ARRAY1);
		unlink(static::TEST_FILEPATH_ARRAY2);
	}

	/**
	 * testLogsObject method
	 *
	 * @return void
	 */
	public function testLogsObject() {
		if (file_exists(static::TEST_FILEPATH_OBJECT)) {
			unlink(static::TEST_FILEPATH_OBJECT);
		}

		try {
			throw new Exception('Test', 1);
		} catch (Exception $exception) {
			// Do nothing
		}

		$result = FileLog::write($exception, static::TEST_FILENAME_OBJECT);

		$this->assertTrue($result);
		$this->assertFileExists(static::TEST_FILEPATH_OBJECT);
		$this->assertRegExp(
			'/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: Exception Object/',
			file_get_contents(static::TEST_FILEPATH_OBJECT)
		);

		unlink(static::TEST_FILEPATH_OBJECT);
	}

	/**
	 * testLogsIntoDefaultFile method
	 *
	 * @return void
	 */
	public function testLogsIntoDefaultFile() {
		if (file_exists(static::TEST_DEFAULT_FILEPATH_STRING)) {
			unlink(static::TEST_DEFAULT_FILEPATH_STRING);
		}

		$result = FileLog::write('It works with default too!');

		$this->assertTrue($result);
		$this->assertFileExists(static::TEST_DEFAULT_FILEPATH_STRING);
		$this->assertRegExp(
			'/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: It works with default too!/',
			file_get_contents(static::TEST_DEFAULT_FILEPATH_STRING)
		);

		unlink(static::TEST_DEFAULT_FILEPATH_STRING);
	}

}
