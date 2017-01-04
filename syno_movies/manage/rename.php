<form id="formMain" name="formMain" action="rename_process.php" method="post" onsubmit="return submitForm(this);" onreset="return resetForm(true);">
	<div class="block">
		<div class="block_header">Convert filename</div>
		<input type="hidden" id="folderOnly" name="folderOnly" value="1" />
		<div>
			<label for="input_path">Path:</label> <br />
			<input type="text" id="input_path" name="input_path" size="50" maxlength="200" required="required" autocomplete="off" autofocus="autofocus" placeholder="Folder path..." onkeyup="path_suggest();" />
			<div id="pathSuggestBox" class="pathSuggestBox">
				<div id="pathSuggest" class="pathSuggest"></div>
			</div>
		</div>
	</div>
	<div>
		<input id="btnReset" name="btnReset" type="reset" value="Reset">
		<input id="btnSubmit" name="btnSubmit" type="submit" value="Update">
	</div>
</form>
