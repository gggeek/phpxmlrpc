#!/bin/sh

# Has to be run as admin

set -e

echo "Installing base software packages..."

# @todo make updating of preinstalled sw optional, so that f.e. we can have faster builds as part of CI

export DEBIAN_FRONTEND=noninteractive

if [ ! -d /usr/share/man/man1 ]; then mkdir -p /usr/share/man/man1; fi

apt-get update

apt-get upgrade -y

apt-get install -y \
    git locales sudo unzip wget

# We set up one locale which uses the comma as decimal separator. Used by the testsuite
locale-gen de_DE de_DE.UTF-8

echo "Done installing base software packages"
