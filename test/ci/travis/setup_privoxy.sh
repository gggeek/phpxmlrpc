#!/bin/sh

# configure privoxy

sudo cp -f test/ci/travis/privoxy /etc/privoxy/config
sudo service privoxy restart
