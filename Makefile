
tools/safeexec: tools/safeexec.c
	gcc -g -O2 $^ -o $@

tools/boca-submit-run-root-wrapper: tools/boca-submit-run-root-wrapper.c
	gcc -g -O2 $^ -o $@

install-bocawww:
	mkdir -p $(DESTDIR)/usr/sbin $(DESTDIR)/etc/cron.d $(DESTDIR)/var/www/boca/
	cp -r src $(DESTDIR)/var/www/boca/
	cp -r doc $(DESTDIR)/var/www/boca/
	install tools/boca-fixssh $(DESTDIR)/usr/sbin/
	install tools/cron-boca-fixssh $(DESTDIR)/etc/cron.d/
	chmod 700 $(DESTDIR)/usr/sbin/boca-fixssh

install-bocaapache:
	mkdir -p $(DESTDIR)/etc/apache2/sites-available/
	cp tools/000-boca.conf $(DESTDIR)/etc/apache2/sites-available/000-boca.conf

install-scripts:
	mkdir -p $(DESTDIR)/usr/sbin/
	install tools/dump.sh $(DESTDIR)/usr/sbin/boca-dump
	chmod 700 $(DESTDIR)/usr/sbin/boca-dump

install-bocadb:
	mkdir -p $(DESTDIR)/usr/sbin/
	mkdir -p $(DESTDIR)/etc
	cp -r tools/postgresql $(DESTDIR)/etc
	install tools/boca-createdb.sh $(DESTDIR)/usr/sbin/boca-createdb
	chmod 700 $(DESTDIR)/usr/sbin/boca-createdb

install-bocacommon: install-bocawww
	mkdir -p $(DESTDIR)/usr/sbin/
	mkdir -p $(DESTDIR)/etc/
	cp tools/boca.conf $(DESTDIR)/etc/
	install tools/boca-config-dbhost.sh $(DESTDIR)/usr/sbin/boca-config-dbhost
	chmod 700 $(DESTDIR)/usr/sbin/boca-config-dbhost

install-bocaautojudge: tools/safeexec
	mkdir -p $(DESTDIR)/usr/sbin/
	mkdir -p $(DESTDIR)/usr/bin/
	mkdir -p $(DESTDIR)/etc/
	install tools/safeexec $(DESTDIR)/usr/bin/safeexec
	install tools/boca-createjail $(DESTDIR)/usr/sbin/boca-createjail
	install tools/boca-autojudge.sh $(DESTDIR)/usr/sbin/boca-autojudge
	chmod 4555 $(DESTDIR)/usr/bin/safeexec
	chmod 700 $(DESTDIR)/usr/sbin/boca-createjail
	chmod 700 $(DESTDIR)/usr/sbin/boca-autojudge

install: install-bocawww install-bocaapache install-bocadb install-bocacommon install-bocaautojudge install-scripts

clean:
	$(RM) tools/safeexec
	$(RM) tools/boca-submit-run-root-wrapper

install-submission-tools: tools/boca-submit-run-root-wrapper
	mkdir -p $(DESTDIR)/usr/bin $(DESTDIR)/usr/sbin $(DESTDIR)/etc/cron.d
	install tools/boca-auth-runs $(DESTDIR)/usr/sbin/
	install tools/boca-submit-run $(DESTDIR)/usr/bin/
	install tools/boca-submit-run-cron $(DESTDIR)/usr/bin/
	install tools/boca-submit-run-aux $(DESTDIR)/usr/bin/
	install tools/boca-submit-run-root $(DESTDIR)/usr/bin/
	install tools/boca-submit-log $(DESTDIR)/usr/sbin/
	install tools/cron-boca-submit $(DESTDIR)/etc/cron.d/
	install tools/cron-boca-log $(DESTDIR)/etc/cron.d/
	install tools/boca-submit-run-root-wrapper $(DESTDIR)/usr/bin/
	install tools/boca-outmanage $(DESTDIR)/usr/sbin/
	install tools/boca-checkinternet $(DESTDIR)/usr/sbin/
	install tools/boca-fixes $(DESTDIR)/usr/sbin/
	install tools/cron-boca-fixes $(DESTDIR)/etc/cron.d/
	chmod 700 $(DESTDIR)/usr/sbin/boca-fixes
	chmod 700 $(DESTDIR)/usr/sbin/boca-auth-runs
	chmod 700 $(DESTDIR)/usr/sbin/boca-outmanage
	chmod 700 $(DESTDIR)/usr/sbin/boca-submit-log
	chmod 700 $(DESTDIR)/usr/bin/boca-submit-run-*
	chmod 4555 $(DESTDIR)/usr/bin/boca-submit-run-root-wrapper
