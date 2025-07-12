<?php
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    $ip = idn_to_ascii(trim($_REQUEST['ip'], " \t\n\r\0\x0B[];\"'"));
    $output = passthru("/usr/local/bin/ipinfo $ip");
    echo $output;
?>
