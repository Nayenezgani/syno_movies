<?php
require_once ('../config.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Synology - manage</title>
<link rel="stylesheet" type="text/css" href="../style/movies.css" />
<script type="text/javascript" src="../scripts/movies.js"></script>
<script type="text/javascript" src="../scripts/processIndicator.js"></script>
</head>
<body onload="mainOnLoad();">
	<header>
		<input type="radio" id="export_nfo" name="toolSwitch" class="toolSwitch" onchange="loadTool(this.id, false, false);" />
		<label for="export_nfo" class="toolSwitch">NFO</label>
		<input type="radio" id="upload_title" name="toolSwitch" class="toolSwitch" onchange="loadTool(this.id, true, false);" />
		<label for="upload_title" class="toolSwitch">Upload</label>
		<!--
		<input type="radio" id="rename" name="toolSwitch" class="toolSwitch" onchange="loadTool(this.id, true, true);" />
		<label for="rename" class="toolSwitch">Rename</label>
		-->
	</header>
	<div id="myTool"></div>
	<div id="content">
		<canvas id="processIndicator" width="70" height="70"></canvas>
		<div id="exportResult" class="result"></div>
	</div>
</body>
</html>