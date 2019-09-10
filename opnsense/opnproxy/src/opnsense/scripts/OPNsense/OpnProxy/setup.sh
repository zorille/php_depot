#!/bin/sh

AGENT_DIRS="/usr/local/etc/SockdIOPS"

for directory in ${AGENT_DIRS}; do
    mkdir -p ${directory}
    chown -R proxy:proxy ${directory}
    chmod -R 770 ${directory}
done

/usr/sbin/pkg list sockdiops > /dev/null
if [ $? -ne 0 ]; then
	>&2 echo "Package SockdIOPS missing"
	exit 1
	#we install sockdiops package
	#/usr/sbin/pkg add /usr/local/opnsense/scripts/OPNsense/SockdIOPS/sockdiops-1.0.0.txz
fi
#We add our startup script
cp /usr/local/opnsense/scripts/OPNsense/SockdIOPS/sockdiops /usr/local/etc/rc.d/
if [ ! -e "/usr/local/sbin/sockdiops" ]; then
	mv /usr/local/sbin/sockd /usr/local/sbin/sockdiops
	chmod 555 /usr/local/sbin/sockdiops
	ln -s /usr/local/sbin/sockdiops /usr/local/sbin/sockd
else
    if [ ! -L "/usr/local/sbin/sockd" ]; then
	rm -f /usr/local/sbin/sockdiops
	mv /usr/local/sbin/sockd /usr/local/sbin/sockdiops
	chmod 555 /usr/local/sbin/sockdiops
	ln -s /usr/local/sbin/sockdiops /usr/local/sbin/sockd
    fi
fi


exit 0
