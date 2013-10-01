<?php
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('ExcelReaderLib', 'Tools.Lib');

/**
 * Testing basic functions
 */
class ExcelReaderLibTest extends MyCakeTestCase {

	public $ExcelReader;

	public function setUp() {
		parent::setUp();

		$this->ExcelReader = new ExcelReaderLib();
	}

	/**
	 * Read binary data directly
	 */
	public function readFromBlob() {
		$path = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'xls' . DS;
		$file = $path . 'excel_2000.xls';
		$content = file_get_contents($file);

		$this->ExcelReader->readFromBlob($content);
		$sheets = $this->ExcelReader->sheets();
		$this->assertSame(3, $sheets);

		$array = $this->ExcelReader->dumpToArray();
		$expected = array(
			array('A', 'B', 'C'),
			array('titleA', 'titleB', 'titleC'),
		);
		$this->assertSame($expected, $array);
	}

	/**
	 * Currently the ExcelReader only works with Excel97/2000/XP etc
	 * Not with any versions prio to that
	 */
	public function testRead() {
		$path = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'xls' . DS;
		$file = $path . 'excel_2000.xls';

		$this->ExcelReader->read($file);

		$sheets = $this->ExcelReader->sheets();
		$this->assertSame(3, $sheets);

		$array = $this->ExcelReader->dumpToArray();
		$expected = array(
			array('A', 'B', 'C'),
			array('titleA', 'titleB', 'titleC'),
		);
		$this->assertSame($expected, $array);

		$array = $this->ExcelReader->dumpToArray(1);
		//debug($array);
		$expected = array(
			array('A1', '0'),
			array('A2', '1'),
			array('A3', '2'),
			array('A4', '3'),
		);
		$this->assertSame($expected, $array);

		$array = $this->ExcelReader->dumpToArray(2);
		$expected = array(
		);
		$this->assertSame($expected, $array);

		$file = $path . 'excel_1995.xls';
		//TODO
	}

}
