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

echo "Done installing base software packages"
