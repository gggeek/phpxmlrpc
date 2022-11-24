#!/bin/sh

# Has to be run as admin

set -e

echo "Installing base software packages..."

DEBIAN_FRONTEND=noninteractive apt-get install -y \
    lsb-release sudo unzip wget

echo "Done installing base software packages"
