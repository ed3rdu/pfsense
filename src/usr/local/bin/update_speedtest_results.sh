#!/usr/bin/env bash
#
#-Refresh speedtest_results.js from influx database

# Modified for integration the Speedtest Tracker running in container on TRueNAS
####################################################

trap 'rm -f "${LOG}"' EXIT
LOG=$(mktemp) || exit 1
JS="/usr/local/www/javascript/speedtest_results.js"
# Source InfluxDB connection details from config file
CONFIG_FILE="/etc/pfsense_speedtest.conf"
if [ ! -f "$CONFIG_FILE" ]; then
  echo "Config file $CONFIG_FILE not found!"
  exit 1
fi
. "$CONFIG_FILE"

mbps() { #-Convert bandwidth data to megabits/sec
    local _bandwidth="${1}"
    printf %.2f $(echo "scale=3; ${_bandwidth} * 0.000001" | bc -l)
}

curl \
-G "${INFLUXDB_URL}/query" \
--header "Authorization: Token ${INFLUXDB_TOKEN}" \
--data-urlencode "org=${INFLUXDB_ORG}" \
--data-urlencode "db=${INFLUXDB_BUCKET}" \
--data-urlencode "q=select time,upload_bits,download_bits from speedtest where time > now() - 12h order by time asc" | \
jq -r -c '.results[].series[].values[]' | sed 's/^\[\(.*\)\]$/\1/' | tr -d '"' >> ${LOG}


echo "// $(date)" > ${JS}
echo "var TESTDAYTE = new Map();" >> ${JS}
echo "var UPLOAD = new Map();" >> ${JS}
echo "var DOWNLOAD = new Map();" >> ${JS}


#- This code uses the generated log file as data source
    unset datum
    unset upload
    unset download
    while IFS=',' read -r t_datum t_upload t_download
    do
	upload_mbps=$( mbps ${t_upload} )
	download_mbps=$( mbps ${t_download} ) 
        datum="${datum},'${t_datum// /}'"
        upload="${upload},'${upload_mbps// /}'"
        download="${download},'${download_mbps// /}'"
    done < ${LOG}

provider="ookla"

    echo "TESTDAYTE.set(\"${provider,,}\", [${datum#,}]);" >> ${JS}
    echo "UPLOAD.set(\"${provider,,}\", [${upload#,}]);" >> ${JS}
    echo "DOWNLOAD.set(\"${provider,,}\", [${download#,}]);" >> ${JS}

exit 0
