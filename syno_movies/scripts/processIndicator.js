/**
 * Process Indicator
 */
var pcindCounter = 5;
var pcindCtxElement = null;
var pcindCtx = null;
var pcindCtxWidth = null;
var pcindCtxHeight = null;
var pcindTimer = null;
var pcindDrawStart = ((pcindCounter - 5 / 100) * Math.PI * 2 * 10).toFixed(2);
var pcindDrawEnd = ((pcindCounter / 100) * Math.PI * 2 * 10).toFixed(2);

function procIndicatorInit(inElementID) {
	pcindCtxElement = document.getElementById(inElementID);
	pcindCtx = pcindCtxElement.getContext('2d');
	pcindCtxWidth = pcindCtx.canvas.width;
	pcindCtxHeight = pcindCtx.canvas.height;
	pcindCtxElement.style.display = 'none';
	return;
} // procIndicatorInit
function procIndicatorStart() {
	pcindCtxElement.style.display = 'inline';
	pcindTimer = setInterval(procIndicatorDraw, 100);
	return;
} // procIndicatorStart
function procIndicatorStop() {
	clearInterval(pcindTimer);
	pcindCtx.clearRect(0, 0, pcindCtxWidth, pcindCtxHeight);
	pcindCtxElement.style.display = 'none';
	return;
} // procIndicatorStop
function procIndicatorDraw() {
	pcindDrawEnd = ((pcindCounter / 100) * Math.PI * 2 * 10).toFixed(2);
	pcindDrawStart = (((pcindCounter - 1) / 100) * Math.PI * 2 * 10).toFixed(2);
	;
	pcindCtx.clearRect(0, 0, pcindCtxWidth, pcindCtxHeight);
	pcindCtx.lineWidth = 10;
	pcindCtx.fillStyle = '#09F';
	pcindCtx.strokeStyle = "#09F";
	pcindCtx.textAlign = 'center';
	pcindCtx.fillText('Working..', pcindCtxWidth * .5, pcindCtxHeight * .5 + 2,
			pcindCtxWidth);
	pcindCtx.beginPath();
	pcindCtx.arc(35, 35, 30, pcindDrawStart, pcindDrawEnd, pcindCounter);
	pcindCtx.stroke();
	if (pcindCounter >= 100) {
		pcindCounter = 1;
	}
	pcindCounter++;
	return;
} // procIndicatorDraw
