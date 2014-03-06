<?php
App::import('Vendor', 'Tools.SpreadsheetExcelReader', array('file' => 'SpreadsheetExcelReader' . DS . 'SpreadsheetExcelReader.php'));
if (!class_exists('SpreadsheetExcelReader')) {
	throw new CakeException('Cannot load SpreadsheetExcelReader class');
}

/**
 * Wrapper of the old excel reader for cake.
 *
 * Just include this class via
 * - App::uses('ExcelReaderLib', 'Tools.Lib');
 *
 * You can use
 * - read($file) to read files
 * - readFromBlob($content) to read binary data
 *
 * Then
 * - $array = $this->ExcelReader->dumpToArray($optionalPage);
 *
 * See the test cases for details.
 *
 * @author Mark Scherer
 * @license MIT
 */
class ExcelReaderLib extends SpreadsheetExcelReader {
}