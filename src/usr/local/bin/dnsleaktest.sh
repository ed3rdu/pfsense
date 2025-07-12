#!/usr/bin/env bash

RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'
api_domain='bash.ws'
error_code=1

function increment_error_code {
    error_code=$((error_code + 1))
}

function echo_bold {
    echo -e "${BOLD}${1}${NC}"
}

function echo_error {
    (>&2 echo -e "${RED}${1}${NC}")
}

function program_exit {
    command -v $1 > /dev/null
    if [ $? -ne 0 ]; then
        echo_error "Please, install \"$1\""
        exit $error_code
    fi
    increment_error_code
}

function check_internet_connection {
    curl --silent --head  --request GET "https://${api_domain}" | grep "200 OK" > /dev/null
    if [ $? -ne 0 ]; then
        echo_error "No internet connection."
        exit $error_code
    fi
    increment_error_code
}

program_exit curl
program_exit ping
program_exit jq
check_internet_connection

if hash shuf 2>/dev/null; then
    id=$(shuf -i 1000000-9999999 -n 1)
else
    id=$(jot -w %i -r 1 1000000 9999999)
fi

for i in $(seq 1 10); do
    ping -c 1 -t 1 "${i}.${id}.${api_domain}" > /dev/null 2>&1
done

function print_servers {
    echo "<table class='table table-striped table-hover table-condensed'>"
    echo "<thead><tr>"
    echo "<th style='text-align: left;'>&nbsp;IP</th>"
    echo "<th style='text-align: left;'>&nbsp;Host</th>"
    echo "<th style='text-align: left;'>&nbsp;Country</th>"
    echo "<th style='text-align: left;'>&nbsp;ASN</th>"
    echo "</tr></thead><tbody>"
#    echo "${result_json}" | \
#            jq  --monochrome-output \
#            --raw-output \
#            ".[] | select(.type == \"${1}\") | \"<tr><td>\(.ip)</td>\(if .country_name != \"\" and  .country_name != false then \"<td>\(.country_name)</td>\(if .asn != \"\" and .asn != false then \"<td>&nbsp;\(.asn)</td>\" else \"\" end)</tr>\" else \"\" end)\""


        while IFS= read -r line; do
            if [[ "$line" != *${1} ]]; then
                continue
            fi

            ip=$(echo "$line" | cut -d'|' -f 1)
            code=$(echo "$line" | cut -d'|' -f 2)
            country=$(echo "$line" | cut -d'|' -f 3)
            asn=$(echo "$line" | cut -d'|' -f 4)

            if [ -z "${ip// }" ]; then
                 continue
            fi

            host=$(nslookup $ip | grep "name =" | cut -f2 -d= | sed 's/\.$//' | sed 's/.//')

#            if [ -z "${country// }" ]; then
#                 echo "$ip"
#            else
#                 if [ -z "${asn// }" ]; then
#                     echo "$ip [$country]"
#                 else
#                     echo "$ip [$country, $asn]"
#                 fi
#            fi

            echo "<tr><td>$ip</td><td>$host</td><td>${code^^}</td><td>$asn</td></tr>"
        done <<< "$result_txt"

    echo "</tbody></table>"
}

#result_json=$(curl --silent "https://${api_domain}/dnsleak/test/${id}?json")
result_txt=$(curl --silent "https://${api_domain}/dnsleak/test/${id}?txt")

#dns_count=$(print_servers "dns" | wc -l)


#if [ "${dns_count}" -eq "0" ];then
#    echo_bold "No DNS servers found"
#else
#    print_servers "dns"
#fi

print_servers "dns"

exit 0
