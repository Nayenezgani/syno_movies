<?php

function export_nfo_start($bReplace, $dDateFrom, $bNotify = false, $bClean = false, $sPathFilter = null)
{

	$dbConn = null;
	$dbRs = null;
	$dbRow = null;
	$oNFOitem = null;
	$sCmd = '';
	$sWhere = null;
	$sWhereF = null;
	$aShow = array();
	$sKey = null;
	$aNotify = array();
	$path_parts = null;
	$aResult = array();
	$fType = '';

	if ($dDateFrom) {
		$sDateFrom = date('Y-m-d H:i:s', $dDateFrom);
		if (!$sWhere) {
			$sWhere = ' WHERE ';
		} else {
			$sWhere = $sWhere . ' AND ';
		}
		$sWhere = $sWhere . " ( create_date >= '{$sDateFrom}' OR modify_date >= '{$sDateFrom}' ) ";
	}

	if ($sPathFilter) {
		if (!$sWhere) {
			$sWhereF = ' WHERE ';
		} else {
			$sWhereF = $sWhere . ' AND ';
		}
		$sWhereF = $sWhereF . " LOWER(path) LIKE '%{$sPathFilter}%' ";
	} else {
		$sWhereF = $sWhere;
	}

	try {
		// Connect to DB
		$dbConn = new PDO(cDbConnSYNOstr, cDbConnSYNOuser, cDbConnSYNOpwd) or die('Could not connect to DB!');
		$dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// Get movies
		$sCmd = "SELECT mapper_id,path FROM video_file {$sWhereF}";
		$dbRs = $dbConn->query($sCmd);
		if ($dbRs) {
			while ($dbRow = $dbRs->fetch(PDO::FETCH_NAMED)) {
				if (!$bReplace && nfoItem::checkNFOexist($dbRow['path'])) {
					continue;
				}
				try {
					$oNFOitem = null;
					$oNFOitem = new nfoItem($dbRow['mapper_id'], $dbConn);
					$oNFOitem->bTestMode = cGlobalDevelMode;
					$oNFOitem->iSynoUserID = cSYNOuserID;
					if (cSYNOcollectionMask) {
						$oNFOitem->setPathCollectionMask(cSYNOcollectionMask);
					}
					if ($oNFOitem->ExportItem($bReplace)) {
						if ($oNFOitem->getItemType() == nfoItemType::Movie) {
							$fType = 'Movie';
						} elseif ($oNFOitem->getItemType() == nfoItemType::Episode) {
							$fType = 'Episode';
						} else {
							$fType = '';
						}
						resultAddText($aResult, $oNFOitem->getMapperID(), $fType, $oNFOitem->toString());
						if ($oNFOitem->getItemType() == nfoItemType::Episode) {
							$sKey = (string) $oNFOitem->getShowMapperID();
							$aShow[$sKey] = true;
						}

						if ($bNotify) {
							$path_parts = pathinfo($oNFOitem->getPath());
							if ($path_parts) {
								$sKey = $path_parts['dirname'];
							} else {
								$sKey = null;
							}
							if ($sKey) {
								$aNotify[$sKey] = $sKey;
							}
						}
					}
				} catch (Exception $e) {
					if ($oNFOitem) {
						resultAddError($aResult, $e->getMessage(), $oNFOitem->getMapperID());
					} else {
						resultAddError($aResult, $e->getMessage());
					}
				}
			}
			$dbRs = null;
		} else {
			throw new Exception('SQL error!');
		}

		// Get show
		if (!$sPathFilter) {
			$sCmd = "SELECT mapper_id,title FROM tvshow {$sWhere}";
			$dbRs = $dbConn->query($sCmd);
			if (!$dbRs) {
				throw new Exception('SQL error!');
			}
			while ($dbRow = $dbRs->fetch(PDO::FETCH_NAMED)) {
				$sKey = (string) $dbRow['mapper_id'];
				if (!array_key_exists($sKey, $aShow)) {
					$aShow[$sKey] = $bReplace;
				}
			}
			$dbRs = null;
		}

		// Show and Show for exported episode
		foreach ($aShow as $iMapperID => $bShowReplace) {
			try {
				$oNFOitem = null;
				$oNFOitem = new nfoItem($iMapperID, $dbConn);
				$oNFOitem->bTestMode = cGlobalDevelMode;
				$oNFOitem->iSynoUserID = cSYNOuserID;
				if ($oNFOitem->ExportItem($bShowReplace)) {
					resultAddText($aResult, $oNFOitem->getMapperID(), 'TV Show', $oNFOitem->toString());
					if ($bNotify) {
						$path_parts = pathinfo($oNFOitem->getNfoPath());
						if ($path_parts) {
							$sKey = $path_parts['dirname'];
						} else {
							$sKey = null;
						}
						if ($sKey) {
							$aNotify[$sKey] = $sKey;
						}
					}
				}
			} catch (Exception $e) {
				if ($oNFOitem) {
					resultAddError($aResult, $e->getMessage(), $oNFOitem->getMapperID());
				} else {
					resultAddError($aResult, $e->getMessage());
				}
			}
		}

		// Close DB connection
		if ($dbRs) {
			$dbRs = null;
		}
		if ($dbConn) {
			$dbConn = null;
		}

		// Notify KODI
		if ($bNotify && count($aNotify) > 0) {
			$sResult = notifyKodiSCAN($aNotify);
			if ($sResult == 'OK') {
				resultAddText($aResult, null, "KODI Notify", "{$sResult}");
			} else {
				resultAddError($aResult, "KODI Notify # {$sResult}");
			}
		}

		// Clean KODI
		if ($bClean) {
			$sResult = notifyKodiCLEAN();
			if ($sResult == 'OK') {
				resultAddText($aResult, null, "KODI Clean", $sResult);
			} else {
				resultAddError($aResult, "KODI Clean # {$sResult}");
			}
		}
	} catch (Exception $e) {
		if ($dbRs) {
			$dbRs = null;
		}
		if ($dbConn) {
			$dbConn = null;
		}
		die('Error# ' . $e->getMessage());
	}

	return $aResult;
} // export_nfo_start
