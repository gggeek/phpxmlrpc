#!/bin/sh

# @todo support getting the 2 vars as cli options as well as via env vars?

set -e

ACTION="${1}"

# Valid values: 'default', 5.6, 7.0 .. 7.4, 8.0 .. 8.4
export PHP_VERSION=${PHP_VERSION:-default}
# Valid values: precise (12), trusty (14), xenial (16), bionic (18), focal (20), jammy (22), noble (24)
export UBUNTU_VERSION=${UBUNTU_VERSION:-jammy}

HTTPSVERIFYHOST="${HTTPSVERIFYHOST:-0}"
HTTPSIGNOREPEER="${HTTPSIGNOREPEER:-1}"
SSLVERSION="${SSLVERSION:-0}"

CONTAINER_USER=docker
CONTAINER_WORKSPACE_DIR="/home/${CONTAINER_USER}/workspace"
ROOT_DIR="$(dirname -- "$(dirname -- "$(dirname -- "$(readlink -f "$0")")")")"
# @todo (low priority) allow passing in a custom prefix for image name, container name
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

Environment variables:
  to be set before the 'build' action
    PHP_VERSION       default value: 'default', ie. the stock php version from the Ubuntu version in use. Other possible values: 5.6, 7.0 .. 7.4, 8.0 .. 8.4
    UBUNTU_VERSION    default value: jammy. Other possible values: xenial, bionic, focal, noble
  can also be set before the 'runtests' and 'runcoverage' actions:
    HTTPSVERIFYHOST   0, 1 or 2. Default and recommended: 0
    HTTPSIGNOREPEER   0 or 1. Default and recommended: 1
    SSLVERSION        0 (auto), 2 (SSLv2) to 7 (tls 1.3). Default: 0
"
}

wait_for_bootstrap() {
    I=0
    while [ $I -le 60 ]; do
        if [ -f "${ROOT_DIR}/tests/ci/var/bootstrap_ok" ]; then
            echo ''
            break;
        fi
        printf '.'
        sleep 1
        I=$((I+1))
    done
    if [ $I -eq 60 ]; then
        echo "ERROR: Container did not finish bootstrapping within 60 seconds..." >&2
        return 1
    fi
    return 0
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
        --env "CONTAINER_USER_UID=$(id -u)" --env "CONTAINER_USER_GID=$(id -g)" \
        --env "TESTS_ROOT_DIR=${CONTAINER_WORKSPACE_DIR}" \
        --env HTTPSERVER=localhost \
        --env HTTPURI=/tests/index.php?demo=server/server.php \
        --env HTTPSSERVER=localhost \
        --env HTTPSURI=/tests/index.php?demo=server/server.php \
        --env PROXYSERVER=localhost:8080 \
        --env "HTTPSVERIFYHOST=${HTTPSVERIFYHOST}" \
        --env "HTTPSIGNOREPEER=${HTTPSIGNOREPEER}" \
        --env "SSLVERSION=${SSLVERSION}" \
        --env DEBUG=0 \
        -v "${ROOT_DIR}":"${CONTAINER_WORKSPACE_DIR}" \
         "${IMAGE_NAME}"

    if [ $? -eq 0 ]; then
        wait_for_bootstrap
    fi
}

start() {
    if docker inspect "${CONTAINER_NAME}" >/dev/null 2>/dev/null; then
        docker start "${CONTAINER_NAME}"
        if [ $? -eq 0 ]; then
            wait_for_bootstrap
        fi
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
        test -t 1 && USE_TTY="-t"
        # @todo clean up /tmp/phpxmlrpc and .phpunit.result.cache
        if [ ! -d build ]; then mkdir build; fi
        docker exec -t "${CONTAINER_NAME}" "${CONTAINER_WORKSPACE_DIR}/tests/ci/setup/setup_code_coverage.sh" enable
        docker exec -i $USE_TTY \
            --env "HTTPSVERIFYHOST=${HTTPSVERIFYHOST}" \
            --env "HTTPSIGNOREPEER=${HTTPSIGNOREPEER}" \
            --env "SSLVERSION=${SSLVERSION}" \
            "${CONTAINER_NAME}" su "${CONTAINER_USER}" -c "./vendor/bin/phpunit --coverage-html build/coverage -v tests"
        docker exec -t "${CONTAINER_NAME}" "${CONTAINER_WORKSPACE_DIR}/tests/ci/setup/setup_code_coverage.sh" disable
        ;;

    runtests)
        test -t 1 && USE_TTY="-t"
        docker exec -i $USE_TTY \
            --env "HTTPSVERIFYHOST=${HTTPSVERIFYHOST}" \
            --env "HTTPSIGNOREPEER=${HTTPSIGNOREPEER}" \
            --env "SSLVERSION=${SSLVERSION}" \
            "${CONTAINER_NAME}" su "${CONTAINER_USER}" -c "./vendor/bin/phpunit -v tests"
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
