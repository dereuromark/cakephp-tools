<?php
if (!isset($svnFile)) {
	$svnFile = APP . '.svn/entries';
}

if (file_exists($svnFile) && ($svn = File($svnFile))) {
	//die(returns($svn));
	$svnrev = $svn[3];
	$lastChange = trim($svn[9]);
	$lastUser = trim($svn[11]);

	if (isset($version) && $version === false || Configure::read('debug') > 0) {
		# display the revision right away
		$versionText = 'Rev. ' . $svnrev . ' (' . h($lastUser) . ' - ' . $this->Datetime->niceDate($lastChange, FORMAT_NICE_YMDHM) . ')';
	} else {
		# in productive mode we want to display a harmless looking version number
		if (strlen($svnrev) > 3) {
			$v = substr($svnrev, 0, strlen($svnrev) - 3) . '.' . substr($svnrev, -3, 1) . '.' . substr($svnrev, -2, 1);
		} elseif (strlen($svnrev) === 3) {
			$v = '0.' . substr($svnrev, -3, 1) . '.' . substr($svnrev, -2, 1);
		} else {
			$v = '0.0.' . substr($svnrev, -2, 1);
		}
		$versionText = 'Version ' . $v;
	}

?>

<div class="svn-revision">
<?php
	echo $versionText;
?>
</div>

<?php
}
if (isset($svn)) {
	unset($svn);
}
?>
