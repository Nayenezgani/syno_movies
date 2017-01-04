/**
 * Syno Movies - Rename
 */

var formElement = null;
var formPath = null;
var pathSuggest = null;
function toolOnLoad() {

	formElement = document.getElementById('formMain');
	formPath = document.getElementById('input_path');
	pathSuggest = document.getElementById('pathSuggest');

	return;
} // toolOnLoad
function path_suggest() {
	var formData = new FormData(formElement);
	var xhr = new XMLHttpRequest();

	xhr.open('POST', 'path_suggest.php');
	xhr.onload = function(e) {
		setPathSuggest(this.responseText.trim());
	};
	xhr.send(formData);

	return;
} // path_suggest
function suggest_fill(inPath) {
	pathSuggest.style.display = 'none';
	formPath.value = inPath;
	formPath.focus();
	return;
} // suggest_fill
function setPathSuggest(inPathList) {
	if (inPathList == '') {
		pathSuggest.style.display = 'none';
		pathSuggest.innerHTML = '';
		return;
	}
	pathSuggest.style.display = 'block';
	pathSuggest.innerHTML = inPathList;
	return;
}
toolOnLoad();
