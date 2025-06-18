#!/bin/sh

# @todo make the GID & UID of the user variable (we picked 2000 as it is the one used by default by Travis)

set -e

echo "Creating user account..."

USERNAME="${1:-docker}"

# adduser is not preinstalled on noble
DEBIAN_FRONTEND=noninteractive apt-get install -y \
    adduser

# on ubuntu 24 noble at least, user ubuntu has id 1000, which clashes with our custom users later on
if [ -d /home/ubuntu ]; then
    userdel ubuntu
    rm -rf ubuntu
fi

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

echo "Done creating user account"
