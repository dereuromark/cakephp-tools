<?php
App::uses('FileLib', 'Tools.Utility');

/**
 *
 */
class FileLibTest extends CakeTestCase {

	/**
	 * Test method
	 *
	 * @return void
	 */
	public function testReadCsv1() {
		$handler = new FileLib(TMP . 'test.txt', true);

		$pre = '"First", "Last Name", "Email"' . NL . '"Example", "Firsty", "test@test.com"'; //.NL.'"Next", "Secondy", "again@test.com"'

		$handler->write($pre);
		$handler->close();

		$handler2 = new FileLib(TMP . 'test.txt', true);

		$is = $handler2->readCsv(1024, ',', '"');
		$expected = array(array(
				'First',
				'Last Name',
				'Email'), array(
				'Example',
				'Firsty',
				'test@test.com'));

		$status = $this->assertEquals($expected, $is);
		$this->_printArrays($status, $is, $expected, $pre);
	}

	/**
	 * FileLibTest::testReadCsv2() with umlauts
	 *
	 * @return void
	 */
	public function testReadCsv2() {
		$handler = new FileLib(TMP . 'test.txt', true);

		$pre = '\'First\', \'Last Name\', \'Email\'' . NL . '\'Example Äs\', \'Firsty üs\', \'test@test.com sß\'';

		$handler->write($pre);
		$handler->close();

		$handler2 = new FileLib(TMP . 'test.txt', true);

		$is = $handler2->readCsv(1024, ',', '\'');
		$expected = array(array(
				'First',
				'Last Name',
				'Email'
			), array(
				'Example Äs',
				'Firsty üs',
				'test@test.com sß'
			)
		);

		$status = $this->assertEquals($expected, $is);
		$this->_printArrays($status, $is, $expected, $pre);
	}

	/**
	 * Test method
	 *
	 * @return void
	 */
	public function testReadWithTags1() {
		$handler = new FileLib(TMP . 'test.txt', true);

		$pre = '<h1>Header</h1><p><b>Bold Text</b></p><hr />Between to lines<hr></p><h4>Some Subheader</h4>Some more text at the end';

		$handler->write($pre);
		$handler->close();

		$handler2 = new FileLib(TMP . 'test.txt', true);

		$is = $handler2->readWithTags();
		$expected = '<h1>Header</h1><p><b>Bold Text</b></p>Between to lines</p>Some SubheaderSome more text at the end';

		$status = $this->assertEquals($expected, $is);
		$this->_printArrays($status, $is, $expected, $pre);
	}

	/**
	 * Test csv file generation from array
	 */
	public function testWriteCsv() {
		$handler = new FileLib(TMP . 'test.csv', true);
		$array = array(
			array(
				'header1',
				'header2',
				'header3'),
			array(
				'v1a',
				'v1b',
				'v1c'),
			array(
				'v2a',
				'v2b',
				'v2c'),
			);

		$res = $handler->writeCsv($array);
		$this->assertTrue($res);

		$handler = new FileLib(TMP . 'test.csv', true);
		$res = $handler->readCsv(1024);
		$this->assertEquals($array, $res);
	}

	/**
	 * Test method
	 *
	 * @return void
	 */
	public function testReadWithPattern1() {
		$handler = new FileLib(TMP . 'test.txt', true);

		$pre = 'First' . TB . 'LastName' . TB . 'Email' . NL . 'Example' . TB . 'Firsty' . TB . 'test@test.com';
		//$pre = 'First, Last Name, Email'.PHP_EOL.'Example, Firsty, test@test.com';
		//$pre = 'First-LastName-Email'.NL.'Example-Firsty-test@test.com';

		$handler->write($pre);
		$handler->close();

		$handler2 = new FileLib(TMP . 'test.txt', true);

		$is = $handler2->readWithPattern('%s' . TB . '%s' . TB . '%s');
		$expected = array(array(
				'First',
				'LastName',
				'Email'), array(
				'Example',
				'Firsty',
				'test@test.com'));

		$status = $this->assertEquals($expected, $is);
		$this->_printArrays($status, $is, $expected, $pre);
	}

	public function testReadWithPattern2() {
		$handler = new FileLib(TMP . 'test.txt', true);

		$pre = '2-33-44' . NL . '5-66-77';
		//$pre = 'First, Last Name, Email'.PHP_EOL.'Example, Firsty, test@test.com';

		$handler->write($pre);
		$handler->close();

		$handler2 = new FileLib(TMP . 'test.txt', true);

		$is = $handler2->readWithPattern('%d-%d-%d');
		$expected = array(array(
				'2',
				'33',
				'44'), array(
				'5',
				'66',
				'77'));

		$status = $this->assertEquals($expected, $is);
		$this->_printArrays($status, $is, $expected, $pre);
	}

	public function testTransfer() {
		$handler = new FileLib(TMP . 'test.txt', true);
		$pre = '"First", "Last Name", "Email"' . NL . '"Example", "Firsty", "test@test.com"' . NL . '"Next", "Secondy", "again@test.com"';

		$handler->write($pre);
		$handler->close();

		$handler2 = new FileLib(TMP . 'test.txt', true);

		$is = $handler2->readCsv(1024, ',', '"');

		$is = $handler2->transfer($is);
		//pr($is);
		$expected = array(array(
				'first' => 'Example',
				'last_name' => 'Firsty',
				'email' => 'test@test.com'), array(
				'first' => 'Next',
				'last_name' => 'Secondy',
				'email' => 'again@test.com'));
		$this->assertEquals($expected, $is);
	}

	public function testTransferWithManualKeys() {
		$handler = new FileLib(TMP . 'test.txt', true);
		$pre = '"First", "Last Name", "Email"' . NL . '"Example", "Firsty", "test@test.com"' . NL . '"Next", "Secondy", "again@test.com"';

		$handler->write($pre);
		$handler->close();

		$handler2 = new FileLib(TMP . 'test.txt', true);

		$is = $handler2->readCsv(1024, ',', '"');
		array_shift($is);
		$is = $handler2->transfer($is, array('keys' => array(
				'X',
				'Y',
				'Z'), 'preserve_keys' => true));
		//pr($is);
		$expected = array(array(
				'X' => 'Example',
				'Y' => 'Firsty',
				'Z' => 'test@test.com'), array(
				'X' => 'Next',
				'Y' => 'Secondy',
				'Z' => 'again@test.com'));
		$this->assertEquals($expected, $is);
	}

	public function testReadCsvWithEmpty() {
		$handler = new FileLib(TMP . 'test.txt', true);
		$pre = '"First", "Last Name", "Email"' . NL . ',,' . NL . '"Next", "Secondy", "again@test.com"';

		$handler->write($pre);
		$handler->close();

		$handler2 = new FileLib(TMP . 'test.txt', true);

		$is = $handler2->readCsv(1024, ',', '"', 'rb', false, true);
		array_shift($is);
		$is = $handler2->transfer($is, array('keys' => array(
				'X',
				'Y',
				'Z'), 'preserve_keys' => true));
		//pr($is);
		$expected = array(array(
				'X' => 'Next',
				'Y' => 'Secondy',
				'Z' => 'again@test.com'));
		$this->assertEquals($expected, $is);
	}

	/**
	 * Test BOM
	 *
	 * @return void
	 */
	public function testBOM() {
		$folder = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'txt' . DS;
		$fileOK = $folder . 'ok.php';
		$fileNOK = $folder . 'nok.php';
		$result = FileLib::hasByteOrderMark(file_get_contents($fileOK));
		$this->assertFalse($result);

		$result = FileLib::hasByteOrderMark(file_get_contents($fileNOK));
		$this->assertTrue($result);

		$tmpFileNOK = TMP . 'nok.php';
		copy($fileNOK, $tmpFileNOK);
		$result = FileLib::removeByteOrderMark(file_get_contents($tmpFileNOK));
		//file_put_contents($tmpFileNOK, $result);
		//$result = FileLib::hasByteOrderMark(file_get_contents($tmpFileNOK));
		$result = FileLib::hasByteOrderMark($result);
		$this->assertFalse($result);
		unlink($tmpFileNOK);
	}

	/** Helper Functions **/

	public function _printArrays($status, $is, $expected, $pre = null) {
		if (!isset($_GET['show_passes']) || !$_GET['show_passes']) {
			return false;
		}

		if ($pre !== null) {
			//echo 'pre:';
			//pr($pre);
		}
		//echo 'is:';
		//pr($is);
		if (!$status) {
			//echo 'expected:';
			//pr($expected);
		}
	}

}
