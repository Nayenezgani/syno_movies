<?php
function resultAdd(&$aResult, $sText) {
	array_push ( $aResult, $sText );
	return;
} // resultAdd
function resultAddText(&$aResult, $iID = null, $sKey = null, $sText) {
	$sId = null;
	$sKeyText = null;
	$sLine = '';

	if ($iID) {
		$sId = " [ID#{$iID}]";
	}

	if ($sKey) {
		$sKeyText = "{$sKey}: ";
	}

	$sLine .= "<div>{$sKeyText}{$sText}{$sId}</div>";

	array_push ( $aResult, $sLine );

	return;
} // resultAddText
function resultAddError(&$aResult, $sText, $iID = null) {
	$sId = null;
	$sLine = '';

	if ($iID) {
		$sId = "[ID#{$iID}] ";
	}

	$sLine = "<div class='error'>Error: {$sId}{$sText}</div>";

	array_push ( $aResult, $sLine );

	return;
} // exportAddResultError
function resultAddSeparator(&$aResult) {
	if (count ( $aResult )) {
		array_push ( $aResult, '<hr />' );
	}
	return;
} // resultAddSeparator
function resultOutput(&$aResult) {
	$bErr = false;
	$sLine = null;

	foreach ( $aResult as $sLine ) {
		if (strpos ( $sLine, "class='error'" )) {
			$bErr = true;
			break;
		}
	}

	echo ('<hr />');

	if ($bErr) {
		echo ('<div class="result_message_error">Finished with error</div>');
	} else {
		echo ('<div class="result_message_ok">Finished</div>');
	}

	echo ('<hr />');

	foreach ( $aResult as $sLine ) {
		echo ($sLine);
	}

	return;
} // resultOutput
