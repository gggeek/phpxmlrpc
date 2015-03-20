#!/bin/sh

# enable nginx (to serve as proxy)

sudo apt-get install nginx
sudo cp -f tests/ci/travis/nginx.conf /etc/nginx/nginx.conf
sudo service nginx restart
