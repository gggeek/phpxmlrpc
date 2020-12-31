#!/bin/sh

# Installs php modules necessary to test the Perl file in the extras dir

# Has to be run as admin

set -e

DEBIAN_FRONTEND=noninteractive apt-get install -y \
    libexpat1-dev

yes | perl -MCPAN -e 'install XML::Parser'
yes | perl -MCPAN -e 'install Frontier::Client'
