<?php
    $results = shell_exec("/usr/local/bin/dnsleaktest.sh");
    echo ($results === null ? "null" : $results);
?>
