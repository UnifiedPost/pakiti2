#!/usr/bin/php
<?php
# Copyright (c) 2008-2009, Grid PP, CERN and CESNET. All rights reserved.
# 
# Redistribution and use in source and binary forms, with or
# without modification, are permitted provided that the following
# conditions are met:
# 
#   o Redistributions of source code must retain the above
#     copyright notice, this list of conditions and the following
#     disclaimer.
#   o Redistributions in binary form must reproduce the above
#     copyright notice, this list of conditions and the following
#     disclaimer in the documentation and/or other materials
#     provided with the distribution.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
# CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
# INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
# MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
# BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
# EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
# TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
# DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
# ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
# OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
# OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE. 

include("../config/config.php");
include("../include/functions.php");
include_once("../include/mysql_connect.php");

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;

$verbose = 0;
if (isset($argv[1]) && $argv[1] == "-v") $verbose = 1;

###########################################
# Store the information in DB
$sql = "SELECT 1 FROM settings WHERE name='recalculate_update_timestamp'";
if (!$row = mysql_query($sql)) {
        die("DB: Unable to get repositories update timestamp: ".mysql_error($link));
}
if (mysql_num_rows($row) > 0) {
  $sql = "UPDATE settings SET value=CURRENT_TIMESTAMP WHERE name='recalculate_update_timestamp'";
  if (!mysql_query($sql)) {
        die("DB: Unable to set set repositories update timestamp: ".mysql_error($link));
  }
} else {
  $sql = "INSERT INTO settings (name, value) VALUES ('recalculate_update_timestamp',CURRENT_TIMESTAMP)";
  if (!mysql_query($sql)) {
	  die("DB: Unable to set set repositories update timestamp: ".mysql_error($link));
  }
}

$sql = "LOCK TABLES pkgs READ, installed_pkgs WRITE, installed_pkgs_cves WRITE, act_version READ, cves READ, cves_os READ, host READ, oses_group READ, repositories READ, settings READ" ;
if (!mysql_query($sql)) {
	die("DB: Unable to lock tables: ".mysql_error($link));
}

# Check if there were some changes in the repositories
$sql = "SELECT DISTINCT host.id, oses_group.os_group_id, host.arch_id, repositories.type, host.host 
	FROM host, oses_group, repositories, settings 
	WHERE host.os_id=oses_group.os_id AND oses_group.os_group_id=repositories.os_group_id AND
	settings.name='repositories_update_timestamp' AND repositories.timestamp > settings.value";

if (!$res = mysql_query($sql)) {
        die("DB: Unable to get info about host: ".mysql_error($link));
}
$num_hosts = mysql_num_rows($res);
$i = 1;
while ($host = mysql_fetch_row($res)) {
	$host_id = $host[0];
	$os_group_id = $host[1];
	$arch_id = $host[2];
	$os_type = $host[3];
	
	if ($verbose) print "Processing ($i/$num_hosts) $host[4] .";
	$i++;

	# Clean CVEs for this host
	$sql = "DELETE FROM installed_pkgs_cves WHERE host_id=$host_id" ;
        if (!mysql_query($sql)) {
                die("DB: Unable to delete installed_pkgs_cves for host:".mysql_error($link));
        }

	$sql = "SELECT id, pkg_id, version, rel FROM installed_pkgs WHERE host_id=$host_id";
	if (!$res2 = mysql_query($sql)) {
	       	die("DB: Unable to pkg info: ".mysql_error($link));
	}
	while ($pkgs = mysql_fetch_row($res2)) {
		$act_version_id = NULL;
		$installed_pkg_id = $pkgs[0];
		$pkg_id = $pkgs[1];
		$pkg_version = $pkgs[2];
		$pkg_rel = $pkgs[3];
	
		$sql_act = "SELECT act_version, id, is_sec, act_rel FROM act_version WHERE pkg_id='" . $pkg_id . "' AND os_group_id='$os_group_id' AND arch_id='$arch_id'";
		$result_act = mysql_query($sql_act);
                if (!$result_act) {
                        die("DB: Unable to fetch act_version:".mysql_error($link));
                }
		if (mysql_num_rows($result_act) > 0) {
			$act = mysql_fetch_row($result_act);
	
			$cmp_ret = vercmp($os_type, $pkg_version, $pkg_rel,  $act[0], $act[3]);

			// Check if there is different version/release of installed package and actual version of package
			if ($cmp_ret < 0) {
				$act_version_id = $act[1];
				$act_version_is_sec = $act[2];
			} else {
				$act_version_id = 0;
				$act_version_is_sec = 0;
			}
		} else {
			$act_version_id = 0;
			$act_version_is_sec = 0;
		}
	
		$sql = "UPDATE installed_pkgs SET act_version_id=$act_version_id WHERE id=$installed_pkg_id";
		if (!mysql_query($sql)) { 
			$mysql_e = mysql_error();
			die("DB: Unable to update installed_pkgs: $mysql_e ... $sql"); 
		}
	
		# Compare against CVEs
		# Get pkg version from CVEs
		$sql = "SELECT cves.id, cves.version, cves.rel
			FROM cves, cves_os, host
			WHERE cves.pkg_id=$pkg_id AND host.id=$host_id AND cves.cves_os_id=cves_os.id
				AND cves_os.os_id=host.os_id 
				AND strcmp(concat(cves.version,cves.rel), '" . $pkg_version . $pkg_rel . "') != 0";
	
		if (!$result = mysql_query($sql)) {
	               $mysql_e = mysql_error();
	               die("DB: Unable to get cves version and release: $mysql_e ... $sql");
	        }
		while ($item = mysql_fetch_row($result)) {
			$cmp_ret = vercmp($os_type, $pkg_version, $pkg_rel, $item[1], $item[2]);
			if ($cmp_ret < 0) {
				$sql = "INSERT IGNORE INTO installed_pkgs_cves (host_id, installed_pkg_id, cve_id) VALUES ($host_id, $installed_pkg_id,  $item[0])";
				if (!mysql_query($sql)) {
					$mysql_e = mysql_error();
			                die("DB: Unable to add entry into installed_pkgs_cves: $mysql_e ... $sql");
				}
			}
		
		}
	}
	if ($verbose) print ". done\n";
}

$sql = "UNLOCK TABLES" ;
if (!mysql_query($sql)) {
        die("DB: Unable to unlock tables: ".mysql_error($link));
}

mysql_close($link);

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
if ($verbose) print "Information recorded in time: $totaltime";
?>
