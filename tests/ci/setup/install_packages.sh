#!/bin/sh

# Has to be run as admin

set -e

echo "Installing base software packages..."

apt-get update

DEBIAN_FRONTEND=noninteractive apt-get install -y \
    locales sudo unzip wget

# We set up one locale which uses the comma as decimal separator. Used by the testsuite
locale-gen de_DE de_DE.UTF-8

echo "Done installing base software packages"
