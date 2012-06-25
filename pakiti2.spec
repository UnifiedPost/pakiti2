Version: 2.1.4
Name: pakiti
Release: 2

License: BSD
Source: http://pakiti.sourceforge.net/rpms/%{name}/%{name}-%{version}-%{release}.tar.gz
Vendor: CESNET/CERN
BuildRoot: %{_tmppath}/%{name}-%{version}-buildroot
Packager: Michal Prochazka <michalp@ics.muni.cz>
Summary: Patching status monitoring tool.
Group: Utilities/System
Url: http://pakiti.sourceforge.net

%define _prefix /

%description 
Runs rpm -qa or dpkg -l on the hosts and sends results to the central server.

Central server then process the results and checks whether the packages are
installed in the recent version. Central server also provides web GUI where
all results can be seen.

%package client-manual
Requires: openssl
Summary: Client for the Pakiti (patching status monitoring tool) using openssl or curl for transport.
Group: Utilities/System
BuildArch: noarch

%description client-manual
Runs rpm -qa or dpkg -l, depends on the linux distro. Results are sent to the
central Pakiti server using openssl s_client or curl. Manual instalation of
the client is required.

%package client
Requires: openssl
Summary: Client for the Pakiti (patching status monitoring tool) using openssl or curl for transport.
Group: Utilities/System
BuildArch: noarch

%description client
Runs rpm -qa or dpkg -l, depends on the linux distro. Results are sent to the
central Pakiti server using openssl s_client or curl.

%package server
BuildArch: noarch
Requires: webserver, php, php-mysql, php-xml
Summary: Pakiti server - Patching status system.
Group: Utilities/System

%description server
Server logic and web interface.

%prep

%setup

%build

%clean
rm -rf %{buildroot}

%install 
install -D -m755  install/pakiti2-update.cron.daily	%{buildroot}/%{_sysconfdir}/cron.daily/pakiti2-server-update
install -D -m755  install/pakiti2-init      		%{buildroot}/%{_sysconfdir}/init.d/pakiti2
install -D -m640  install/pakiti2-server.conf      	%{buildroot}/%{_sysconfdir}/pakiti2/pakiti2-server.conf
install -D -m640  client/pakiti2-client.conf		%{buildroot}/%{_sysconfdir}/pakiti2/pakiti2-client.conf
install -D -m755  client/pakiti2-client			%{buildroot}/usr/sbin/pakiti2-client
install -D -m755  client/pakiti2-client.update.cron.daily	%{buildroot}/%{_sysconfdir}/cron.daily/pakiti2-client-update
#install -m644  README          %{buildroot}/README
#install -m622  pakiti.sql   %{buildroot}/pakiti.sql

install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/scripts
install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/include
install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/install
install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/config
install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/docs
install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/www
install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/img
install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/www/feed
install -d %{buildroot}/%{_localstatedir}/lib/pakiti2/www/link
install -d %{buildroot}/%{_sysconfdir}/pakiti2

install -m644   scripts/backup_configuration.sh	%{buildroot}/%{_localstatedir}/lib/pakiti2/scripts/backup_configuration.sh
install -m644   scripts/process_oval_rh.php	%{buildroot}/%{_localstatedir}/lib/pakiti2/scripts/process_oval_rh.php
install -m644   scripts/process_oval_rh_php4.php	%{buildroot}/%{_localstatedir}/lib/pakiti2/scripts/process_oval_rh_php4.php
install -m644   scripts/recalculate_vulnerabilities.php   %{buildroot}/%{_localstatedir}/lib/pakiti2/scripts/recalculate_vulnerabilities.php
install -m644   scripts/repository_updates.php   %{buildroot}/%{_localstatedir}/lib/pakiti2/scripts/repository_updates.php

install -m644   include/functions.php   %{buildroot}/%{_localstatedir}/lib/pakiti2/include/functions.php
install -m644   include/process_rpm_pkgs_xmlreader.php %{buildroot}/%{_localstatedir}/lib/pakiti2/include/process_rpm_pkgs_xmlreader.php
install -m644   include/process_rpm_pkgs_domxml.php %{buildroot}/%{_localstatedir}/lib/pakiti2/include/process_rpm_pkgs_domxml.php
install -m644   include/gui.php   %{buildroot}/%{_localstatedir}/lib/pakiti2/include/gui.php
install -m644   include/mysql_connect.php   %{buildroot}/%{_localstatedir}/lib/pakiti2/include/mysql_connect.php

#install -m644   install/pakiti2.sql   %{buildroot}/%{_localstatedir}/lib/pakiti2/pakiti2.sql
install -m600   config/config.php   %{buildroot}/%{_localstatedir}/lib/pakiti2/config/config.php

install -m644   www/pakiti/hosts.php    %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/hosts.php
install -m644   www/pakiti/index.php    %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/index.php
install -m644   www/pakiti/cves.php    %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/cves.php
install -m644   www/pakiti/host.php    %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/host.php
install -m644   www/pakiti/packages.php    %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/packages.php
install -m644   www/pakiti/settings.php    %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/settings.php
install -m644   www/pakiti/admin.php    %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/admin.php
install -m644   www/pakiti/pakiti.css    %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/pakiti.css

install -m644   www/pakiti/img/link.gif %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/img/link.gif
install -m644   www/pakiti/img/mark.gif %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/img/mark.gif
install -m644   www/pakiti/img/ok.gif %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/img/ok.gif
install -m644   www/pakiti/img/os_installed.gif %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/img/os_installed.gif
install -m644   www/pakiti/img/os_not_installed.gif %{buildroot}/%{_localstatedir}/lib/pakiti2/www/pakiti/img/os_not_installed.gif

install -m644   www/feed/index.php     %{buildroot}/%{_localstatedir}/lib/pakiti2/www/feed/index.php

ln -s ../pakiti/cves.php www/link/cves.php
ln -s ../pakiti/packages.php www/link/packages.php
ln -s ../pakiti/pakiti.css www/link/pakiti.css

%files client-manual
%defattr(-,root,root)
#%attr(0755,root,root) %{_sysconfdir}/init.d/pakiti2
#%attr(0755,root,root) %{_sysconfdir}/cron.daily/pakiti2-client-update
#%attr(0755,root,root) %{_sbindir}/pakiti2-client
#%config(noreplace)    %{_sysconfdir}/pakiti2/pakiti2-client.conf
%doc client/pakiti2-client
%doc client/pakiti2-client.conf
%doc client/pakiti2-client.update.cron.daily
%doc client/README

%files client
%defattr(-,root,root)
%attr(0755,root,root) %{_sysconfdir}/cron.daily/pakiti2-client-update
%attr(0755,root,root) /usr/sbin/pakiti2-client
%config(noreplace)    %{_sysconfdir}/pakiti2/pakiti2-client.conf
%doc README

%files server
%defattr(-,root,root)
%attr(0664,root,root) %{_localstatedir}/lib/pakiti2/scripts/*

%attr(0640,root,apache) %{_localstatedir}/lib/pakiti2/config/config.php

%attr(0664,root,root) %{_localstatedir}/lib/pakiti2/include/*.php

%attr(0664,root,root) %{_localstatedir}/lib/pakiti2/www/pakiti/*.php
%attr(0664,root,root) %{_localstatedir}/lib/pakiti2/www/pakiti/pakiti.css
%attr(0664,root,root) %{_localstatedir}/lib/pakiti2/www/pakiti/img/*.gif

%attr(0664,root,root) %{_localstatedir}/lib/pakiti2/www/feed/index.php

%attr(0664,root,root) %{_sysconfdir}/init.d/pakiti2

%attr(0775,root,root) %{_sysconfdir}/cron.daily/pakiti2-server-update
%attr(0640,root,apache) %config(noreplace) %{_sysconfdir}/pakiti2/pakiti2-server.conf

%doc install/pakiti2.sql 
%doc install/pakiti2.apache2
#%doc docs/pakiti2_configuration_example_Debian_Ubuntu_SL_SLC.sql
#%doc docs/pakiti2_configuration_example_RH_SL_SLC.sql
%doc README

%post client-manual
#if [ "$1" = 1 ]; then
#   /sbin/chkconfig --add pakiti2
#   /sbin/chkconfig pakiti2 on
#else
#   /sbin/chkconfig pakiti2 reset
#fi
echo "See README file in /usr/share/doc/pakiti-client-%{version}/"

#%preun client
#if [ "$1" = 0 ]; then
#   /sbin/service pakiti2 stop
#   /sbin/chkconfig --del pakiti2
#fi

%post server
#echo "After configuring MySQL, Apache and /etc/pakiti/pakiti-server.conf"
/sbin/chkconfig --level 234 pakiti2 on

%preun server
/sbin/chkconfig --level 234 pakiti2 off

%changelog
%include CHANGELOG
