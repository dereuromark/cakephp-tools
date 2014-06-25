<?php
if (!isset($svnDir)) {
	$svnDir = APP;
}
$command = 'cd ' . realpath($svnDir) . ' && svn info';
$svnrev = null;
exec($command, $out);
if ($out) {
	foreach ($out as $row) {
		if (strpos($row, 'Revision:') === 0) {
			$svnrev = (int)substr($row, 9);
		}
	}
}
if (!$svnrev) {
	return;
}
?>
<span class="svn-revision">
<?php
	echo $svnrev;
?>
</span>
