#!/bin/sh

# @todo set up the same user for running tests as on travis (ie. 'travis'), or maybe user 'user' ?
# @todo make the GID & UID of the user variable

set -e

USERNAME="${1:-test}"

addgroup --gid 1013 "${USERNAME}"
adduser --system --uid=1013 --gid=1013 --home "/home/${USERNAME}" --shell /bin/bash "${USERNAME}"
adduser "${USERNAME}" "${USERNAME}"

mkdir -p "/home/${USERNAME}/.ssh"
cp /etc/skel/.[!.]* "/home/${USERNAME}"

chown -R "${USERNAME}:${USERNAME}" "/home/${USERNAME}"

if [ -f /etc/sudoers ]; then
    adduser "${USERNAME}" sudo
    sed -i "\$ a ${USERNAME}   ALL=\(ALL:ALL\) NOPASSWD: ALL" /etc/sudoers
fi
