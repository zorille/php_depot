#!/bin/sh

AGENT_DIRS="/usr/local/etc/xinetd.d"

for directory in ${AGENT_DIRS}; do
    mkdir -p ${directory}
    chmod -R 770 ${directory}
done
#We add our startup script
cp /usr/local/opnsense/scripts/OPNsense/Xinetd/xinetd /usr/local/etc/rc.d/

exit 0
