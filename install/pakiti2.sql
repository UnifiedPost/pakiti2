DROP TABLE IF EXISTS `act_version`;
CREATE TABLE `act_version` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `arch_id` int(10) unsigned NOT NULL,
  `arch` char(50) default '',
  `os_group_id` int(10) unsigned NOT NULL,
  `pkg_id` int(10) unsigned NOT NULL,
  `repo_id` int(11) default NULL,
  `act_version` char(100) NOT NULL,
  `act_rel` char(100) default NULL,
  `is_sec` int(1) unsigned default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `arch_id` (`arch_id`,`os_group_id`,`pkg_id`),
  KEY `pkg_id` (`pkg_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `arch`;
CREATE TABLE `arch` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `arch` char(15) default NULL,
  PRIMARY KEY  (`id`),
  KEY `arch_index` (`arch`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `cve`;
CREATE TABLE `cve` (
  `cve_name` char(15) default NULL,
  `cves_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `cve_name` (`cve_name`,`cves_id`),
  KEY `cve_name_2` (`cve_name`),
  KEY `cves_id` (`cves_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `cves`;
CREATE TABLE `cves` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `def_id` varchar(255) NOT NULL,
  `cves_os_id` char(10) NOT NULL,
  `arch_id` int(10) unsigned NOT NULL,
  `pkg_id` int(10) unsigned NOT NULL,
  `version` char(100) NOT NULL,
  `rel` char(100) default '',
  `operator` char(2) NOT NULL,
  `severity` char(50) NOT NULL,
  `title` varchar(256) default '',
  `reference` varchar(256) default '',
  `cve_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `def_id` (`def_id`,`cves_os_id`,`pkg_id`,`arch_id`),
  KEY `arch_id` (`arch_id`),
  KEY `cve_id` (`cve_id`),
  KEY `i_pkg_id` (`pkg_id`),
  KEY `cves_os_id` (`cves_os_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `cves_os`;
CREATE TABLE `cves_os` (
  `id` char(10) NOT NULL,
  `os_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`,`os_id`),
  KEY `os_id` (`os_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `domain`;
CREATE TABLE `domain` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `domain` varchar(150) NOT NULL,
  `numhosts` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `domain_index` (`domain`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `host`;
CREATE TABLE `host` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `host` char(100) NOT NULL,
  `os_id` int(10) unsigned NOT NULL,
  `kernel` char(70) NOT NULL default '',
  `arch_id` int(10) unsigned NOT NULL,
  `admin` varchar(255) default 'nobody',
  `conn` varchar(20) NOT NULL default '',
  `version` char(5) NOT NULL default '',
  `report_host` varchar(255) NOT NULL default '',
  `report_ip` char(45) NOT NULL default '',
  `dmn_id` int(10) unsigned NOT NULL,
  `site_id` int(10) unsigned default NULL,
  `type` char(5) default NULL,
  `report_md5` char(32) default NULL,
  `pkgs_change_timestamp` char(32) default NULL,
  PRIMARY KEY  (`id`),
  KEY `arch_id` (`arch_id`),
  KEY `os_id` (`os_id`),
  KEY `dmn_id` (`dmn_id`),
  KEY `host` (`host`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `installed_pkgs`;
CREATE TABLE `installed_pkgs` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `host_id` int(10) unsigned NOT NULL,
  `pkg_id` int(10) unsigned NOT NULL,
  `version` char(100) default '',
  `rel` char(100) default '',
  `arch` char(50) default '',
  `act_version_id` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_entry` (`host_id`,`pkg_id`,`version`,`rel`,`arch`),
  UNIQUE KEY `uniq_entry_no_arch` (`host_id`,`pkg_id`,`version`,`rel`),
  KEY `pkg_id` (`pkg_id`),
  KEY `host_id` (`host_id`,`pkg_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `installed_pkgs_cves`;
CREATE TABLE `installed_pkgs_cves` (
  `host_id` int(10) unsigned NOT NULL,
  `installed_pkg_id` bigint(20) unsigned NOT NULL,
  `cve_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `unique_entry` (`host_id`,`installed_pkg_id`,`cve_id`),
  KEY `host_id` (`host_id`),
  KEY `installed_pkg_id` (`installed_pkg_id`),
  KEY `cve_id` (`cve_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `os`;
CREATE TABLE `os` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `os` varchar(100) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `os` (`os`),
  KEY `os_index` (`os`(50))
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `os_group`;
CREATE TABLE `os_group` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `oses_group`;
CREATE TABLE `oses_group` (
  `os_group_id` int(10) unsigned NOT NULL,
  `os_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`os_group_id`,`os_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `pkgs`;
CREATE TABLE `pkgs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` char(150) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `repositories`;
CREATE TABLE `repositories` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(1024) NOT NULL,
  `url` varchar(1024) NOT NULL,
  `is_sec` int(1) NOT NULL,
  `type` char(5) NOT NULL,
  `enabled` int(1) default NULL,
  `last_access_ok` int(1) default NULL,
  `arch_id` int(10) unsigned default NULL,
  `os_group_id` int(10) unsigned default NULL,
  `file_checksum` char(33) default NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(1024) NOT NULL,
  `value` varchar(4096) NOT NULL,
  `value2` varchar(4096) default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`(1000))
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `site`;
CREATE TABLE `site` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL,
  `numhosts` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `site_index` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `user_domain`;
CREATE TABLE `user_domain` (
  `user_id` int(10) unsigned NOT NULL,
  `domain_id` int(10) unsigned NOT NULL,
  `domain` varchar(150) default NULL,
  PRIMARY KEY  (`user_id`,`domain_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user` char(128) NOT NULL,
  `dn` char(255) default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dn` (`dn`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
LOCK TABLES `settings` WRITE;
INSERT INTO `settings` VALUES (1,'RedHat CVEs URL','https://www.redhat.com/security/data/oval/com.redhat.rhsa-2003.xml','1'),(2,'RedHat CVEs URL','https://www.redhat.com/security/data/oval/com.redhat.rhsa-2004.xml','1'),(3,'RedHat CVEs URL','https://www.redhat.com/security/data/oval/com.redhat.rhsa-2005.xml','1'),(4,'RedHat CVEs URL','https://www.redhat.com/security/data/oval/com.redhat.rhsa-2006.xml','1'),(5,'RedHat CVEs URL','https://www.redhat.com/security/data/oval/com.redhat.rhsa-2007.xml','1'),(6,'RedHat CVEs URL','https://www.redhat.com/security/data/oval/com.redhat.rhsa-2008.xml','1'),(7,'RedHat CVEs URL','https://www.redhat.com/security/data/oval/com.redhat.rhsa-2009.xml','1'),(8,'RedHat CVEs URL','https://www.redhat.com/security/data/oval/com.redhat.rhsa-2010.xml','1');
UNLOCK TABLES;
