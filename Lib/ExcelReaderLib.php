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
 */
class ExcelReaderLib extends SpreadsheetExcelReader {

}