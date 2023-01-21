ARG UBUNTU_VERSION=focal

FROM ubuntu:${UBUNTU_VERSION}

ARG PHP_VERSION=default

COPY setup/*.sh /root/setup/
COPY config/* /root/config/

RUN mkdir -p /usr/share/man/man1 && \
  apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y upgrade && \
  chmod 755 /root/setup/*.sh && \
  cd /root/setup && \
  ./install_packages.sh && \
  ./create_user.sh && \
  ./setup_apache.sh && \
  ./setup_privoxy.sh && \
  ./setup_php.sh "${PHP_VERSION}" && \
  ./setup_composer.sh

COPY docker/entrypoint.sh /root/entrypoint.sh
RUN chmod 755 /root/entrypoint.sh

EXPOSE 80 443 8080

# @todo can we avoid hardcoding this here? We can f.e. get it passed down as ARG...
WORKDIR /home/docker/workspace

ENTRYPOINT ["/root/entrypoint.sh"]
