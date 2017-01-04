<form id="formMain" name="formMain" action="export_nfo_process.php" method="post" onsubmit="return submitForm(this);" onreset="return resetForm(true);">
	<div class="block">
		<div class="block_header">Export NFO</div>
		<div>
			<label for="from_date">Date from:</label>
			<input type="date" id="from_date" name="from_date" max="<?php echo(Date('Y-m-d'));?>" value="<?php echo(Date('Y-m-d', strtotime ( "-7 days" ))); ?>" required="required" />
		</div>
		<div>
			<label for="path_filter">Path filter:</label>
			<input type="text" id="path_filter" name="path_filter" placeholder="path fragment" maxlength="50" size="24" />
		</div>
		<div>
			<input type="checkbox" id="replace" name="replace" value="1" />
			<label for="replace">Replace existing NFO</label>
		</div>
	</div>
	<div class="block">
		<div class="block_header">KODI</div>
		<div>
			<input type="checkbox" id="watched" name="watched" value="1" checked="checked" />
			<label for="watched">Sync watched</label>
			<div>
				<label for="watched_date">Watched from:</label>
				<input type="date" id="watched_date" name="watched_date" max="<?php echo(Date('Y-m-d'));?>" value="<?php echo(Date('Y-m-d', strtotime ( "-30 days" ))); ?>" required="required" />
			</div>
		</div>
		<div>
			<input type="checkbox" id="notify" name="notify" value="1" checked="checked" />
			<label for="notify">Notify</label>
		</div>
		<div>
			<input type="checkbox" id="clean" name="clean" value="1" />
			<label for="clean">Clean</label>
		</div>
	</div>
	<div>
		<input id="btnReset" name="btnReset" type="reset" value="Reset">
		<input id="btnSubmit" name="btnSubmit" type="submit" value="Start">
	</div>
</form>