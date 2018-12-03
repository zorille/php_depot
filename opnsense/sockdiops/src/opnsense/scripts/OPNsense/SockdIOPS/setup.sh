#!/bin/sh

AGENT_DIRS="/usr/local/etc/SockdIOPS"

for directory in ${AGENT_DIRS}; do
    mkdir -p ${directory}
    chown -R proxy:proxy ${directory}
    chmod -R 770 ${directory}
done

#We add our startup script
cp /usr/local/opnsense/scripts/OPNsense/SockdIOPS/sockdiops /usr/local/etc/rc.d/
if [ ! -e "/usr/local/sbin/sockdiops" ]; then
	mv /usr/local/sbin/sockd /usr/local/sbin/sockdiops
	chmod 555 /usr/local/sbin/sockdiops
	ln -s /usr/local/sbin/sockdiops /usr/local/sbin/sockd
fi

exit 0
