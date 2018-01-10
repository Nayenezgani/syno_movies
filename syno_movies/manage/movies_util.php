<?php
function upload_episodes_title($iTVShowID, $sInputFileName, $bLock = false, $bTest = true) {
	$bConvertCP = false;
	$iLine = 0;
	$sLine = null;
	$bError = false;
	$aEpisodeTitle = null;
	$aEpisodesTitle = array ();
	$dbConn = null;
	$dbRs = null;
	$sSQLcmd = null;
	$iSeason = null;
	$iEpisode = null;
	$sOldTitle = null;
	$sNewTitle = null;
	$iMapperID = null;
	$iShowMapperID = null;
	$aResult = array ();
	$aReplace = array (
			"\r\n",
			"\n",
			"\r",
			"\""
	);
	$sLock = $bLock ? 'true' : 'false';

	try {
		// Detect code-page and convert to UTF-8
		$sLine = file_get_contents ( $sInputFileName );
		if ($sLine) {
			if (! mb_detect_encoding ( $sLine, 'UTF-8', true )) {
				$bConvertCP = true;
			}
		}
		// Parce CSV to array
		$handle = fopen ( $sInputFileName, 'r' );
		if ($handle) {
			while ( ($sLine = fgets ( $handle )) !== FALSE ) {
				$aEpisodeTitle = null;
				$iLine ++;
				preg_match ( '/^[Ss]?(\d{1,2})[.,;EeXx](\d{1,2})[ .,;_\-]\"?(.*)\"?$/i', $sLine, $aEpisodeTitle );
				if (count ( $aEpisodeTitle ) == 4) {
					$sNewTitle = $aEpisodeTitle [3];
					if ($bConvertCP) {
						$sNewTitle = iconv ( 'Windows-1250', 'UTF-8', $sNewTitle );
					}
					$sNewTitle = str_replace ( $aReplace, '', $sNewTitle );
					$sNewTitle = filter_var ( $sNewTitle, FILTER_SANITIZE_STRING );
					$sNewTitle = trim ( $sNewTitle );
					$aEpisodeTitle [3] = $sNewTitle;
					array_push ( $aEpisodesTitle, $aEpisodeTitle );
				} else {
					$bError = true;
					resultAddError ( $aResult, "Chyba na řádce", $iLine );
				}
			}
			fclose ( $handle );
		}

		if (! count ( $aEpisodesTitle ) || $bError) {
			resultAddError ( $aResult, 'Chyba vstupního souboru!' );
			return $aResult;
		}

		$dbConn = new PDO ( cDbConnSYNOstr, cDbConnSYNOuser, cDbConnSYNOpwd ) or die ( 'Could not connect to SYNO DB!' );
		$dbConn->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		$sSQLcmd = "SELECT mapper_id FROM tvshow WHERE id = {$iTVShowID} LIMIT 1";
		$dbRs = $dbConn->query ( $sSQLcmd );
		if ($dbRs) {
			$dbRow = $dbRs->fetch ( PDO::FETCH_NAMED );
		}
		if (! $dbRow) {
			resultAddError ( $aResult, "{$iTVShowID} - Seriál nenalezen!" );
			return false;
		}
		$iShowMapperID = $dbRow ['mapper_id'];

		foreach ( $aEpisodesTitle as $aEpisodeTitle ) {
			$dbRow = null;
			$iSeason = intval ( $aEpisodeTitle [1] );
			$iEpisode = intval ( $aEpisodeTitle [2] );
			$sNewTitle = $aEpisodeTitle [3];
			$sqlNewTitle = $dbConn->quote ( $sNewTitle );
			$iMapperID = null;
			$sOldTitle = null;

			$sSQLcmd = "SELECT mapper_id,tag_line FROM tvshow_episode WHERE tvshow_id = {$iTVShowID} AND season = {$iSeason} AND episode = {$iEpisode} LIMIT 1";
			$dbRs = $dbConn->query ( $sSQLcmd );
			if ($dbRs) {
				$dbRow = $dbRs->fetch ( PDO::FETCH_NAMED );
			}
			if (! $dbRow) {
				resultAddError ( $aResult, "{$iSeason}x{$iEpisode} - Epizoda nenalezena!" );
				continue;
			}

			$iMapperID = $dbRow ['mapper_id'];
			$sOldTitle = $dbRow ['tag_line'];

			if ($bTest || strcmp ( $sOldTitle, $sNewTitle ) == 0) {
				resultAddText ( $aResult, $iMapperID, "{$iSeason}x{$iEpisode}", "{$sOldTitle} => {$sNewTitle}" );
				continue;
			}

			$sSQLcmd = "UPDATE tvshow_episode SET tag_line = {$sqlNewTitle}, islock = {$sLock} WHERE tvshow_id = '{$iTVShowID}' AND season = {$iSeason} AND episode = {$iEpisode}";
			$dbRs = $dbConn->exec ( $sSQLcmd );
			if (! $dbRs) {
				resultAddError ( $aResult, "{$iSeason}x{$iEpisode} {$sOldTitle} => {$sNewTitle} - Update DB error", $iMapperID );
				continue;
			}

			try {
				$oNFOitem = null;
				$oNFOitem = new nfoItem ( $iMapperID, $dbConn );
				$oNFOitem->bTestMode = cGlobalDevelMode;
				$oNFOitem->iSynoUserID = cSYNOuserID;
				if ($oNFOitem->ExportItem ( true )) {
					resultAddText ( $aResult, $iMapperID, "{$iSeason}x{$iEpisode}", "{$sOldTitle} => {$sNewTitle} - OK" );
				} else {
					resultAddError ( $aResult, "{$iSeason}x{$iEpisode} {$sOldTitle} => {$sNewTitle} - Export NFO error", $iMapperID );
				}
			} catch ( Exception $e ) {
				resultAddError ( $aResult, "{$iSeason}x{$iEpisode} {$sOldTitle} => {$sNewTitle} - " . $e->getMessage (), $iMapperID );
			}
		}

		if (! $bTest) {
			$oNFOitem = null;
			$oNFOitem = new nfoItem ( $iShowMapperID, $dbConn );
			$oNFOitem->bTestMode = cGlobalDevelMode;
			$oNFOitem->iSynoUserID = cSYNOuserID;
			if ($oNFOitem->ExportItem ( true )) {
				resultAddText ( $aResult, $iMapperID, "Show", "Export NFO - OK" );
			} else {
				resultAddError ( $aResult, "Show - Export NFO error", $iShowMapperID );
			}
		}
	} catch ( Exception $e ) {
		if ($dbRs) {
			$dbRs = null;
		}
		if ($dbConn) {
			$dbConn = null;
		}
		die ( 'Error# ' . $e->getMessage () );
	}

	return $aResult;
} // upload_episodes_title
