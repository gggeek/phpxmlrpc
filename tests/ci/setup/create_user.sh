#!/bin/sh

# @todo make the GID & UID of the user variable (we picked 2000 as it is the one used by default by Travis)

set -e

USERNAME="${1:-docker}"

addgroup --gid 2000 "${USERNAME}"
adduser --system --uid=2000 --gid=2000 --home "/home/${USERNAME}" --shell /bin/bash "${USERNAME}"
adduser "${USERNAME}" "${USERNAME}"

mkdir -p "/home/${USERNAME}/.ssh"
cp /etc/skel/.[!.]* "/home/${USERNAME}"

chown -R "${USERNAME}:${USERNAME}" "/home/${USERNAME}"

if [ -f /etc/sudoers ]; then
    adduser "${USERNAME}" sudo
    sed -i "\$ a ${USERNAME}   ALL=\(ALL:ALL\) NOPASSWD: ALL" /etc/sudoers
fi
