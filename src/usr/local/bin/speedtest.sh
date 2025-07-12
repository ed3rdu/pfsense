#!/usr/bin/env bash
#
# Script to collect internet speed test data
# The data is saved to a file to be used by pfSense dashboard widget

# Directory where speed test data is saved

while getopts "s:H" opts
do
    case "${opts}" in
        s)
            server="${OPTARG}"
            ;;
	H)
		human_readable=true
		;;
        :)
            echo "Error: -${OPTARG} required an aurgument."
            exit 1
            ;;
        *)
            exit 1
            ;;
    esac
done

echo "Start $0 ${@}" | logger -t "$(basename $0)"

# Delete temporary data file on exit
trap 'cleanup' EXIT

cleanup() {
    rm -f "${JSON}" "${TELEGRAF_CONF}"
}

# Temporary data file
JSON="$(mktemp)" || exit 1

# Source connection details from config file
CONFIG_FILE="/etc/pfsense_speedtest.conf"
if [ ! -f "$CONFIG_FILE" ]; then
  echo "Config file $CONFIG_FILE not found!"
  exit 1
fi
. "$CONFIG_FILE"

curl -L \
  --request POST \
  --url "${API_URL}/api/v1/speedtests/run" \
  --header 'Accept: */*' \
  --header "Authorization: Bearer $SPEEDTEST_TRACKER_TOKEN" \
  --data '{"server_id": 5342}' >/dev/null 2>&1

echo "speedtest request submitted, please wait..."

sleep 20

count=0
max=8

while :; do

response=$(curl -L --request GET "${API_URL}/api/v1/results/latest" --header 'Accept: */*' --header "Authorization: Bearer $TOKEN" 2>/dev/null)

if [[ "$response" == *'"status":"completed"'* ]]; then
  echo "Test completed!"
  echo -n "${response}" > ${JSON}
	break
else
  echo "Still running..."
  sleep 2
  ((count++))
  [ "${count}" -ge "${max}" ] && break
fi
done

#-Run speedtest and save output
#[ -x "/usr/local/bin/speedtest" ] || { echo "speedtest not found!"; exit 1; }

#/usr/local/bin/speedtest --accept-license -a -f json --server-id=${server:-5342} > ${JSON}

if [ -f "${JSON}" ]; then
	[ -s "${JSON}" ] || { echo "${JSON} is empty"; exit 1; }

if [ -z ${human_readable+x} ]
then
	cat ${JSON}
else
#cat ${JSON} | jq | tee dump.txt
jq -r '
{
    "ISP": .data.data.isp,
    "Timestamp": .data.updated_at,
    "Ping Latency (ms)": (.data.data.ping.latency * 100 | round / 100),
    "Ping Jitter (ms)": (.data.data.ping.jitter * 100 | round / 100),
    "Download Bandwidth (Mbps)": (.data.data.download.bandwidth / 125000 * 100 | round / 100),
    "Download Latency (ms)": (.data.data.download.latency.iqm * 100 | round / 100),
    "Upload Bandwidth (Mbps)": (.data.data.upload.bandwidth / 125000 * 100 | round / 100),
    "Upload Latency (ms)": (.data.data.upload.latency.iqm * 100 | round / 100),
    "Packet Loss (%)": (.data.data.packetLoss | tostring),
    "Server Location": .data.data.server.location,
    "Server Host": .data.data.server.host
} | 
    "ISP\t" + .ISP,
    "Timestamp\t" + .Timestamp,
    "Ping Latency\t" + (.["Ping Latency (ms)"] | tostring + " ms"),
    "Ping Jitter\t" + (.["Ping Jitter (ms)"] | tostring + " ms"),
    "Download Bandwidth\t" + (.["Download Bandwidth (Mbps)"] | tostring + " Mbps"),
    "Download Latency\t" + (.["Download Latency (ms)"] | tostring + " ms"),
    "Upload Bandwidth\t" + (.["Upload Bandwidth (Mbps)"] | tostring + " Mbps"),
    "Upload Latency\t" + (.["Upload Latency (ms)"] | tostring + " ms"),
    "Packet Loss\t" + (.["Packet Loss (%)"] | tostring + " %"),
    "Server Location\t" + .["Server Location"],
    "Server Host\t" + .["Server Host"]' "${JSON}" | column -t -s $'\t'
fi
	logger -t "$(basename $0)" -f "${JSON}"
else
	echo "${JSON} does not exist"
	exit 1
fi

#if [ -x "/usr/local/bin/telegraf" ] 
#then#
#	TELEGRAF_CONF="$(mktemp)" || exit 1

#	cp /usr/local/etc/speedtest_telegraf_template.conf ${TELEGRAF_CONF}
#	printf "\tfiles = [\"${JSON}\"]" >> ${TELEGRAF_CONF}

#	telegraf --once --config ${TELEGRAF_CONF}
#fi

echo "End $0 ${@}" | logger -t "$(basename $0)"
exit 0
