<?php
require_once ('../config.php');
include_once ('nfoItem.php');
include_once ('util.php');
include_once ('nfo_util.php');
include_once ('kodi_util.php');

$bWatched = false;
$bReplace = false;
$bNotify = false;
$bClean = false;
$dDateFrom = null;
$dWatchedDateFrom = null;
$sPathFilter = null;
$sLine = null;
$aResult = null;
$aResultFull = array ();

if (isset ( $_GET ['batch'] )) {
	$bBatch = filter_input ( INPUT_GET, 'batch', FILTER_VALIDATE_BOOLEAN );
}

if (isset ( $_POST ['watched'] )) {
	$bWatched = filter_input ( INPUT_POST, 'watched', FILTER_VALIDATE_BOOLEAN );
}

if (isset ( $_POST ['replace'] )) {
	$bReplace = filter_input ( INPUT_POST, 'replace', FILTER_VALIDATE_BOOLEAN );
}

if (isset ( $_POST ['notify'] )) {
	$bNotify = filter_input ( INPUT_POST, 'notify', FILTER_VALIDATE_BOOLEAN );
}

if (isset ( $_POST ['clean'] )) {
	$bClean = filter_input ( INPUT_POST, 'clean', FILTER_VALIDATE_BOOLEAN );
}

if (isset ( $_POST ['path_filter'] )) {
	$sPathFilter = trim ( strtolower ( filter_input ( INPUT_POST, 'path_filter', FILTER_SANITIZE_STRING ) ) );
}

if (isset ( $_POST ['from_date'] )) {
	$dDateFrom = strtotime ( $_POST ['from_date'] );
	if ($dDateFrom == FALSE || ! is_int ( $dDateFrom )) {
		$dDateFrom = null;
	}
}

if (isset ( $_POST ['watched_date'] )) {
	$dWatchedDateFrom = strtotime ( $_POST ['watched_date'] );
	if ($dWatchedDateFrom == FALSE || ! is_int ( $dWatchedDateFrom )) {
		$dWatchedDateFrom = null;
	}
}

if ($bBatch) {
	$bWatched = true;
	$bNotify = true;
	$bReplace = false;
	$bClean = false;
	$dDateFrom = null;
	$sPathFilter = null;
	$dWatchedDateFrom = null;
}

if (! $dDateFrom && ! $sPathFilter) {
	$dDateFrom = strtotime ( "-14 days" );
}

if (! $dWatchedDateFrom && $bWatched) {
	$dWatchedDateFrom = strtotime ( "-30 days" );
}

if ($bWatched) {
	$aResult = sync_watched ( $dWatchedDateFrom );
	if (count ( $aResult )) {
		resultAddSeparator ( $aResultFull );
		$aResultFull = array_merge ( $aResultFull, $aResult );
	}
}

$aResult = export_nfo_start ( $bReplace, $dDateFrom, $bNotify, $bClean, $sPathFilter );
if (count ( $aResult )) {
	resultAddSeparator ( $aResultFull );
	$aResultFull = array_merge ( $aResultFull, $aResult );
}

/* Write export result */
resultOutput ( $aResultFull );
