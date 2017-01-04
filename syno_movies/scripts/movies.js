/**
 * Syno Movies - Main
 */
function mainOnLoad() {
	procIndicatorInit('processIndicator');
	var toolSwitch = document.getElementsByName("toolSwitch")[0];
	toolSwitch.checked = true;
	loadTool(toolSwitch.id);
	return true;
} // mainOnLoad
function setResultText(inText) {
	var resultElement = document.getElementById("exportResult");
	resultElement.innerHTML = inText;
	if (inText != '') {
		jump_to('content');
	}
	return false;
} // setResultText
function submitForm(formElement) {
	var formData = new FormData(formElement);
	var xhr = new XMLHttpRequest();

	setResultText("");
	procIndicatorStart();

	xhr.open(formElement.method, formElement.action);
	xhr.onload = function(e) {
		procIndicatorStop();
		setResultText(this.responseText);
		// resetForm(false);
	};
	xhr.send(formData);

	return false;
} // submitForm
function resetForm(bResetLog) {

	if (bResetLog) {
		setResultText('');
	}

	return true;
} // resetForm
function loadTool(toolID, bJS, bStyle) {
	var xhr = new XMLHttpRequest();
	xhr.open("GET", toolID + ".php");
	xhr.onload = function(e) {
		setToolContent(toolID, this.responseText, bJS, bStyle);
	};
	xhr.send();

	return false;
} // loadTool
function setToolContent(toolID, inToolContent, bJS, bStyle) {
	var toolContent = document.getElementById("myTool");
	var toolJS = document.createElement('script');
	var toolStyle = document.createElement('link');

	setResultText("");
	procIndicatorStop();

	toolContent.innerHTML = inToolContent;

	if (bStyle) {
		toolStyle.rel = 'stylesheet';
		toolStyle.type = "text/css";
		toolStyle.href = '../style/movies_' + toolID + '.css';
		toolStyle.media = 'all';
		toolContent.appendChild(toolStyle);
	}

	if (bJS) {
		toolJS.type = "application/javascript";
		toolJS.src = '../scripts/movies_' + toolID + '.js';
		toolContent.appendChild(toolJS);
	}

	return;
}// setToolContent
function jump_to(h) {
	var url = location.href; // Save down the URL without hash.
	location.href = "#" + h; // Go to the target element.
	history.replaceState(null, null, url); // Don't like hashes.
} // jump_to​
