<?php
App::import('Vendor', 'Tools.SpreadsheetExcelReader', array('file' => 'SpreadsheetExcelReader' . DS . 'SpreadsheetExcelReader.php'));
if (!class_exists('SpreadsheetExcelReader')) {
	throw new CakeException('Cannot load SpreadsheetExcelReader class');
}

/**
 * Wrapper of the old excel reader for cake
 *
 * @author Mark Scherer
 * @license MIT
 * 2013-01-24 ms
 */
class ExcelReaderLib extends SpreadsheetExcelReader {

}