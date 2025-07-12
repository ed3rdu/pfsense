<?php
   // $results = shell_exec("/usr/local/bin/ip_info_data.sh");
   // echo ($results === null ? "null" : $results);
?>

<table class='table table-striped table-hover table-condensed'>
<thead>
<tr>
<th style='text-align: center;'>External IP</th>
<th style='text-align: center;'>Provider</th>
<th style='text-align: center;'>Region</th>
<th style='text-align: center;'>Country</th>
<th style='text-align: center;'>State</th>
<th style='text-align: center;'>City</th>
</tr>
</thead>

<tbody>
<tr>
<td><span id="ip"></span></td>
<td><span id="org"></span></td>
<td><span id="region"></span></td>
<td><span id="country"></span></td>
<td><span id="state"></span></td>
<td><span id="city"></span></td></tr>
</tbody>
</table>

<script src="https://geoip-js.com/js/apis/geoip2/v2.1/geoip2.js" type="text/javascript"></script>

<script>
var fillInPage = (function() {
  var updateLocationData = function(geoipResponse) {
	var missing = '';
	document.getElementById('city').innerHTML = geoipResponse.city.names.en || missing;
	document.getElementById('ip').innerHTML = geoipResponse.traits.ip_address || missing;
	document.getElementById('org').innerHTML = geoipResponse.traits.organization || missing;
	document.getElementById('region').innerHTML = geoipResponse.continent.names.en || missing;
        document.getElementById('state').innerHTML = geoipResponse.subdivisions[0].names.en || missing;
	document.getElementById('country').innerHTML = geoipResponse.country.names.en || missing;
  };

  var onSuccess = function(geoipResponse) {
    updateLocationData(geoipResponse);
  };

  // If we get an error, we will display an error message
  var onError = function(error) {
	document.getElementById('city').innerHTML = 'an error! Please try again..';
  };

  return function() {
    if (typeof geoip2 !== 'undefined') {
      geoip2.insights(onSuccess, onError);
    } else {
	document.getElementById('city').innerHTML = 'a browser that blocks GeoIP2 requests';
    }
  };
}());

fillInPage();
</script>
