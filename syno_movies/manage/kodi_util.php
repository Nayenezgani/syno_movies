<?php

class KodiJSON implements JsonSerializable {
	public $jsonrpc = "2.0";
	public $method = null;
	public $params;
	public $id = "1";

	public function KodiJSON(string $method, string $id = null) {

		$this->method = $method;
		if($id) {
			$this->id = $id;
		}

	}

	public function __toString(): string {

		return json_encode ( $this );

	}

	public function jsonSerialize(): array {

		$vars = array_filter ( get_object_vars ( $this ), function ($item) {
			// Keep only not-NULL values
			return ! is_null ( $item );
		} );

			return $vars;

	}

}

function sync_watched($dWatchedDateFrom = null) {

	$dbConn = null;
	$dbRs = null;
	$dbRow = null;
	$sSQLcmd = null;
	$sCmdSyno = null;
	$iSynoUserID = cSYNOuserID;
	$sWatchedDateFrom = date ( 'Y-m-d H:i:s', $dWatchedDateFrom );
	$aWatched = null;
	$aFile = null;
	$sFileName = null;
	$dWatched = null;
	$iMapperID = null;
	$iSynoFileID = null;
	$iPosition = null;
	$oNFOitem = null;
	$aResult = array ();

	try {

		// Connect to KODI
		$dbConn = new PDO ( cDbConnKODIstr, cDbConnKODIuser, cDbConnKODIpwd ) or die ( 'Could not connect to KODI DB!' );
		$dbConn->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		// Get watched
		$sCmdKodi = <<<EOT
SELECT
	( CONCAT( path.strPath, files.strFilename ) ) AS fileName,
	files.lastPlayed,
	files.playCount
FROM files
INNER JOIN path
	ON path.idPath = files.idPath
WHERE files.playCount > 0
  AND files.dateAdded IS NOT NULL
  AND lastPlayed >= '{$sWatchedDateFrom}';
EOT;
		$sSQLcmd = $sCmdKodi;
		$dbRs = $dbConn->query ( $sSQLcmd );
		if (! $dbRs) {
			throw new Exception ( 'SQL error!' );
		}
		$aWatched = $dbRs->fetchAll ( PDO::FETCH_NAMED );
		if (! $aWatched) {
			throw new Exception ( 'KODI get watched error!' );
		}

		// Close DB connection
		if ($dbRs) {
			$dbRs = null;
		}
		if ($dbConn) {
			$dbConn = null;
		}

		$dbConn = new PDO ( cDbConnSYNOstr, cDbConnSYNOuser, cDbConnSYNOpwd ) or die ( 'Could not connect to SYNO DB!' );
		$dbConn->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		// Set not existing watch status
		foreach ( $aWatched as $aFile ) {
			$dbRow = null;
			$sFileName = str_replace ( cKODIsrcPath, cSYNOvolume, $aFile['fileName'] );
			$sqlFileName = $dbConn->quote ( $sFileName );
			$dWatched = $aFile['lastPlayed'];

			$sSQLcmd = <<<EOT
SELECT id AS video_file_id, mapper_id, duration AS position
FROM video_file
WHERE path = {$sqlFileName}
	AND NOT EXISTS ( SELECT id FROM watch_status WHERE uid = {$iSynoUserID} AND video_file_id = video_file.id );
EOT;
			$dbRs = $dbConn->query ( $sSQLcmd );
			if ($dbRs) {
				$dbRow = $dbRs->fetch ( PDO::FETCH_NAMED );
			}
			if (! $dbRow) {
				continue;
			}
			$iMapperID = $dbRow['mapper_id'];
			$iSynoFileID = $dbRow['video_file_id'];
			$iPosition = $dbRow['position'];

			$sCmdSyno = <<<EOT
INSERT INTO watch_status (uid, video_file_id, mapper_id, position, create_date, modify_date)
	VALUES ({$iSynoUserID}, {$iSynoFileID}, {$iMapperID}, {$iPosition}, '{$dWatched}', '{$dWatched}');
EOT;
			$sSQLcmd = $sCmdSyno;
			$dbRs = $dbConn->exec ( $sSQLcmd );
			if (! $dbRs) {
				// Neni chyba, zaznam uz existuje
				continue;
			}
			try {
				$oNFOitem = null;
				$oNFOitem = new nfoItem ( $iMapperID, $dbConn );
				$oNFOitem->bTestMode = cGlobalDevelMode;
				$oNFOitem->iSynoUserID = cSYNOuserID;
				if (cSYNOcollectionMask) {
					$oNFOitem->setPathCollectionMask ( cSYNOcollectionMask );
				}
				if ($oNFOitem->ExportItem ( true )) {
					resultAddText ( $aResult, $iMapperID, "WATCHED Sync #", "{$sFileName} - OK" );
				} else {
					resultAddError ( $aResult, "WATCHED Sync #", "{$sFileName} - Export NFO error", $iMapperID );
				}
			} catch ( Exception $e ) {
				resultAddError ( $aResult, "WATCHED Sync #", "{$sFileName} - " . $e->getMessage (), $iMapperID );
			}
		}
	} catch ( Exception $e ) {
		if ($dbRs) {
			$dbRs = null;
		}
		if ($dbConn) {
			$dbConn = null;
		}
		echo ($sSQLcmd);
		die ( 'Error# ' . $e->getMessage () );
	}

	return $aResult;

}

// sync_watched
function notifyKodiSCAN($aPath) {

	$sResult = '';
	$url = cKODIurl . "/jsonrpc?request";

	$oKodiJSON = new KodiJSON ( "VideoLibrary.Scan", "kodi_util_scan" );

	if (count ( $aPath ) == 1) {
		$oKodiJSON->params = [
				"directory" => cKODIsrcPath . $aPath[0] . '/'
		];
	}

	$sKodiJSON = (string)$oKodiJSON;
	$opts = array (
			'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json\r\n' . "Content-Length: " . strlen($sKodiJSON) . "\r\n",
					'content' => $sKodiJSON
			)
	);
	$context = stream_context_create ( $opts );

	$result = file_get_contents ( $url, false, $context );
	if (! $result) {
		$sResult = nfoItem::getLastErrorMessage ();
		return $sResult;
	}

	$sPattern = '/"result":"(.*)"\}/i';
	$aMatch = null;
	if (preg_match ( $sPattern, $result, $aMatch )) {
		if ($aMatch[1] == 'OK') {
			$sResult = 'OK';
		} else {
			$sResult = 'Error';
		}
	} else {
		$sResult = 'Error';
	}

	return $sResult;

}

// notifyKodiSCAN
function notifyKodiCLEAN() {

	$sResult = '';
	$url = cKODIurl . "/jsonrpc";

	$oKodiJSON = new KodiJSON ( "VideoLibrary.Clean", "kodi_util_clean" );
	$sKodiJSON = (string)$oKodiJSON;
	$opts = array (
			'http' => array (
					'method' => 'POST',
					'header' => 'Content-type: application/json\r\n' . "Content-Length: " . strlen($sKodiJSON) . "\r\n",
					'content' => $sKodiJSON
			)
	);
	$context = stream_context_create ( $opts );

	$result = file_get_contents ( $url, false, $context );
	if (! $result) {
		$sResult = nfoItem::getLastErrorMessage ();
		return $sResult;
	}

	$sPattern = '/"result":"(.*)"\}/i';
	$aMatch = null;
	if (preg_match ( $sPattern, $result, $aMatch )) {
		if ($aMatch[1] == 'OK') {
			$sResult = 'OK';
		} else {
			$sResult = 'Error';
		}
	} else {
		$sResult = 'Error';
	}

	return $sResult;

} // notifyKodiCLEAN
