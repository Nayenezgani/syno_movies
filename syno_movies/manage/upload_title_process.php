<?php
require_once ('../config.php');
include_once ('nfoItem.php');
include_once ('util.php');
include_once ('movies_util.php');

$bTest = false;
$bLock = false;
$iTVShowID = null;
$sInputFile = null;
$sLine = null;
$aResult = null;
$aResultFull = array ();

if (isset ( $_POST ['TVshow'] )) {
	$iTVShowID = filter_input ( INPUT_POST, 'TVshow', FILTER_VALIDATE_INT );
}

if (isset ( $_FILES ["episodesTitleFile"] ["tmp_name"] )) {
	$sInputFileName = $_FILES ["episodesTitleFile"] ["tmp_name"];
}

if (isset ( $_POST ['lock'] )) {
	$bLock = filter_input ( INPUT_POST, 'lock', FILTER_VALIDATE_BOOLEAN );
}

if (isset ( $_POST ['test'] )) {
	$bTest = filter_input ( INPUT_POST, 'test', FILTER_VALIDATE_BOOLEAN );
}

if (! $iTVShowID || ! $sInputFileName) {
	echo ('Chyba vstupních dat');
	exit ();
}

$aResult = upload_episodes_title ( $iTVShowID, $sInputFileName, $bLock, $bTest );
if (count ( $aResult )) {
	resultAddSeparator ( $aResultFull );
	$aResultFull = array_merge ( $aResultFull, $aResult );
}

/* Write export result */
resultOutput ( $aResultFull );
