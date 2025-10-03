#!/bin/sh

# Has to be run as root

set -e

echo "Installing base software packages..."

UPDATE_INSTALLED=false

export DEBIAN_FRONTEND=noninteractive

while getopts ":u" opt
do
    case $opt in
        u)
          UPDATE_INSTALLED=true
          ;;
        \?)
          echo "Invalid option: -$OPTARG" >&2
          exit 1
          ;;
    esac
done

if [ ! -d /usr/share/man/man1 ]; then mkdir -p /usr/share/man/man1; fi

apt-get update

if [ "$UPDATE_INSTALLED" = true ]; then
    apt-get upgrade -y
fi

apt-get install -y \
    git locales sudo unzip wget

# We set up one locale which uses the comma as decimal separator. Used by the testsuite
locale-gen de_DE de_DE.UTF-8

echo "Done installing base software packages"
