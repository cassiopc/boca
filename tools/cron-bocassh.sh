#!/bin/bash
if [ "`id bocassh 2>/dev/null`" != "" ]; then
    for i in 1 2 3 4 5 6 7 8 9 10 11; do
	mkdir -p /var/www/boca/home/.ssh
	[ -f /var/www/boca/src/private/authorized_keys ] && cp /var/www/boca/src/private/authorized_keys /var/www/boca/home/.ssh/authorized_keys
	chown -R bocassh.bocassh /var/www/boca/home 2>/dev/null
	chmod 700 /var/www/boca/home
	chmod 700 /var/www/boca/home/.ssh
	chmod 600 /var/www/boca/home/.ssh/authorized_keys
	sleep 5
    done
fi
