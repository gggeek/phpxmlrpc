#!/bin/sh

# @todo support getting the 2 vars as cli options as well as via env vars?

set -e

ACTION="${1}"

# Valid values: 'default', 5.6, 7.0 .. 7.4, 8.0
export PHP_VERSION=${PHP_VERSION:-default}
# Valid values: precise (12), trusty (14), xenial (16), bionic (18), focal (20)
# We default to the same version we use on Travis
export UBUNTU_VERSION=${UBUNTU_VERSION:-xenial}

CONTAINER_USER=docker
CONTAINER_BUILD_DIR="/home/${CONTAINER_USER}/build"
ROOT_DIR="$(dirname -- "$(dirname -- "$(dirname -- "$(readlink -f "$0")")")")"
IMAGE_NAME=phpxmlrpc:${UBUNTU_VERSION}-${PHP_VERSION}
CONTAINER_NAME=phpxmlrpc_${UBUNTU_VERSION}_${PHP_VERSION}

cd "$(dirname -- "$(readlink -f "$0")")"

help() {
printf "Usage: vm.sh [OPTIONS] ACTION [OPTARGS]

Manages the Test Environment Docker Stack

Commands:
    build             build or rebuild the containers and set up the test env
    cleanup           removes the docker containers and their images
    enter             enter the test container
    inspect
    logs
    ps
    runtests [\$suite] execute the test suite using the test container (or a single test scenario eg. tests/1ParsingBugsTest.php)
    runcoverage       execute the test suite and generate a code coverage report (in build/coverage)
    start             start the containers
    stop              stop containers
    top

Options:
    -h                print help

Environment variables: to be set before the 'build' action
    PHP_VERSION       default value: 'default', ie. the stock php version from the Ubuntu version in use. Other possible values: 7.0 .. 8.0
    UBUNTU_VERSION    default value: xenial. Other possible values: bionic, focal
"
}

build() {
    stop
    docker build --build-arg PHP_VERSION --build-arg UBUNTU_VERSION -t "${IMAGE_NAME}" .
    if docker inspect "${CONTAINER_NAME}" >/dev/null 2>/dev/null; then
        docker rm "${CONTAINER_NAME}"
    fi
    docker run -d \
        -p 80:80 -p 443:443 -p 8080:8080 \
        --name "${CONTAINER_NAME}" \
        --env CONTAINER_USER_UID=$(id -u) --env CONTAINER_USER_GID=$(id -g) \
        --env TESTS_ROOT_DIR=${CONTAINER_BUILD_DIR} \
        --env HTTPSERVER=localhost \
        --env HTTPURI=/demo/server/server.php \
        --env HTTPSSERVER=localhost \
        --env HTTPSURI=/demo/server/server.php \
        --env PROXYSERVER=localhost:8080 \
        --env HTTPSVERIFYHOST=0 \
        --env HTTPSIGNOREPEER=1 \
        --env SSLVERSION=0 \
        --env DEBUG=0 \
        -v "${ROOT_DIR}":"${CONTAINER_BUILD_DIR}" \
         "${IMAGE_NAME}"
}

start() {
    if docker inspect "${CONTAINER_NAME}" >/dev/null 2>/dev/null; then
        docker start "${CONTAINER_NAME}"
    else
        build
    fi
}

stop() {
    if docker inspect "${CONTAINER_NAME}" >/dev/null 2>/dev/null; then
        docker stop "${CONTAINER_NAME}"
    fi
}

case "${ACTION}" in

    build)
        build
        stop
        ;;

    cleanup)
        docker rm "${CONTAINER_NAME}"
        docker rmi "${IMAGE_NAME}"
        ;;

    enter | shell | cli)
        # @todo allow login as root
        docker exec -it "${CONTAINER_NAME}" su "${CONTAINER_USER}"
        ;;

    # @todo implement
    #exec)
    #    ;;

    restart)
        stop
        start
        ;;

    runcoverage)
        # @todo clean up /tmp/phpxmlrpc and .phpunit.result.cache
        if [ ! -d build ]; then mkdir build; fi
        docker exec -t "${CONTAINER_NAME}" /home/${CONTAINER_USER}/build/tests/ci/setup/setup_code_coverage.sh enable
        docker exec -it "${CONTAINER_NAME}" su "${CONTAINER_USER}" -c "./vendor/bin/phpunit --coverage-html build/coverage -v tests"
        docker exec -t "${CONTAINER_NAME}" /home/${CONTAINER_USER}/build/tests/ci/setup/setup_code_coverage.sh disable
        ;;

    runtests)
        docker exec -it "${CONTAINER_NAME}" su "${CONTAINER_USER}" -c "./vendor/bin/phpunit -v tests"
        ;;

    start)
        start
        ;;

    #status)
    #    :
    #    ;;

    stop)
        stop
        ;;

    ps)
        docker ps --filter "name=${CONTAINER_NAME}"
        ;;

    diff | inspect | kill | logs | pause | port | stats | top | unpause)
        docker container "${ACTION}" "${CONTAINER_NAME}"
        ;;

    *)
        printf "\n\e[31mERROR:\e[0m unknown action '${ACTION}'\n\n" >&2
        help
        exit 1
        ;;
esac
