<?php
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    header("Pragma: no-cache");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    //echo passthru("/usr/local/bin/iperf3 -c ash.speedtest.clouvider.net -p 5200-5209");
    echo passthru("/usr/local/bin/iperf3 -c speedtest.dal13.us.leaseweb.net -p 5201-5210");
?>
