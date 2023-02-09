#!/bin/sh

# Installs php modules necessary to test the Perl file in the extras dir

# Has to be run as admin

# @todo test in the VM env: do we need any ubuntu dev packages ?

set -e

echo "Installing Perl..."

DEBIAN_FRONTEND=noninteractive apt-get install -y \
    perl libexpat1-dev

yes | perl -MCPAN -e 'install XML::Parser'
yes | perl -MCPAN -e 'install Frontier::Client'

echo "Done installing Perl"
