#!/usr/bin/env bash

set -e

VM_CMD='./vm.sh'
LOGS_DIR='./var/logs/matrix'

# @todo use better config for this. If yq is present, we could eg. parse the github config
OS_LIST=${OS_LIST:-focal jammy noble}
# NB: as of 2025/06, the php install scripts fail for focal with php 5.6, 7.x except 7.4, and 8.x
# Default php versions: focal 7.4.3, jammy 8.1.1, noble 8.3.6
PHP_LIST_focal=${PHP_LIST_focal:-default 5.4}
PHP_LIST_jammy=${PHP_LIST_jammy:-default 5.5 7.1 7.3 8.2 8.3}
PHP_LIST_noble=${PHP_LIST_noble:-default 5.6 7.2 7.4 8.1 8.4}

STARTING_HTTPPORT="${STARTING_HTTPPORT:-81}"
STARTING_HTTPSPORT="${STARTING_HTTPSPORT:-444}"
STARTING_PROXYPORT="${STARTING_PROXYPORT:-8081}"

ACTION="${1}"

cd "$(dirname -- "$(readlink -f "$0")")"

help() {
    printf "Usage: matrix.sh [OPTIONS] ACTION [OPTARGS]

Builds and runs a Test Matrix (ie. using different OS/PHP versions)

Commands:
    build             build or rebuild the containers and set up the test env
    runtests [\$suite] execute the test suite using the test containers (or a single test scenario eg. tests/1ParsingBugsTest.php)
    cleanup           removes the docker containers and their images
    exec \$command
    inspect
    logs
    pause
    ps
    start
    stop
    top
    unpause

Options:
    -h                print help

Environment variables:
  see output of 'vm.sh -h'
"
}

loop() {
    if [ ! -d "$LOGS_DIR" ]; then
        mkdir -p "$LOGS_DIR"
    fi
    export HOST_HTTPPORT="$STARTING_HTTPPORT"
    export HOST_HTTPSPORT="$STARTING_HTTPSPORT"
    export HOST_PROXYPORT="$STARTING_PROXYPORT"
    if [ "$1" = build ] || [ "$1" = start ] || [ "$1" = stop ] || [ "$1" = pause ] || [ "$1" = unpause ]; then
        PARALLEL=true
    fi
    for ubuntu_version in ${OS_LIST}
    do
        php_var="PHP_LIST_${ubuntu_version}"
        set +e
        for php_version in ${!php_var}
        do
            export UBUNTU_VERSION=$ubuntu_version
            export PHP_VERSION=$php_version
            case "${ACTION}" in
                build)
                    $VM_CMD build 2>"${LOGS_DIR}/${ubuntu_version}_${php_version}.build.log" &
                    ;;
                start | stop | pause | unpause)
                    ### @todo force start process not to run composer
                    $VM_CMD $1 &
                    ;;
                runtests | diff | inspect | kill | logs | port | ps | top)
                    ### @todo force runtests process to run composer; clean that up after testing
                    $VM_CMD $1
                    ;;
                exec)
                    $VM_CMD "$@"
                    ;;
            esac
            export HOST_HTTPPORT=$((HOST_HTTPPORT + 1))
            export HOST_HTTSPPORT=$((HOST_HTTPSPORT + 1))
            export HOST_PROXYPORT=$((HOST_PROXYPORT + 1))
        done
        set -e
    done
    if [ "$PARALLEL" = true ]; then
        wait
    fi
}

case "${ACTION}" in

    exec)
        shift
        loop exec "$@"
        ;;

    build | start | runtests | stop | cleanup | diff | inspect | kill | logs | pause | port | ps | top | unpause)
        loop "${ACTION}"
        ;;

    *)
        printf "\n\e[31mERROR:\e[0m unknown action '%s'\n\n" "${ACTION}" >&2
        help
        exit 1
        ;;
esac
