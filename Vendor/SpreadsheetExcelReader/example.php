<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once 'SpreadsheetExcelReader.php';
$reader = new SpreadsheetExcelReader("example.xls");

// or
$reader = new SpreadsheetExcelReader();
$fileContent = file_get_contents("example.xls");
$reader->readFromBlob($fileContent);
?>
<html>
<head>
<style>
table.excel {
	border-style:ridge;
	border-width:1;
	border-collapse:collapse;
	font-family:sans-serif;
	font-size:12px;
}
table.excel thead th, table.excel tbody th {
	background:#CCCCCC;
	border-style:ridge;
	border-width:1;
	text-align: center;
	vertical-align:bottom;
}
table.excel tbody th {
	text-align:center;
	width:20px;
}
table.excel tbody td {
	vertical-align:bottom;
}
table.excel tbody td {
	padding: 0 3px;
	border: 1px solid #EEEEEE;
}
</style>
</head>

<body>
<table class="excel">
<?php
	$table = $reader->dumpToArray();
	foreach ($table as $row) {
		echo '<tr>';
		foreach ($row as $field) {
			echo '<td>' . $field . '</td>';
		}
		echo '</tr>';
	}
?>
</table>
</body>
</html>
