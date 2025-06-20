#!/bin/sh

# Has to be run as admin

set -e

echo "Installing base software packages..."

# @todo allow optional updating of preinstalled sw

apt-get update

# adduser is not preinstalled on noble
DEBIAN_FRONTEND=noninteractive apt-get install -y \
    locales sudo unzip wget

# We set up one locale which uses the comma as decimal separator. Used by the testsuite
locale-gen de_DE de_DE.UTF-8

# In case we will be using php5.x from shivammatur, it has a dependency on a lib missing from bionic
# `lsb-release` is not necessarily onboard. We parse /etc/os-release instead
DEBIAN_VERSION=$(grep 'VERSION_CODENAME=' /etc/os-release | sed 's/VERSION_CODENAME=//')
if [ -z "${DEBIAN_VERSION}" ]; then
    # Example strings:
    # VERSION="14.04.6 LTS, Trusty Tahr"
    # VERSION="18.04.6 LTS (Bionic Beaver)"
    # VERSION="8 (jessie)"
    DEBIAN_VERSION="$(grep 'VERSION=' /etc/os-release | sed 's/VERSION= *//' | sed 's/ *["0-9.,()]\+ *//g'| tr '[:upper:]' '[:lower:]' | sed 's/ *lts *//' | cut -d' ' -f 1)"
fi
if [ "$DEBIAN_VERSION" = bionic ]; then
    wget https://launchpad.net/~ubuntu-security/+archive/ubuntu/ppa/+build/15108504/+files/libpng12-0_1.2.54-1ubuntu1.1_amd64.deb
    dpkg --install libpng12-0_1.2.54-1ubuntu1.1_amd64.deb
    rm libpng12-0_1.2.54-1ubuntu1.1_amd64.deb
fi

echo "Done installing base software packages"
