#!/bin/sh

set -e

# install and configure privoxy

SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"

DEBIAN_FRONTEND=noninteractive apt-get install -y privoxy

cp -f "$SCRIPT_DIR/../config/privoxy" /etc/privoxy/config
service privoxy restart
