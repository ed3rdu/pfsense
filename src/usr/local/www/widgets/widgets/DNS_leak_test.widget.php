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
    //$results = shell_exec("/usr/local/go/bin/go run /usr/local/bin/dnsleaktest.go | egrep -v 'Conclusion|leaking'");
    //$results = shell_exec("/usr/local/go/bin/go run /usr/local/bin/dnsleaktest.go");
    $results = shell_exec("/usr/local/bin/dnsleaktest.sh");
    if($results !== null) {
	preg_match_all('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $results, $ip_matches);
	if (sizeof($ip_matches[0]) !== sizeof(array_unique($ip_matches[0]))) {
		$results .= "DNS may be leaking. A DNS server matches your IP address.";
	} else {
		$results .= "DNS is not leaking.";
	}
	$results = "<p style='margin-left: 16px;'>Click the refresh symbol on the title bar to run the DNS leak test.</p><pre><b>" . $results . "</b></pre>";
        //$results .= "<p>If a DNS server matches your IP then you have a DNS leak.</p>";
        $config['widgets']['dnsleaktest_result'] = $results;
        write_config("Save DNS leak test results");
        echo $results;
    } else {
        echo json_encode(null);
    }
} else {
    $results = isset($config['widgets']['dnsleaktest_result']) ? $config['widgets']['dnsleaktest_result'] : null;
    if(($results !== null) && (!is_object(json_decode($results)))) {
        $results = null;
    }
?>
<div id="dnsleaktest-result"></div>
<a id="upddns" href="#" class="fa fa-refresh" style="display: none;"></a>
<script type="text/javascript">

function update_dnsleaktest_result(results) {
    if(results != null) {
        $("#dnsleaktest-result").html(results);
    } else {
    	$("#dnsleaktest-result").html("<p style='margin-left: 16px;'>Click the refresh symbol on the title bar to run the leak test.</p>");
    }
}

function update_dnsleaktest() {
    $('#upddns').off("click").blur().addClass("fa-spin").click(function() {
        $('#upddns').blur();
        return false;
    });
    $.ajax({
        type: 'POST',
        url: "/widgets/widgets/DNS_leak_test.widget.php",
        dataType: 'html',
        data: {
            ajax: "ajax"
        },
        success: function(data) {
            update_dnsleaktest_result(data);
        },
        error: function() {
            update_dnsleaktest_result(null);
        },
        complete: function() {
            $('#upddns').off("click").removeClass("fa-spin").click(function() {
                update_dnsleaktest();
                return false;
            });
        }
    });
}
events.push(function() {
	var target = $("#upddns").closest(".panel").find(".widget-heading-icon");
	$("#upddns").prependTo(target).show();
    $('#upddns').click(function() {
        update_dnsleaktest();
        return false;
    });
    update_dnsleaktest_result(<?php echo ($results === null ? "null" : $results); ?>);
});
</script>
<?php } ?>
