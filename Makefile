
tools/safeexec: tools/safeexec.c
	gcc tools/safeexec.c -o tools/safeexec

tools/boca-submit-run-root-wrapper: tools/boca-submit-run-root-wrapper.c
	gcc $^ -o $@

install-bocawww:
	mkdir -p  $(DESTDIR)/var/www/boca/
	cp -r src $(DESTDIR)/var/www/boca/
	cp -r doc $(DESTDIR)/var/www/boca/

install-bocaapache: install-bocawww
	mkdir -p $(DESTDIR)/etc/apache2/sites-enabled/
	cp tools/000-boca.conf $(DESTDIR)/etc/apache2/sites-enabled/000-boca.conf
	[ -f /etc/apache2/sites-available/default-ssl.conf ] && ln -s /etc/apache2/sites-available/default-ssl.conf /etc/apache2/sites-enabled/default-ssl.conf
	[ -f /etc/apache2/mods-available/ssl.load ] && ln -s /etc/apache2/mods-available/ssl.load /etc/apache2/mods-enabled/ssl.load
	[ -f /etc/apache2/mods-available/ssl.conf ] && ln -s /etc/apache2/mods-available/ssl.conf /etc/apache2/mods-enabled/ssl.conf
	[ -f /etc/apache2/mods-available/socache_shmcb.load ] && ln -s /etc/apache2/mods-available/socache_shmcb.load /etc/apache2/mods-enabled/socache_shmcb.load

install-scripts:
	mkdir -p $(DESTDIR)/usr/sbin/
	install tools/dump.sh $(DESTDIR)/usr/sbin/boca-dump
	install tools/boca-createjail $(DESTDIR)/usr/sbin/boca-createjail
	install tools/boca-createdb.sh $(DESTDIR)/usr/sbin/boca-createdb
	install tools/boca-autojudge.sh $(DESTDIR)/usr/sbin/boca-autojudge
	install tools/boca-config-dbhost.sh $(DESTDIR)/usr/sbin/boca-config-dbhost

install: install-bocawww install-bocaapache install-scripts tools/safeexec
	mkdir -p $(DESTDIR)/usr/bin/
	mkdir -p $(DESTDIR)/etc/
	cp tools/boca.conf $(DESTDIR)/etc/
	install tools/safeexec $(DESTDIR)/usr/bin/safeexec
	chmod 4555 $(DESTDIR)/usr/bin/safeexec

install-submission-tools: tools/boca-submit-run-root-wrapper
	mkdir -p $(DESTDIR)/usr/bin $(DESTDIR)/usr/sbin $(DESTDIR)/etc/cron.d
	install tools/boca-auth-runs $(DESTDIR)/usr/sbin/
	install tools/boca-submit-run $(DESTDIR)/usr/bin/
	install tools/boca-submit-run-cron $(DESTDIR)/usr/bin/
	install tools/boca-submit-run-aux $(DESTDIR)/usr/bin/
	install tools/boca-submit-run-root $(DESTDIR)/usr/bin/
	install tools/boca-submit-logroot $(DESTDIR)/usr/sbin/boca-submit-logroot
	install tools/cron-submit $(DESTDIR)/etc/cron.d/
	install tools/cron-logroot $(DESTDIR)/etc/cron.d/
	install tools/boca-submit-run-root-wrapper $(DESTDIR)/usr/bin/
	chmod 4555 $(DESTDIR)/usr/bin/boca-submit-run-root-wrapper
