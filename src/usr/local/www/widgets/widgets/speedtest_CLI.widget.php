<?php

/*
 * speedtest.widget.php
 *
 * Copyright (c) 2020 Alon Noy
 *
 * Modified by  : edhill3@yahoo.com
 * Date         : 02-26-2022
 *
 * Licensed under the GPL, Version 3.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("guiconfig.inc");

if ($_REQUEST['ajax']) {
//    $results = shell_exec("/usr/local/bin/speedtest-cli --secure --json");
    $results = shell_exec("/usr/local/bin/speedtest.sh");
//	$results = shell_exec("/usr/local/bin/speedtest --accept-license -a -f json");

    if(($results !== null) && (json_decode($results) !== null)) {
        $config['widgets']['speedtest_result'] = $results;
        write_config("Save speedtest results");
        echo $results;
    } else {
        echo json_encode(null);
    }
} else {
    $results = isset($config['widgets']['speedtest_result']) ? $config['widgets']['speedtest_result'] : null;
    if(($results !== null) && (!is_object(json_decode($results)))) {
        $results = null;
    }
?>
<table class="table">
	<tr>
		<td><h4>Latency <i class="fa fa-exchange"></h4></td>
        <td><h4>Jitter <i class="fa fa-exchange"></h4></td>
		<td><h4>Download <i class="fa fa-download"></i></h4></td>
		<td><h4>Upload <i class="fa fa-upload"></h4></td>
	</tr>
	<tr>
		<td><h4 id="speedtest-ping">N/A</h4></td>
        <td><h4 id="speedtest-jitter">N/A</h4></td>
		<td><h4 id="speedtest-download">N/A</h4></td>
		<td><h4 id="speedtest-upload">N/A</h4></td>
	</tr>
    <table align="center" class="table">
        <tr>
            <th align="center" style="border-top: 1px solid grey;border-right: 1px solid grey;text-align:center;">Service Provider</th>
            <th align="center" style="border-top: 1px solid grey;text-align:center;">Speed Test Server</th>
        </tr>
        <tr>
            <td style="border-right: 1px solid grey;text-align:center;" id="speedtest-isp">N/A</td>
            <td style="text-align:center;" id="speedtest-host">N/A</td>
        <tr>
        <tr>
            <td style="border-right: 1px solid grey;text-align:center" id="speedtest-extip">N/A</td>
            <td style="text-align:center" id="speedtest-loc">N/A</td></tr>
	    <tr>
            <td colspan="2" id="speedtest-ts" style="border-top: 1px solid grey;text-align:center">
                Click the refresh symbol on the title bar to run the speed test.</td>
	    </tr>
    </table>
</table>
<a id="updspeed" href="#" class="fa fa-refresh" style="display: none;"></a>
<script type="text/javascript">

const ts_options = {
    weekday:    "long",
    year:       "numeric",
    month:      "long",
    day:        "numeric",
    timeZone:   Intl.DateTimeFormat().resolvedOptions().timeZone,
    hour12:     true,
    hour:       "numeric",
    minute:     "numeric"
   // second: "2-digit"
};

function update_speedtest_result(results) {
    if(results != null) {

    	var date = new Date(results.timestamp);
    	$("#speedtest-ts").html(date.toLocaleString("en-US", ts_options));
    	$("#speedtest-ping").html(results.ping.latency.toFixed(2) + "<small> ms</small>");
    	$("#speedtest-download").html((results.download.bandwidth / 125000).toFixed(2) + "<small> Mbps</small>");
    	$("#speedtest-upload").html((results.upload.bandwidth / 125000).toFixed(2) + "<small> Mbps</small>");
    	$("#speedtest-isp").html(results.isp);
    	$("#speedtest-host").html(results.server.name);
        $("#speedtest-loc").html(results.server.location);
        $("#speedtest-extip").html(results.interface.externalIp);
	$("#speedtest-jitter").html(results.ping.jitter.toFixed(2) + "<small> ms</small>");

    } else {
    	$("#speedtest-ts").html("The speed test failed. Click the refresh symbol on the title bar to retry.");
    	$("#speedtest-ping").html("N/A");
    	$("#speedtest-download").html("N/A");
    	$("#speedtest-upload").html("N/A");
    	$("#speedtest-isp").html("N/A");
    	$("#speedtest-host").html("N/A");
        $("#speedtest-loc").html("N/A");
        $("#speedtest-extip").html("N/A");
	$("#speedtest-jitter").html("N/A");
    }
}

function update_speedtest() {
    $('#updspeed').off("click").blur().addClass("fa-spin").click(function() {
        $('#updspeed').blur();
        return false;
    });
    $.ajax({
        type: 'POST',
        url: "/widgets/widgets/speedtest_CLI.widget.php",
        dataType: 'json',
        data: {
            ajax: "ajax"
        },
        success: function(data) {
            update_speedtest_result(data);
        },
        error: function() {
            update_speedtest_result(null);
        },
        complete: function() {
            $('#updspeed').off("click").removeClass("fa-spin").click(function() {
                update_speedtest();
                return false;
            });
        }
    });
}
events.push(function() {
	var target = $("#updspeed").closest(".panel").find(".widget-heading-icon");
	$("#updspeed").prependTo(target).show();
    $('#updspeed').click(function() {
        update_speedtest();
        return false;
    });
    update_speedtest_result(<?php echo ($results === null ? "null" : $results); ?>);
});
</script>
<?php } ?>
