<?php
require_once ('../config.php');
if ($this->bTestMode) {
	$sRootPath = cSYNOvolumeTest;
} else {
	$sRootPath = cSYNOvolume;
}
$sInputPath = '';
$bFolderOnly = false;
$aPathEntries = array ();
if (isset ( $_POST ['input_path'] )) {
	$sInputPath = trim ( filter_input ( INPUT_POST, 'input_path', FILTER_SANITIZE_STRING ) );
}
if (isset ( $_POST ['folderOnly'] )) {
	$bFolderOnly = filter_input ( INPUT_POST, 'folderOnly', FILTER_VALIDATE_BOOLEAN );
}
if (! strlen ( $sInputPath )) {
	exit ();
}
$sPathMask = $sRootPath . DIRECTORY_SEPARATOR . $sInputPath;
if (is_dir ( $sPathMask )) {
	$sTargetPath = $sPathMask;
} elseif (is_file ( $sPathMask )) {
	exit ();
} else {
	$sTargetPath = dirname ( $sPathMask );
}
$sTargetPath = realpath ( $sTargetPath );
if (substr_compare ( $sTargetPath, $sRootPath, 0, strlen ( $sRootPath ), false ) != 0) {
	exit ();
}
$aDirContent = scandir ( $sTargetPath, SCANDIR_SORT_ASCENDING );
if (! $aDirContent) {
	exit ();
}
foreach ( $aDirContent as $sDirEntry ) {
	if ($sDirEntry == '.' || $sDirEntry == '..') {
		continue;
	}
	$sFullPath = $sTargetPath . DIRECTORY_SEPARATOR . $sDirEntry;
	if ($bFolderOnly && ! is_dir ( $sFullPath )) {
		continue;
	}
	if (substr_compare ( $sFullPath, $sPathMask, 0, strlen ( $sPathMask ), true ) == 0) {
		$sFullPath = str_replace ( $sRootPath . DIRECTORY_SEPARATOR, '', $sFullPath );
		array_push ( $aPathEntries, $sFullPath );
	}
}
if (! count ( $aPathEntries )) {
	exit ();
}
?>
<ul>
<?php
foreach ( $aPathEntries as $sDirEntry ) {
	if (DIRECTORY_SEPARATOR == '\\') {
		$sDirEntryJS = str_replace ( DIRECTORY_SEPARATOR, '\\\\', $sDirEntry );
	} else {
		$sDirEntryJS = $sDirEntry;
	}
	echo ("<li class='pathSuggestItem' onclick='suggest_fill(\"{$sDirEntryJS}\");'>{$sDirEntry}</li>");
}
?>
</ul>