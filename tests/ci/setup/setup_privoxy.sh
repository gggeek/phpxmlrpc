#!/bin/sh

# Install and configure privoxy
# Has to be run as root

set -e

echo "Installing privoxy..."

SCRIPT_DIR="$(dirname "$(readlink -f "$0")")"

export DEBIAN_FRONTEND=noninteractive

apt-get install -y privoxy

cp -f "$SCRIPT_DIR/../config/privoxy" /etc/privoxy/config
service privoxy restart

echo "Done installing Privoxy"
