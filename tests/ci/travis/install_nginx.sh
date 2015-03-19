#!/bin/sh

# enable nginx (to serve as proxy)

apt-get install nginx
sudo cp -f tests/ci/nginx.conf /etc/nginx/nginx.conf
sudo service nginx restart
