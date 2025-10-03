#!/bin/sh

# @todo rename: this is not based on a vm. Also, the 'ci' folder should really be called 'env' or 'testenv'...
# @todo support getting the various settings as cli options as well as via env vars (use getopts)

set -e

ACTION="${1}"

# Valid values: 'default', 5.4 .. 5.6, 7.0 .. 7.4, 8.0 .. 8.5
export PHP_VERSION=${PHP_VERSION:-default}
# Valid values: precise (12), trusty (14), xenial (16), bionic (18), focal (20), jammy (22), noble (24)
# For end of support dates, see: https://wiki.ubuntu.com/Releases
export UBUNTU_VERSION=${UBUNTU_VERSION:-jammy}

HTTPSVERIFYHOST="${HTTPSVERIFYHOST:-0}"
HTTPSIGNOREPEER="${HTTPSIGNOREPEER:-1}"
SSLVERSION="${SSLVERSION:-0}"
DEBUG="${DEBUG:-0}"

HOST_HTTPPORT="${HOST_HTTPPORT:-80}"
HOST_HTTPSPORT="${HOST_HTTPSPORT:-443}"
HOST_PROXYPORT="${HOST_PROXYPORT:-8080}"

CONTAINER_INSTALL_ON_START="${CONTAINER_INSTALL_ON_START:-false}"
CONTAINER_NAME_PREFIX="${CONTAINER_NAME_PREFIX:-phpxmlrpc}"
CONTAINER_IMAGE_PREFIX="${CONTAINER_IMAGE_PREFIX:-phpxmlrpc_}"
CONTAINER_USER=docker
CONTAINER_WORKSPACE_DIR="/home/${CONTAINER_USER}/workspace"

IMAGE_NAME="${CONTAINER_NAME_PREFIX}:${UBUNTU_VERSION}-${PHP_VERSION}"
CONTAINER_NAME="${CONTAINER_IMAGE_PREFIX}${UBUNTU_VERSION}_${PHP_VERSION}"

ROOT_DIR="$(dirname -- "$(dirname -- "$(dirname -- "$(readlink -f "$0")")")")"

cd "$(dirname -- "$(readlink -f "$0")")"

help() {
    printf "Usage: vm.sh [OPTIONS] ACTION [OPTARGS]

Manages the Test Environment (a Docker Container)

Main actions:
    build             build or rebuild the container image with the test env
    cleanup           remove the container and its image
    enter             start a shell session in the running container
    exec [\$command]   run a single command in the running container
    runtests [\$suite] execute the test suite using the test container (or a single test scenario eg. tests/02ValueTest.php);
                      build and start the container if required
    runcoverage       execute the test suite and generate a code coverage report (in build/coverage);
                      build and start the container if required
    start             start the container; build it if required
    stop              stop the container
    top

Actions for troubleshooting the container:
    inspect, logs, port, ps, stats, top

Options:
    -h                print help

Environment variables:
  used by the 'build' action
    PHP_VERSION       default value: 'default', ie. the stock php version from the Ubuntu version in use. Other possible values: 5.6, 7.0 .. 7.4, 8.0 .. 8.4
    UBUNTU_VERSION    default value: jammy. Other possible values: xenial, bionic, focal, noble
  used by the 'start' action
    HOST_HTTPPORT     default value: 80. Set to 'no' not to publish the container's http port to the host
    HOST_HTTPSPORT    default value: 443. Set to 'no' not to publish the container's https port to the host
    HOST_PROXYPORT    default value: 8080. Set to 'no' not to publish the container's proxy http port to the host
  used by the 'runtests' and 'runcoverage' actions:
    HTTPSVERIFYHOST   0, 1 or 2. Default and recommended: 0
    HTTPSIGNOREPEER   0 or 1. Default and recommended: 1
    SSLVERSION        0 (auto), 2 (SSLv2) to 7 (tls 1.3). Default: 0
    DEBUG             0 - 3. Default: 0
"
}

build() {
    if docker build --build-arg PHP_VERSION --build-arg UBUNTU_VERSION -t "${IMAGE_NAME}" .; then
        if [ "$1" = '-r' ]; then
            # stop and remove existing containers built from a previous version of this image
            if docker inspect "${CONTAINER_NAME}" >/dev/null 2>/dev/null; then
                stop -q
                docker rm "${CONTAINER_NAME}"
            fi
        fi
    fi
}

start() {
    if [ "$(docker inspect --format '{{.State.Status}}' "${CONTAINER_NAME}" 2>/dev/null)" = running ]; then
        # @todo we should check that the env vars have not changed since cont. start, and give a warning if so.
        #       Doable using `docker container cp` to retrieve the /etc/build-info file...
        echo "${CONTAINER_NAME} already started..."
    else
        if docker inspect "${CONTAINER_NAME}" >/dev/null 2>/dev/null; then
            echo "starting existing container ${CONTAINER_NAME}..."
            # @todo we should check that the env vars have not changed since cont. creation, and give a warning if so.
            #       Doable using `docker container inspect`...
            if docker start "${CONTAINER_NAME}"; then
                wait_for_bootstrap
            fi
        else
            build

            PORTMAPPING=''
            # @todo improve error message and abort in case any port is not an integer or negative
            if [ "$HOST_HTTPPORT" != no ] && [ "$HOST_HTTPPORT" != '' ]; then
                PORTMAPPING="-p $((HOST_HTTPPORT-0)):80 "
            fi
            if [ "$HOST_HTTPSPORT" != no ] && [ "$HOST_HTTPSPORT" != '' ]; then
                PORTMAPPING="${PORTMAPPING}-p $((HOST_HTTPSPORT)):443 "
            fi
            if [ "$HOST_PROXYPORT" != no ] && [ "$HOST_PROXYPORT" != '' ]; then
                PORTMAPPING="-p $((HOST_PROXYPORT-0)):8080 "
            fi

            if [ ! -d "${ROOT_DIR}/tests/ci/var/composer_cache" ]; then mkdir -p "${ROOT_DIR}/tests/ci/var/composer_cache"; fi

            if docker run -d \
                $PORTMAPPING \
                --name "${CONTAINER_NAME}" \
                --env "CONTAINER_USER_UID=$(id -u)" --env "CONTAINER_USER_GID=$(id -g)" \
                --env "TESTS_ROOT_DIR=${CONTAINER_WORKSPACE_DIR}" \
                --env "INSTALL_ON_START=${CONTAINER_INSTALL_ON_START}" \
                --env HTTPSERVER=localhost \
                --env HTTPURI=/tests/index.php?demo=server/server.php \
                --env HTTPSSERVER=localhost \
                --env HTTPSURI=/tests/index.php?demo=server/server.php \
                --env PROXYSERVER=localhost:8080 \
                -v "${ROOT_DIR}:${CONTAINER_WORKSPACE_DIR}" \
                -v "${ROOT_DIR}/tests/ci/var/composer_cache:/home/${CONTAINER_USER}/.cache/composer" \
                 "${IMAGE_NAME}"; then
                wait_for_bootstrap
            fi
        fi
    fi
}

wait_for_bootstrap() {
    I=0
    while [ $I -le 60 ]; do
        if [ -f "${ROOT_DIR}/tests/ci/var/bootstrap_ok_${UBUNTU_VERSION}_${PHP_VERSION}" ]; then
            echo ''
            break;
        fi
        printf '.'
        sleep 1
        I=$((I+1))
    done
    if [ $I -gt 60 ]; then
        echo ''
        echo "ERROR: Container did not finish bootstrapping within 60 seconds..." >&2
        return 1
    fi
    return 0
}

stop() {
    if [ "$(docker inspect --format '{{.State.Status}}' "${CONTAINER_NAME}" 2>/dev/null)" = exited ]; then
        if [ "$1" != -q ]; then
            echo "${CONTAINER_NAME} already stopped"
        fi
    else
        if docker inspect "${CONTAINER_NAME}" >/dev/null 2>/dev/null; then
            if [ "$1" != -q ]; then
                echo "stopping ${CONTAINER_NAME}..."
            fi
            docker stop "${CONTAINER_NAME}"
        fi
    fi
}

runtests() {
    # @todo allow auto-deleting the container after execution - use either a cli option or an env var?
    if [ "$(docker inspect --format '{{.State.Status}}' "${CONTAINER_NAME}" 2>/dev/null)" != running ]; then
        start
    fi
    if [ -z "$1" ]; then
        TESTSUITE=tests
    else
        TESTSUITE="$*"
    fi
    test -t 1 && USE_TTY="-t"
    lock
    trap unlock INT
    RETCODE=0
    {
        docker exec $USE_TTY "${CONTAINER_NAME}" /root/setup/setup_app.sh "${CONTAINER_WORKSPACE_DIR}"
        docker exec -i $USE_TTY \
            --env "HTTPSVERIFYHOST=${HTTPSVERIFYHOST}" \
            --env "HTTPSIGNOREPEER=${HTTPSIGNOREPEER}" \
            --env "SSLVERSION=${SSLVERSION}" \
            --env DEBUG="${DEBUG}" \
            "${CONTAINER_NAME}" su "${CONTAINER_USER}" -c "./vendor/bin/phpunit -v $TESTSUITE"
    } || {
        RETCODE="$?"
    }
    unlock
    return $RETCODE
}

runcoverage() {
    # @todo allow auto-deleting the container after execution - use either a cli option or an env var?
    if [ "$(docker inspect --format '{{.State.Status}}' "${CONTAINER_NAME}" 2>/dev/null)" != running ]; then
        start
    fi
    # @todo double-check if setup_code_coverage.sh does always need a tty (`-t`). If so, abort if `test -t 1` fails
    test -t 1 && USE_TTY="-t"
    RETCODE=0
    lock
    trap unlock INT
    {
        # @todo clean up /tmp/phpxmlrpc_coverage and .phpunit.result.cache (in setup_code_coverage.sh?)
        docker exec $USE_TTY "${CONTAINER_NAME}" /root/setup/setup_app.sh "${CONTAINER_WORKSPACE_DIR}" || true
        if [ ! -d ./var/coverage ]; then mkdir -p ./var/coverage; fi
        docker exec -t "${CONTAINER_NAME}" /root/setup/setup_code_coverage.sh enable
        docker exec -i $USE_TTY \
            --env "HTTPSVERIFYHOST=${HTTPSVERIFYHOST}" \
            --env "HTTPSIGNOREPEER=${HTTPSIGNOREPEER}" \
            --env "SSLVERSION=${SSLVERSION}" \
            --env DEBUG="${DEBUG}" \
            "${CONTAINER_NAME}" su "${CONTAINER_USER}" -c "./vendor/bin/phpunit --coverage-html tests/ci/var/coverage -v tests"
        docker exec -t "${CONTAINER_NAME}" /root/setup/setup_code_coverage.sh disable
    } || {
       RETCODE="$?"
    }
    unlock
    return $RETCODE
}

lock() {
    if [ -f ./var/tests_executing.lock ]; then
        echo "ERROR: tests are already running - or there is a leftover lock file. Use 'unlock' action to remove it" >&2
        exit 1
    else
        touch ./var/tests_executing.lock
    fi
}

unlock() {
    if [ -f ./var/tests_executing.lock ]; then
        rm ./var/tests_executing.lock;
    fi
}

case "${ACTION}" in

    build)
        build -r
        ;;

    cleanup)
        # @todo allow to cleanup tests/ci/var completely - use either a cli option or a separate action?
        # @todo allow to only remove the container but not the image - use either a cli option or a separate action?
        if docker inspect "${CONTAINER_NAME}" >/dev/null 2>/dev/null; then
            stop -q
            docker rm "${CONTAINER_NAME}"
        fi
        docker rmi "${IMAGE_NAME}"
        ;;

    enter | shell | cli)
        # @todo allow login as root - use either a cli option or a separate action?
        docker exec -it \
            --env "HTTPSVERIFYHOST=${HTTPSVERIFYHOST}" \
            --env "HTTPSIGNOREPEER=${HTTPSIGNOREPEER}" \
            --env "SSLVERSION=${SSLVERSION}" \
            --env DEBUG="${DEBUG}" \
            "${CONTAINER_NAME}" su "${CONTAINER_USER}"
        ;;

    exec)
        shift
        test -t 1 && USE_TTY="-t"
        docker exec -i $USE_TTY \
            --env "HTTPSVERIFYHOST=${HTTPSVERIFYHOST}" \
            --env "HTTPSIGNOREPEER=${HTTPSIGNOREPEER}" \
            --env "SSLVERSION=${SSLVERSION}" \
            --env DEBUG="${DEBUG}" \
            "${CONTAINER_NAME}" su "${CONTAINER_USER}" -c '"$0" "$@"' -- "$@"
            # @todo which one is better? test with a command with spaces in options values, and with a composite command such as cd here && do that
            #"${CONTAINER_NAME}" sudo -iu "${CONTAINER_USER}" -- "$@"
        ;;

    restart)
        stop
        start
        ;;

    runcoverage)
        runcoverage
        ;;

    runtests)
        shift
        runtests "$@"
        ;;

    start)
        start
        ;;

    stop)
        stop
        ;;

    ps)
        docker ps --filter "name=${CONTAINER_NAME}"
        ;;

    diff | inspect | kill | logs | pause | port | stats | top | unpause)
        docker container "${ACTION}" "${CONTAINER_NAME}"
        ;;

    unlock)
        unlock
        ;;

    -h)
        help
        exit 0
        ;;

    *)
        printf "\n\e[31mERROR:\e[0m unknown action '%s'\n\n" "${ACTION}" >&2
        help
        exit 1
        ;;
esac
