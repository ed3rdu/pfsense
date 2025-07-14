<?php
require_once("guiconfig.inc");

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$secrets = include('/conf/influx_secrets.php');
$influx_host  = $secrets['influx_host'];
$influx_token = $secrets['influx_token'];
$org          = $secrets['org'];
$bucket       = $secrets['bucket'];

$cache_file = "/tmp/speedtest_tracker_widget_cache.csv";
$last_seen_file = "/tmp/speedtest_tracker_widget_lasttime.txt";
$cache_ttl = 3600;
$now = time();
$force_refresh = isset($_GET['force']) && $_GET['force'] === 'true';

function queryLatestResultTime($influx_host, $influx_token, $org, $bucket) {
    $query = 'from(bucket: "' . $bucket . '")
      |> range(start: -2d)
      |> filter(fn: (r) => r._measurement == "speedtest")
      |> filter(fn: (r) => r._field == "download_bits")
      |> keep(columns: ["_time"])
      |> sort(columns: ["_time"], desc: true)
      |> limit(n:1)';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$influx_host/api/v2/query?org=$org");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token $influx_token",
        "Accept: application/csv",
        "Content-type: application/vnd.flux"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    $result = curl_exec($ch);
    curl_close($ch);

    $lines = explode("\n", $result);
    foreach ($lines as $line) {
        if (strpos($line, "#") === 0 || empty(trim($line))) continue;
        $cols = str_getcsv($line);
        foreach ($cols as $col) {
            if (strtotime($col)) {
                return trim($col);
            }
        }
    }
    return null;
}

function fetchInfluxData($influx_host, $influx_token, $org, $bucket) {
    $query = 'from(bucket: "' . $bucket . '")
      |> range(start: -12h)
      |> filter(fn: (r) => r._measurement == "speedtest")
      |> filter(fn: (r) => r._field == "upload_bits" or r._field == "download_bits")
      |> sort(columns: ["_time"], desc: false)';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$influx_host/api/v2/query?org=$org");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token $influx_token",
        "Accept: application/csv",
        "Content-type: application/vnd.flux"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$use_cache = false;
$latest_time = queryLatestResultTime($influx_host, $influx_token, $org, $bucket);
$cached_last_time = file_exists($last_seen_file) ? trim(file_get_contents($last_seen_file)) : null;

if (!$force_refresh && file_exists($cache_file) && $cached_last_time === $latest_time) {
    $use_cache = true;
}

if ($use_cache) {
    $data_csv = file_get_contents($cache_file);
} else {
	$data_csv = fetchInfluxData($influx_host, $influx_token, $org, $bucket);
    if ($data_csv && $latest_time) {
        file_put_contents($cache_file, $data_csv);
        file_put_contents($last_seen_file, $latest_time);
    }
}

$download_data = [];
$upload_data = [];
$labels = [];

if ($data_csv) {
    $lines = explode("\n", $data_csv);
    foreach ($lines as $line) {
        if (strpos($line, "#") === 0 || empty(trim($line))) continue;
        $cols = str_getcsv($line);
        if (count($cols) < 8) continue;
        if (isset($cols[5]) && trim($cols[5]) === "_time") continue;
        if (isset($cols[7]) && trim($cols[7]) === "_field") continue;
        if (isset($cols[6]) && trim($cols[6]) === "_value") continue;

        $time = $cols[5];
        $field = $cols[7];
        $value = round((float)$cols[6] * 0.000001, 2);

        if (!in_array($time, $labels)) $labels[] = $time;
        if ($field === "download_bits") $download_data[$time] = $value;
        if ($field === "upload_bits") $upload_data[$time] = $value;
    }
    sort($labels);
}

echo json_encode([
    "title" => "Internet Speed - Mbps (Smart Auto Load)",
    "labels" => array_values($labels),
    "download" => array_values($download_data),
    "upload" => array_values($upload_data),
    "debug" => [
        "force_refresh" => $force_refresh,
        "used_cache" => $use_cache,
        "cached_last_time" => $cached_last_time,
        "latest_time_checked" => $latest_time,
        "cache_file_exists" => file_exists($cache_file),
        "time_data" => $labels,
        "cache_age" => file_exists($cache_file) ? (time() - filemtime($cache_file)) : null,
        "cache_max_age" => $cache_ttl
    ]
]);
