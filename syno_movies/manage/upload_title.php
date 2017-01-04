<?php
require_once ('../config.php');
$dbConn = new PDO ( cDbConnSYNOstr, cDbConnSYNOuser, cDbConnSYNOpwd ) or die ( 'Could not connect to SYNO DB!' );
$dbConn->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$dbRs = $dbConn->query ( 'SELECT id,title FROM tvshow ORDER BY title;' );
if ($dbRs) {
	$aShows = $dbRs->fetchAll ( PDO::FETCH_NAMED );
}
?>
<form id="formMain" name="formMain" action="upload_title_process.php" method="post" onsubmit="return submitForm(this);" onreset="return resetForm(true);">
	<div class="block">
		<div class="block_header">Upload episode title</div>
		<div>
			<label for="TVshow">TV Show:</label> <select name="TVshow" id="TVshow" required="required" onchange="resetFile();">
				<option value=""></option>
					<?php
					foreach ( $aShows as $aShow ) {
						?>
					<option value="<?php echo($aShow["id"])?>"><?php echo($aShow["title"])?></option>
					<?php }?>
				</select>
		</div>
		<div>
			<label for="episodesTitleFile">File:</label>
			<input type="file" name="episodesTitleFile" id="episodesTitleFile" required="required" />
		</div>
		<div>
			<input type="checkbox" id="lock" name="lock" value="1" />
			<label for="lock">Set Lock</label>
		</div>
		<div>
			<input type="checkbox" id="test" name="test" value="1" checked="checked" />
			<label for="test">Test mode</label>
		</div>
	</div>
	<div>
		<input id="btnReset" name="btnReset" type="reset" value="Reset">
		<input id="btnSubmit" name="btnSubmit" type="submit" value="Update">
	</div>
</form>
