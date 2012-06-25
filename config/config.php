<?php
# Default Config file.
$config = '/etc/pakiti2/pakiti2-server.conf';

# Default view in host detail:
# installed - show all installed packages
# updates - show all packages which have newer version in repository
# supdates - show packages which have newer version in security repository
# cve - show packages which have some CVE
$default_view = "updates";

# Default tag which will be shown on Pakiti server web page
# all - show all tags
# 'your tag' - show only machines tagged 'your tag'
$default_tag = "all";

# Default order
# tag - order by tag name
# host - order by hostname
# time - order by the time of last report
# kernel - order by kernel
$default_order = "tag";

# Default view on domains and hosts page
# all - all domains or hosts
# vulnerable - only with vulnerable packages
# unpatched - only with unpatched packages
$default_type = "all";

# Which package names represent kernels, this name will be used to determine running kernel and the right one package represents it
$kernel_pkg_names = array ( "kernel", "kernel-devel", "kernel-smp", "kernel-smp-devel", "kernel-xenU", "kernel-xenU-devel", "kernel-largesmp", "kernel-largesmp-devel", "kernel-xen", "kernel-PAE", "kernel-hugemem" );

# If devel packages will be stored in the DB ([package name]-devel), 0 - false, 1 - true
$store_devel_packages = 0;

# If doc packages will be sotred in the DB ([package-name]-doc), 0 - false, 1 - true
$store_doc_packages = 0;

# List of ignored packages, this packages won't be stored in the database
$ignore_package_list = array ( "kernel-headers", "kernel-debug", "kernel-source" );

# Enable anonymous links
$anonymous_links = 1;

# Lifetime in seconds (default one week)
$anonymous_link_lifetime = 604800;

# Secret used for links
$secret = 'put some random string here';

# Enable/disable Outdated/missing packages view (off by default)
$ext_pages_outdated = 0;

# Enable ansynchronous mode (vulnerabilities won't be checked when host reporting, but by running scripts/recalculate_vulnerabilities.php script)
$asynchronous_mode = 0;

# Enable authorization, off by default
$enable_authz = 0;

# DNs of logins of the users, who can setup authz
# Example for SSL AuthN: $admin_dns = array ( "/DC=cz/DC=cesnet-ca/O=Masaryk University/CN=xxx1", "/DC=cz/DC=cesnet-ca/O=Masaryk University/CN=xxx2" );
# Example for Basic AuthN" $admin_dns = array ( "user1", "user2" );
$admin_dns = array ( );

# Array of the trusted proxy clients, that can send results on behalf of other pakiti clients
# Example $trusted_proxy_clients = array ( "proxy1.ics.muni.cz", "proxy2.ics.muni.cz" );
$trusted_proxy_clients = array ( );

# Repository_updates and process_oval_rh access remote sites, you can setup proxy here
# $web_proxy = "tcp://proxy.example.com:3128";
?>
