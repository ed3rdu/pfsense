<?php
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    header("Pragma: no-cache");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    echo passthru("/usr/local/bin/speedtest-ookla-native");
?>
