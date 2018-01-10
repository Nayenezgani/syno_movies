<?php
abstract class nfoItemType {
	const Unknown = 0;
	const Movie = 1;
	const Episode = 2;
	const Show = 3;
}
// nfoItemType
class nfoItem {
	public $bTestMode = false;
	public $iSynoUserID = null;
	const cXMLHeader = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>';
	const cXMLRootMovie = 'movie';
	const cXMLRootEpisode = 'episodedetails';
	const cXMLRootShow = 'tvshow';
	const cNFOext = 'nfo';
	const cPrimaryLanguage = 'cs';
	protected $dbConn = null;
	protected $dbConnStr = null;
	protected $dbConnUser = null;
	protected $dbConnPwd = null;
	protected $sSynoCollectionPathMask = null;
	protected $iMapperID = null;
	protected $iShowMapperID = null;
	protected $iFileMapperID = null;
	protected $sItemType = nfoItemType::Unknown;
	protected $sTitle = null;
	protected $sShowTitle = null;
	protected $sOriginalTitle = null;
	protected $sSortTitle = null;
	protected $fRating = null;
	protected $iYear = null;
	protected $iSeason = null;
	protected $iEpisode = null;
	protected $sPlot = null;
	protected $sTag = null;
	protected $iRuntime = null;
	protected $sThumbPoster = null;
	protected $iPlayCount = null;
	protected $sEpisodeguideURL = null;
	protected $sEpisodeguideLANG = null;
	protected $sID = null;
	protected $sIDimdb = null;
	protected $aGenre = null;
	protected $sSet = null;
	protected $aCredits = null;
	protected $sDirector = null;
	protected $dPremiered = null;
	protected $aActor = null;
	protected $dDayAdded = null;
	protected $sFilePath = null;
	protected $sNfoFilePath = null;
	protected $iSynoPosterOID = null;
	private $sXMLstring = null;
	function __construct($iMapperID, & $dbConnection = null, $sConnStr = null, $sConnUser = null, $sConnPwd = null) {
		if (! $iMapperID) {
			throw new Exception ( 'Parameters required!' );
		}
		$this->iMapperID = $iMapperID;
		if ($sConnStr) {
			$this->dbConnStr = $sConnStr;
			$this->dbConnUser = $sConnUser;
			$this->dbConnPwd = $sConnPwd;
		}
		if ($dbConnection) {
			$this->dbConn = $dbConnection;
		}
		if (! $this->dbConn && $this->dbConnStr) {
			$this->dbConnect ();
		}
		return;
	}
	// __construct
	function __destruct() {
	}
	// __destruct
	public static function checkNFOexist($sFilePath) {
		$sNFOpath = nfoItem::getNFOname ( $sFilePath );
		return file_exists ( $sNFOpath );
	}
	// checkNFOexist
	public static function getNFOname($sFilePath) {
		$path_parts = pathinfo ( $sFilePath );
		return $path_parts ['dirname'] . DIRECTORY_SEPARATOR . $path_parts ['filename'] . '.' . nfoItem::cNFOext;
	}
	// getNFOname
	public static function getMoviePosterName($sFilePath) {
		$path_parts = pathinfo ( $sFilePath );
		return $path_parts ['dirname'] . DIRECTORY_SEPARATOR . $path_parts ['filename'] . '-poster.jpg';
	}
	// getMoviePosterName
	public static function getEpisodeThumbName($sFilePath) {
		$path_parts = pathinfo ( $sFilePath );
		return $path_parts ['dirname'] . DIRECTORY_SEPARATOR . $path_parts ['filename'] . '-thumb.jpg';
	}
	// getEpisodeThumbName
	public static function getShowPath($sFilePath) {
		$path_parts = pathinfo ( $sFilePath );
		return dirname ( $path_parts ['dirname'] );
	}
	// getShowPath
	public static function getShowNFOname($sFilePath) {
		return nfoItem::getShowPath ( $sFilePath ) . DIRECTORY_SEPARATOR . 'tvshow.nfo';
	}
	// getShowNFOname
	public static function getShowBannerName($sFilePath) {
		return nfoItem::getShowPath ( $sFilePath ) . DIRECTORY_SEPARATOR . 'banner.jpg';
	}
	// getShowBannerName
	public static function getShowPosterName($sFilePath) {
		return nfoItem::getShowPath ( $sFilePath ) . DIRECTORY_SEPARATOR . 'poster.jpg';
	}
	// getShowPosterName
	public static function getCollectionName($sFilePath) {
		$path_parts = pathinfo ( $sFilePath );
		$path_parts = pathinfo ( $path_parts ['dirname'] );
		return strtr ( basename ( $path_parts ['dirname'] ), '_', ' ' );
	}
	// getCollectionName
	public static function checkURL($sURL) {
		$headers = get_headers ( $sURL );
		if (strpos ( $headers [0], '404' ) === false) {
			return true;
		} else {
			return false;
		}
		return false;
	}
	// checkURL
	public function setDbConnectionString($sConnStr) {
		$this->dbConnStr = $sConnStr;
		return;
	}
	// setDbConnectionString
	public function setDbConnection(&$dbConnection) {
		$this->dbConn = $dbConnection;
		return;
	}
	// setDbConnectionString
	public function setPathCollectionMask($sMask) {
		$this->sSynoCollectionPathMask = $sMask;
		return;
	}
	// setPathCollectionMask
	public function setUserID($iUID) {
		$this->iSynoUserID = $iUID;
		return;
	}
	// setUserID
	public function ExportItem($bReplace = false) {
		$bExport = false;
		$this->synoSelectData ();
		$this->buildNFOstring ();
		$bExport = $this->writeNFO ( $bReplace );
		$this->writePoster ( $bReplace );
		return $bExport;
	}
	// ExportItem
	protected function dbConnect() {
		if ($this->dbConn != null) {
			return;
		}
		if (! $this->dbConnStr) {
			throw new Exception ( 'DB connection string missing!' );
		}
		// Connect to DB
		$this->dbConn = new PDO ( $this->dbConnStr, $this->dbConnUser, $this->dbConnPwd ) or die ( 'Could not connect to DB!' );

		try {
			$this->dbConn->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch ( Exception $e ) {
			throw new Exception ( 'DB set connection parameters error!' . $e->getMessage () );
		}

		return;
	}
	// dbConnect
	protected function dbSelectSingle($sSQLcmd) {
		$dbRs = null;
		$dbRow = null;

		if (! $this->dbConn) {
			throw new Exception ( 'No DB connection!' );
		}

		$dbRs = $this->dbConn->query ( $sSQLcmd . ' LIMIT 1' );
		if (! $dbRs) {
			return null;
		}

		$dbRow = $dbRs->fetch ( PDO::FETCH_NAMED );

		return $dbRow;
	}
	// dbSelectTable
	protected function dbSelectTable($sSQLcmd) {
		$dbRs = null;
		$dbTable = null;

		if (! $this->dbConn) {
			throw new Exception ( 'No DB connection!' );
		}

		$dbRs = $this->dbConn->query ( $sSQLcmd );
		if (! $dbRs) {
			return null;
		}

		$dbTable = $dbRs->fetchAll ( PDO::FETCH_NAMED );

		return $dbTable;
	}
	// dbSelectTable
	protected function dbResultGetValue(&$var, $default = null) {
		return isset ( $var ) ? $var : $default;
	}
	// dbResultGetValue
	protected function synoSelectData() {
		$sSQLcmd = null;
		$dbRes = null;
		$sInfo = null;
		$sPattern = null;
		$aMatch = null;
		$dValue = null;
		$aValue = null;

		if (! $this->dbConn) {
			$this->dbConnect ();
		}

		// Type
		$sSQLcmd = "SELECT type FROM mapper WHERE id = {$this->iMapperID}";
		$dbRes = $this->dbSelectSingle ( $sSQLcmd );
		if (! $dbRes) {
			throw new Exception ( 'Media not found!' );
		}

		switch ($dbRes ['type']) {
			case 'movie' :
				$this->sItemType = nfoItemType::Movie;
				$sSQLcmd = <<<EOT
SELECT main.mapper_id,main.year,main.originally_available,main.library_id,
       main.title,
       main.sort_title,
       main.tag_line AS tag_line,
       main.mapper_id AS file_mapper_id,
       '0' AS show_mapper_id,
       ''  AS show_title,
       '0' AS season,
       '0' AS episode,
       file.path,
       file.create_date,
       file.duration,
       ( SELECT director FROM director WHERE mapper_id = main.mapper_id LIMIT 1 ) AS director,
       ( SELECT summary FROM summary WHERE mapper_id = main.mapper_id LIMIT 1 ) AS summary,
       ( SELECT plus_info FROM plus_info WHERE mapper_id = main.mapper_id LIMIT 1 ) AS plus_info,
       ( SELECT lo_oid FROM poster WHERE mapper_id = main.mapper_id LIMIT 1 ) AS lo_oid,
       ( SELECT string_agg(actor,'|') FROM actor WHERE mapper_id = main.mapper_id ) AS actor,
       ( SELECT string_agg(gnere,'|') FROM gnere WHERE mapper_id = main.mapper_id ) AS gnere,
       ( SELECT string_agg(writer,'|') FROM writer WHERE mapper_id = main.mapper_id ) AS writer,
       ( SELECT COUNT(*) FROM watch_status WHERE uid = {$this->iSynoUserID} AND video_file_id = file.id ) AS playcount
  FROM movie AS main
  INNER JOIN video_file AS file
    ON file.mapper_id = main.mapper_id
  WHERE main.mapper_id = {$this->iMapperID}
EOT;
				break;
			case 'tvshow' :
				$this->sItemType = nfoItemType::Show;
				$sSQLcmd = <<<EOT
SELECT main.mapper_id,main.year,main.originally_available,main.library_id,
       main.title,
       main.sort_title,
       '' AS tag_line,
       file.mapper_id AS file_mapper_id,
       main.mapper_id AS show_mapper_id,
       main.title AS show_title,
       '-1' AS season,
       ( SELECT count(*) AS episode FROM tvshow_episode WHERE tvshow_id = main.id ) as episode,
       file.path,
       file.create_date,
       '0' AS duration,
       '' AS director,
       ( SELECT summary FROM summary WHERE mapper_id = main.mapper_id LIMIT 1 ) AS summary,
       ( SELECT plus_info FROM plus_info WHERE mapper_id = main.mapper_id LIMIT 1 ) AS plus_info,
       ( SELECT lo_oid FROM poster WHERE mapper_id = main.mapper_id LIMIT 1 ) AS lo_oid,
       ( SELECT string_agg(actor,'|') FROM actor WHERE mapper_id = file.mapper_id ) AS actor,
       ( SELECT string_agg(gnere,'|') FROM gnere WHERE mapper_id = file.mapper_id ) AS gnere,
       ( SELECT string_agg(writer,'|') FROM writer WHERE mapper_id = file.mapper_id ) AS writer,
       0 AS playcount
  FROM tvshow AS main
  INNER JOIN video_file AS file
    ON file.mapper_id = ( SELECT mapper_id FROM tvshow_episode WHERE tvshow_id = main.id ORDER BY season, episode LIMIT 1)
  WHERE main.mapper_id = {$this->iMapperID}
EOT;
				break;
			case 'tvshow_episode' :
				$this->sItemType = nfoItemType::Episode;
				$sSQLcmd = <<<EOT
SELECT main.mapper_id,main.year,main.originally_available,main.library_id,
       main.tag_line AS title,
       '' AS sort_title,
       '' AS tag_line,
       main.mapper_id AS file_mapper_id,
       show.mapper_id AS show_mapper_id,
       show.title AS show_title,
       main.season,
       main.episode,
       file.path,
       file.create_date,
       file.duration,
       ( SELECT director FROM director WHERE mapper_id = main.mapper_id LIMIT 1 ) AS director,
       ( SELECT summary FROM summary WHERE mapper_id = main.mapper_id LIMIT 1 ) AS summary,
       ( SELECT plus_info FROM plus_info WHERE mapper_id = main.mapper_id LIMIT 1 ) AS plus_info,
       ( SELECT lo_oid FROM poster WHERE mapper_id = main.mapper_id LIMIT 1 ) AS lo_oid,
       ( SELECT string_agg(actor,'|') FROM actor WHERE mapper_id = main.mapper_id ) AS actor,
       ( SELECT string_agg(gnere,'|') FROM gnere WHERE mapper_id = main.mapper_id ) AS gnere,
       ( SELECT string_agg(writer,'|') FROM writer WHERE mapper_id = main.mapper_id ) AS writer,
       ( SELECT COUNT(*) FROM watch_status WHERE uid = {$this->iSynoUserID} AND video_file_id = file.id ) AS playcount
  FROM tvshow_episode as main
  INNER JOIN tvshow AS show
    ON show.id = main.tvshow_id
  INNER JOIN video_file AS file
    ON file.mapper_id = main.mapper_id
  WHERE main.mapper_id = {$this->iMapperID}
EOT;
				break;
			default :
				throw new Exception ( 'Unknown media type!' );
				break;
		}

		// Main SQL select
		$dbRes = $this->dbSelectSingle ( $sSQLcmd );
		if (! $dbRes) {
			throw new Exception ( 'Media not found!' );
		}

		$this->sTitle = $this->dbResultGetValue ( $dbRes ['title'] );
		$this->sSortTitle = $this->dbResultGetValue ( $dbRes ['sort_title'] );
		$this->sTag = $this->dbResultGetValue ( $dbRes ['tag_line'] );
		$this->iFileMapperID = $this->dbResultGetValue ( $dbRes ['file_mapper_id'] );
		$this->iShowMapperID = $this->dbResultGetValue ( $dbRes ['show_mapper_id'] );
		$this->sShowTitle = $this->dbResultGetValue ( $dbRes ['show_title'] );
		$this->iYear = $this->dbResultGetValue ( $dbRes ['year'] );
		$this->iSynoLibraryID = $this->dbResultGetValue ( $dbRes ['library_id'] );
		$this->iSeason = $this->dbResultGetValue ( $dbRes ['season'] );
		$this->iEpisode = $this->dbResultGetValue ( $dbRes ['episode'] );
		$this->iRuntime = round ( $this->dbResultGetValue ( $dbRes ['duration'] ) / 60 );
		$this->sFilePath = $this->dbResultGetValue ( $dbRes ['path'] );
		$this->sDirector = $this->dbResultGetValue ( $dbRes ['director'] );
		$this->sPlot = $this->dbResultGetValue ( $dbRes ['summary'] );
		$this->iSynoPosterOID = $this->dbResultGetValue ( $dbRes ['lo_oid'] );
		$this->iPlayCount = $this->dbResultGetValue ( $dbRes ['playcount'] );

		$dValue = $this->dbResultGetValue ( $dbRes ['originally_available'] );
		if ($dValue) {
			$this->dPremiered = date ( "Y-m-d", strtotime ( $dValue ) );
		}
		$dValue = $this->dbResultGetValue ( $dbRes ['create_date'] );
		if ($dValue) {
			$this->dDayAdded = date ( 'Y-m-d H:i:s', strtotime ( $dValue ) );
		}

		$aValue = $this->dbResultGetValue ( $dbRes ['actor'] );
		if ($aValue) {
			$this->aActor = explode ( '|', $aValue );
		}
		$aValue = $this->dbResultGetValue ( $dbRes ['gnere'] );
		if ($aValue) {
			$this->aGenre = explode ( '|', $aValue );
		}
		$aValue = $this->dbResultGetValue ( $dbRes ['writer'] );
		if ($aValue) {
			$this->aCredits = explode ( '|', $aValue );
		}

		/* Extra info */
		$sInfo = $this->dbResultGetValue ( $dbRes ['plus_info'] );
		if ($sInfo) {
			// Poster
			$sPattern = '/\"poster\".\:.\[.\"(.*)\".\]/i';
			$aMatch = null;
			if (preg_match ( $sPattern, $sInfo, $aMatch )) {
				$this->sThumbPoster = $aMatch [1];
			}

			if ($this->sItemType == nfoItemType::Movie) {
				// Rating
				$sPattern = '/\"themoviedb\".\:.([0-9]\.[0-9])/i';
				$aMatch = null;
				if (preg_match ( $sPattern, $sInfo, $aMatch )) {
					$this->fRating = $aMatch [1];
				}
				// Reference/ID
				$sPattern = '/\"themoviedb\".\:.([0-9]{2,})/i';
				$aMatch = null;
				if (preg_match ( $sPattern, $sInfo, $aMatch )) {
					$this->sID = $aMatch [1];
				}
				// IMDB ID
				$sPattern = '/\"imdb\".\:.\"(.*)\"/i';
				$aMatch = null;
				if (preg_match ( $sPattern, $sInfo, $aMatch )) {
					$this->sIDimdb = $aMatch [1];
				}
			} else {
				// Rating
				$sPattern = '/\"thetvdb\".\:.([0-9]\.[0-9])/i';
				$aMatch = null;
				if (preg_match ( $sPattern, $sInfo, $aMatch )) {
					$this->fRating = $aMatch [1];
				}
				// Reference/ID
				$sPattern = '/\"thetvdb\".\:.\"([0-9]+)\"/i';
				$aMatch = null;
				if (preg_match ( $sPattern, $sInfo, $aMatch )) {
					$this->sID = $aMatch [1];
				}
			}
		}

		// Set
		if ($this->sSynoCollectionPathMask && preg_match ( $this->sSynoCollectionPathMask, $this->sFilePath )) {
			$this->sSet = nfoItem::getCollectionName ( $this->sFilePath );
		}

		// Episodeguide
		if ($this->sItemType == nfoItemType::Show && $this->sID) {
			$this->sEpisodeguideLANG = nfoItem::cPrimaryLanguage;
			$this->sEpisodeguideURL = "http://thetvdb.com/api/1D62F2F90030C444/series/{$this->sID}/all/{$this->sEpisodeguideLANG}.zip";
			if (! nfoItem::checkURL ( $this->sEpisodeguideURL )) {
				$this->sEpisodeguideLANG = 'en';
				if (! nfoItem::checkURL ( $this->sEpisodeguideURL )) {
					$this->sEpisodeguideURL = null;
					$this->sEpisodeguideLANG = null;
				}
			}
		}

		if ($this->bTestMode) {
			$this->sFilePath = str_replace ( cSYNOvolume, cSYNOvolumeTest . DIRECTORY_SEPARATOR, $this->sFilePath );
			$this->sFilePath = str_replace ( '/', DIRECTORY_SEPARATOR, $this->sFilePath );
		}

		// NFO Path
		if ($this->sItemType == nfoItemType::Show) {
			$this->sNfoFilePath = nfoItem::getShowNFOname ( $this->sFilePath );
		} else {
			$this->sNfoFilePath = nfoItem::getNFOname ( $this->sFilePath );
		}

		return true;
	}
	// synoSelectData
	protected function buildNFOstring() {
		$sRoot = null;
		$sValue = null;
		$aAttr = null;

		$this->sXMLstring = nfoItem::cXMLHeader . "\n";

		switch ($this->sItemType) {
			case nfoItemType::Movie :
				$sRoot = nfoItem::cXMLRootMovie;
				break;
			case nfoItemType::Show :
				$sRoot = nfoItem::cXMLRootShow;
				break;
			case nfoItemType::Episode :
				$sRoot = nfoItem::cXMLRootEpisode;
				break;
			default :
				throw new Exception ( 'Unknown movie type!' );
				break;
		}

		$this->xmlAddElementBegin ( $sRoot, 0 );

		$this->xmlAddElement ( 'title', $this->sTitle );
		if ($this->sItemType == nfoItemType::Episode || $this->sItemType == nfoItemType::Show) {
			$this->xmlAddElement ( 'showtitle', $this->sShowTitle );
		}
		if ($this->sItemType == nfoItemType::Movie) {
			$this->xmlAddElement ( 'originaltitle', $this->sOriginalTitle );
		}
		$this->xmlAddElement ( 'sorttitle', $this->sSortTitle );
		$this->xmlAddElement ( 'rating', $this->fRating );
		$this->xmlAddElement ( 'year', $this->iYear );
		if ($this->sItemType == nfoItemType::Episode || $this->sItemType == nfoItemType::Show) {
			$this->xmlAddElement ( 'season', $this->iSeason );
			$this->xmlAddElement ( 'episode', $this->iEpisode );
		}
		$this->xmlAddElement ( 'plot', $this->sPlot );
		if ($this->sItemType == nfoItemType::Movie || $this->sItemType == nfoItemType::Show) {
			$this->xmlAddElement ( 'tagline', $this->sTag );
		}
		$this->xmlAddElement ( 'runtime', $this->iRuntime );

		if ($this->sItemType == nfoItemType::Movie) {
			$aAttr = array (
					'aspect' => 'poster',
					'preview' => $this->sThumbPoster
			);
		} elseif ($this->sItemType == nfoItemType::Show) {
			$aAttr = array (
					'aspect' => 'poster'
			);
		} else {
			$aAttr = null;
		}

		$this->xmlAddElement ( 'thumb', $this->sThumbPoster, $aAttr );
		$this->xmlAddElement ( 'playcount', $this->iPlayCount );

		if ($this->sEpisodeguideURL) {
			$this->xmlAddElementBegin ( 'episodeguide' );
			$this->xmlAddElement ( 'url', $this->sEpisodeguideURL, array (
					'cache' => "{$this->sID}-{$this->sEpisodeguideLANG}.xml"
			), 2 );
			$this->xmlAddElementEnd ( 'episodeguide' );
		}

		switch ($this->sItemType) {
			case nfoItemType::Movie :
				$this->xmlAddElement ( 'id', $this->sIDimdb );
				break;
			case nfoItemType::Show :
				$this->xmlAddElement ( 'id', $this->sID );
				break;
			case nfoItemType::Episode :
				$this->xmlAddElement ( 'uniqueid', $this->sID );
				break;
		}

		if ($this->sItemType == nfoItemType::Movie || $this->sItemType == nfoItemType::Show) {
			if ($this->aGenre) {
				foreach ( $this->aGenre as $sValue ) {
					$this->xmlAddElement ( 'genre', $sValue );
				}
			}
		}

		if ($this->sItemType == nfoItemType::Movie) {
			$this->xmlAddElement ( 'set', $this->sSet );
		}

		if ($this->sItemType == nfoItemType::Movie || $this->sItemType == nfoItemType::Episode) {
			if ($this->aCredits) {
				foreach ( $this->aCredits as $sValue ) {
					$this->xmlAddElement ( 'credits', $sValue );
				}
			}
		}

		if ($this->sItemType == nfoItemType::Movie || $this->sItemType == nfoItemType::Episode) {
			$this->xmlAddElement ( 'director', $this->sDirector );
		}

		$this->xmlAddElement ( 'premiered', $this->dPremiered );

		if ($this->aActor) {
			foreach ( $this->aActor as $sValue ) {
				$this->xmlAddElementBegin ( 'actor' );
				$this->xmlAddElement ( 'name', $sValue, null, 2 );
				$this->xmlAddElementEnd ( 'actor' );
			}
		}

		$this->xmlAddElement ( 'dateadded', $this->dDayAdded );

		$this->xmlAddElementEnd ( $sRoot, 0 );

		return true;
	}
	// buildNFOstring
	protected function writeNFO($bReplace = false) {
		if (! $this->sXMLstring) {
			throw new Exception ( 'No XML to export!' );
		}

		if (! $this->sNfoFilePath) {
			throw new Exception ( 'No NFO file name generated!' );
		}

		if (! $bReplace && file_exists ( $this->sNfoFilePath )) {
			return false;
		}

		if (file_put_contents ( $this->sNfoFilePath, $this->sXMLstring ) == false) {
			throw new Exception ( 'Write NFO error!' . nfoItem::getLastErrorMessage () );
		}

		return true;
	}
	// writeNFO
	protected function writePoster($bReplace = false) {
		$stream = null;
		$content = null;
		$sName = null;

		if (! $this->iSynoPosterOID && ! $this->sThumbPoster) {
			return;
		}

		switch ($this->sItemType) {
			case nfoItemType::Movie :
				$sName = nfoItem::getMoviePosterName ( $this->sFilePath );
				break;
			case nfoItemType::Show :
				$sName = nfoItem::getShowPosterName ( $this->sFilePath );
				break;
			case nfoItemType::Episode :
				$sName = nfoItem::getEpisodeThumbName ( $this->sFilePath );
				break;
			default :
				throw new Exception ( 'Unknown movie type!' );
				break;
		}

		if (! $sName) {
			throw new Exception ( 'No POSTER file name generated!' );
		}

		if (! $bReplace && file_exists ( $sName )) {
			return false;
		}

		if ($this->iSynoPosterOID) {
			try {
				if (! $this->dbConn->inTransaction ()) {
					$this->dbConn->beginTransaction ();
				}
				$stream = $this->dbConn->pgsqlLOBOpen ( $this->iSynoPosterOID, 'r' );
				$content = stream_get_contents ( $stream );
				$this->dbConn->commit ();
			} catch ( Exception $e ) {
				throw new Exception ( 'Select POSTER error!' . $e->getMessage () );
			}
		}

		if (! $content && $this->sThumbPoster) {
			$content = file_get_contents ( $this->sThumbPoster );
			if (! $content) {
				throw new Exception ( 'Get POSTER error!' . nfoItem::getLastErrorMessage () );
			}
		}

		if (! $content) {
			return false;
		}

		if (file_put_contents ( $sName, $content ) == false) {
			throw new Exception ( 'Write POSTER error!' . nfoItem::getLastErrorMessage () );
		}

		return true;
	}
	// writePoster
	private function xmlAddElement($sName, $sValue, $aAttr = null, $iLevel = 1) {
		$sTab = null;
		$sAttr = '';

		if (! $sValue) {
			return;
		}

		if ($aAttr) {
			foreach ( $aAttr as $sAttrName => $sAttrValue ) {
				$sAttr .= " {$sAttrName}=\"{$sAttrValue}\"";
			}
		}

		if ($iLevel) {
			$sTab = str_repeat ( "\t", $iLevel );
		}

		$this->sXMLstring .= "{$sTab}<{$sName}{$sAttr}>{$sValue}</{$sName}>\n";

		return;
	}
	// xmlAddElement
	private function xmlAddElementBegin($sName, $iLevel = 1) {
		$sTab = null;

		if ($iLevel) {
			$sTab = str_repeat ( "\t", $iLevel );
		}

		$this->sXMLstring .= "{$sTab}<{$sName}>\n";

		return;
	}
	// xmlAddElementBegin
	private function xmlAddElementEnd($sName, $iLevel = 1) {
		$sTab = null;

		if ($iLevel) {
			$sTab = str_repeat ( "\t", $iLevel );
		}

		$this->sXMLstring .= "{$sTab}</{$sName}>\n";

		return;
	}
	// xmlAddElementEnd
	public static function getLastErrorMessage() {
		$last_error = error_get_last ();
		if ($last_error && isset ( $last_error ['message'] )) {
			$errmessage = ' ' . $last_error ['message'];
		} else {
			$errmessage = '';
		}
		return $errmessage;
	}
	// getLastErrorMessage
	public function getMapperID() {
		return $this->iMapperID;
	}
	// getMapperID
	public function getShowMapperID() {
		return $this->iShowMapperID;
	}
	// getShowMapperID
	public function getItemType() {
		return $this->sItemType;
	}
	// getItemType
	public function getPath() {
		return $this->sFilePath;
	}
	// getPath
	public function getNfoPath() {
		return $this->sNfoFilePath;
	}
	// getNfoPath
	public function toString() {
		$sString = '';
		switch ($this->sItemType) {
			case nfoItemType::Movie :
				if ($this->sSet) {
					$sString = "{$this->sSet}/{$this->sTitle} ({$this->iYear})";
				} else {
					$sString = "{$this->sTitle} ({$this->iYear})";
				}
				break;
			case nfoItemType::Show :
				$sString = "{$this->sShowTitle} ({$this->iYear})";
				break;
			case nfoItemType::Episode :
				$sString = "{$this->sShowTitle} - {$this->iSeason}x{$this->iEpisode} {$this->sTitle}";
				break;
			default :
				$sString = '<Unknown>';
				break;
		}

		return $sString;
	} // toString
} // nfoItem
