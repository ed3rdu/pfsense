#!/bin/sh

set -eu
mkdir -p "/usr/local/share/ntopng/httpdocs/geoip"

TEMPDIR="$(mktemp -d "/usr/local/share/ntopng/httpdocs/geoip/MMDB-XXXXXX")"
trap 'rc=$? ; set +e ; rm -rf "'"$TEMPDIR"'" ; exit $rc' 0

cd "${TEMPDIR}"

# arguments:
# $1 URL
# $2 filename
_fetchextract() {
	url="$1"
	file="$(basename "${url}")"

	if fetch "${url}"; then
		tar xzf "${file}"
	else
		echo "${file} download failed"
		return 1
	fi

	return 0
}

# Get the license key from the GeoIP.conf file
LICENSE_KEY=$(awk -F ' ' '/^#/ {next} $1=="LicenseKey" {print $2}' /usr/local/etc/GeoIP.conf)

echo Fetching GeoLite2-City
_fetchextract "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=${LICENSE_KEY}&suffix=tar.gz"

echo Fetching GeoLite2-ASN
_fetchextract "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-ASN&license_key=${LICENSE_KEY}&suffix=tar.gz"

mv GeoLite2-*/*.mmdb /usr/local/share/ntopng/httpdocs/geoip

cd /usr/local/share/ntopng/httpdocs/geoip
rm -rf "${TEMPDIR}"

chown root:wheel *.mmdb
chmod 444 *.mmdb

trap - 0

return 0
