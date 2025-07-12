<?php
/* File     : /usr/local/www/widgets/widgets/internet_speed_test.widget.php
 * Author   : Zak Ghani
 * Date     : 10-03-2019
 *
 * Modified by  : edhill3@yahoo.com
 * Date         : 11-19-2022
 *
 * This software is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES
 * OR CONDITIONS OF ANY KIND, either express or implied.
 *
 * Speed test service providers supported by this widget include:
 *
 * - M-Lab NDT
 * - Netflix Fast
 * - speedtest-cli
 * - Ookla Speed Test
 */
require_once("guiconfig.inc");
?>
<style>
	.speedtest-td {
//		border: 1px solid black;
		border-color: black;
//		border-style: solid;
//		background-color: #D6EEEE;
		text-align: left;
	}
	.speedtest-td-snow {
//		border: 1px solid black;
		border-color: black;
//		border-style: solid;
//		background-color: #1d2930;
		text-align: center;
    color: orange;
	}
	.speedtest-th {
//		border: 1px solid black;
		border-color: black;
//		border-style: solid;
//		background-color: snow;
		text-align: center;
	}
	label { cursor: pointer; }
	.label-wait { cursor: wait; }
	input[name="speedtest"] { cursor: pointer; }
	input[name="speedtest"] + label[for^=speedtest-] { 
//		color: white; 
		font-weight: normal;
	}
	input[name="speedtest"]:checked + label[for^=speedtest-] {
//	  	color: orange;
	  	font-weight: bold;
	}
	.st-ul { list-style-type: none; }
	.st-li { float: left; padding: 5px; }
</style>

<table id="speedtest-table" class="table table-hover table-striped table-condensed" style="overflow-x: visible;" width="100%">
<tr>
	<!-- Speed tests between local client behind pfSense and a remote server -->
	<td class="speedtest-td">
		<ul class="st-ul">
			<li class="st-li">
				<input type="radio" name="speedtest" id="speedtest-measurementlab-ndt"
									 value="https://www.measurementlab.net/p/ndt-ws.html">
				<label for="speedtest-measurementlab-ndt">&nbsp;M-Lab NDT</label>
			</li>
			<li class="st-li">
				<input type="radio" name="speedtest" id="speedtest-measurementlab-web"
									 value="https://speed.measurementlab.net/">
				<label for="speedtest-measurementlab-web">&nbsp;M-Lab Web</label>
			</li>
			<li class="st-li">
				<input type="radio" name="speedtest" id="speedtest-fast"
									 value="https://fast.com/">
				<label for="speedtest-fast">&nbsp;Fast.com</label>
			</li>
			<li class="st-li">
				<input type="radio" name="speedtest" id="speedtest-open"
									 value="https://openspeedtest.com/speedtest">
				<label for="speedtest-open">&nbsp;OpenSpeedTest</label>
			</li>
			<li class="st-li">
				<input type="radio" name="speedtest" id="speedtest-ookla"
									 value="widgets/include/speedtest_ookla.php">
				<label for="speedtest-ookla">&nbsp;speedtest-ookla</label>
			</li>
<!--
			<li class="st-li">
				<input type="radio" name="speedtest" id="speedtest-cli"
									 value="widgets/include/speedtest_cli.php">
				<label for="speedtest-cli">&nbsp;speedtest-cli</label>
			</li>
-->
			<li class="st-li">
				<input type="radio" name="speedtest" id="speedtest-iperf"
									 value="widgets/include/speedtest_iperf.php">
				<label for="speedtest-iperf">&nbsp;speedtest-iperf</label>
			</li>
		</ul>
	</td>
</tr>
<tr id="panel" hidden="true">
	<td class="speedtest-td-snow" colspan="2">
		<!-- status banner for long running speed tests -->
		<div id="banner" hidden="true">
			<h4>speedtest is running. Please wait...</h4>
		</div>
		<!-- results iframe -->
		<iframe id="canvas" src="about:blank" align="middle"
			height="500px" width="100%" frameborder="0" scrolling="yes"
			loading="lazy">
		</iframe>
	</td>
</tr>
</table>

<script>
let buttons = document.getElementsByName("speedtest");
let labels = document.querySelectorAll("label[for^=speedtest-]");
let panel = document.getElementById("panel");
let banner = document.getElementById("banner");
let canvas = document.getElementById("canvas");
let oneMinute = 60000; // milliseconds
let fiveMinutes = 300000; // milliseconds

buttons.forEach(button => button.addEventListener("click", onButtonClick));
canvas.addEventListener("load", onSpeedTestResultLoad);

const timedAutoPanelCloser = {

	setup(timeoutMillis = fiveMinutes) {

		if (typeof this.timeoutID === 'number') {
			this.cancel();
		}

		this.timeoutID = setTimeout(() => {
            this.timeoutID = undefined;

            // close the display panel
			panel.hidden = true;

            // revert the button to ready state
			buttons.forEach(button => button.checked = false);

		}, timeoutMillis);
	},

	cancel() {
		clearTimeout(this.timeoutID);
	}
};

function onSpeedTestResultLoad() {
	if (this.src != "about:blank") {

		// start the clock on the automatic display panel closer
	        timedAutoPanelCloser.setup(oneMinute);

		// close the wait message banner
		if (!banner.hidden) {banner.hidden = true;}

		// re-enable the buttons
        	buttons.forEach(button => button.disabled = false);
		labels.forEach(label => label.classList.remove('label-wait'));
	}
}

function onButtonClick() {
    	timedAutoPanelCloser.cancel();

    	// if the display is closed or a different speed test is picked
    	// then open and load new speed test
	if (panel.hidden || (canvas.src.includes(this.value) == false)) {

		// Load speed test after small delay
		setTimeout(() => { canvas.src = this.value; }, 100);

		// open the panel
		panel.hidden = false;

		// clear the panel
		canvas.src = "about:blank";

		// disable the buttons
		buttons.forEach(button => button.disabled = true);
		labels.forEach(label => label.classList.add('label-wait'));

        	canvas.height = "500px";

		if (this.id.includes("-cli")) {
            		banner.hidden = false;
			canvas.height = "260px";
		} else if (this.id.includes("-ookla")) {
            		banner.hidden = false;
			canvas.height = "320px";
		} else if (this.id.includes("-iperf")) {
           		 banner.hidden = false;
        	}

	} else {
		// If clicked on the same button and still open, close the display panel
        	panel.hidden = true;
		this.checked = false;
	}
}
</script>
