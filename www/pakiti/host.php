<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
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


   $mtime = microtime();
   $mtime = explode(" ", $mtime);
   $mtime = $mtime[1] + $mtime[0];
   $starttime = $mtime; 

   include_once("../../config/config.php");
   include_once("../../include/mysql_connect.php");
   include_once("../../include/functions.php");
   include_once("../../include/gui.php");

   $h = (isset($_GET["h"])) ? mysql_real_escape_string($_GET["h"]) : "";
   $d = (isset($_GET["d"])) ? mysql_real_escape_string($_GET["d"]) : "";
   $p = (isset($_GET["p"])) ? mysql_real_escape_string($_GET["p"]) : "";
   $cve = (isset($_GET["cve"])) ? mysql_real_escape_string($_GET["cve"]) : "";
   $tag = (isset($_GET["tag"])) ? mysql_real_escape_string($_GET["tag"]) : "";
   $view = (isset($_GET["view"])) ? $_GET["view"] : $default_view;
   $select_pkg = (isset($_GET["selpkg"])) ? $_GET["selpkg"] : "";
   $select_host = (isset($_GET["selhost"])) ? $_GET["selhost"] : "";
   $select_cve = (isset($_GET["selcve"])) ? $_GET["selcve"] : "";
 
   $title = "Selected ";
   $h ? $title .= "host: <b>$h</b>" : $title .= "host: all";
   $p ? $title .= " package: <b>$p</b> " : $title .= " package: all";
   $cve ? $title .= " CVE: <b>$cve</b>": $title .= " CVE: all";
?>
<html>
<head>
	<title>Pakiti Results for <?php echo $h ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="pakiti.css" media="all" type="text/css" />
</head>
<body onLoad="document.getElementById('loading').style.display='none';">

	<div id="loading" style="position: absolute; width: 250px; height: 40px; left: 45%; top: 50%; font-weight: bold; font-size: 20pt; text-decoration: blink;">Loading ...</div>

<?php print  print_header();

   if ($d != "") {
   	if ($enable_authz) {
		if (check_authz($d) != 1) {
			exit;
                }
	}
   }
?>

<!-- Start a table for the drop down boxes. -->
	<form action="" method="get" name="gform">
		<input type="hidden" name="selpkg" id="selpkg">
		<input type="hidden" name="selhost" id="selhost">
		<input type="hidden" name="selcve" id="selcve">

		<table width="100%">
		<tr align="center">
			<td>
<?php
	/* Show all hosts with selected tag if it was specified */
	if ($select_host) {
		print 'Hostname:';
		print '	<select name="h" onchange="gform.submit();">';
		print '	<option value="">All';
		if ($tag != "") {
			$sql = "SELECT host FROM host, domain WHERE admin='$tag' AND host.dmn_id=domain.id ";
			# Authz
        		if ($enable_authz) {
		                $dmn_ret = get_authz_domain_ids();
                		if (($dmn_ret != 1 || $dmn_ret != -1) && !empty($dmn_ret)) {
		                        $sql .= " AND ( $dmn_ret ) ";
                		}
        		}
			$sql .= "ORDER BY host" ;
			$hosts = mysql_query($sql);
		} else {
			$sql = "SELECT host FROM host, domain WHERE host.dmn_id=domain.id";
			if ($enable_authz) {
                                $dmn_ret = get_authz_domain_ids();
                                if (($dmn_ret != 1 || $dmn_ret != -1) && !empty($dmn_ret)) {
                                        $sql .= " AND ( $dmn_ret ) ";
                                }
                        }
                        $sql .= "ORDER BY host" ;
                        $hosts = mysql_query($sql);
		}

		while($row = mysql_fetch_row($hosts)) {
			print '<option' ;
			if ($h ==  $row[0]) print " selected";
			print ' value="'.$row[0].'">'.$row[0] ;
   		}
		print "</select>";
        } else {
		print "<span onClick=\"document.getElementById('selhost').value=1; gform.submit();\" class=\"bu bu2\">";
		print "Click to select host";
		print "</span><input type=\"hidden\" name=\"h\" value=\"$h\">";
        }
?>
			</td>
			<td>
<?php
	/* Show packages if the button "Click to select concrete package" was clicked */
	if ($select_pkg) {
		print "Package:";
		print '	<select name="p" onchange="gform.submit();">';
		print '	<option value="">All';
		$pkgs = mysql_query("SELECT name FROM pkgs ORDER BY name") ;
		while($row = mysql_fetch_row($pkgs)) {
      			print '<option' ;
      			if ($p ==  $row[0]) print " selected";
			print ' value="'.$row[0].'">'.$row[0] ;
   		}
		print "</select>";
	} else {
		print "<span onClick=\"document.getElementById('selpkg').value=1; gform.submit();\" class=\"bu bu2\">";
		print "Click to select package";
		print "</span><input type=\"hidden\" name=\"p\" value=\"$p\">";
	}
?>
			</td>
			<td>
<?php
	/* Show selected CVE */
	if ($select_cve) {
		print "CVE:";
		print '	<select name="cve" onchange="gform.submit();">';
		print '	<option value=""';
		if ($cve != "") print " selected";
		print '>All';
		$cves = mysql_query("SELECT DISTINCT cve_name FROM cve ORDER BY cve_name DESC") ;
		while($row = mysql_fetch_row($cves)) {
      			print '<option' ;
      			if ($cve ==  $row[0]) print " selected";
			print ' value="'.$row[0].'">'.$row[0] ;
   		}
		print "</select>";
	} else {
		print "<span onClick=\"document.getElementById('selcve').value=1; gform.submit();\" class=\"bu bu2\">";
		print "Click to select CVE";
		print "</span><input type=\"hidden\" name=\"cve\" value=\"$cve\">";
	}
?>
		</td>
		<td>Tag:
			<select name="tag" onchange="gform.submit();">
			<option value="">All
<?php
	$pkgs = mysql_query("SELECT DISTINCT admin FROM host ORDER BY admin") ;
	while ($row = mysql_fetch_row($pkgs)) {
		print '<option' ;
		if ($tag ==  $row[0])
			print " selected"; 
		print ' value="'.$row[0].'">'.$row[0] ;
		print "</option>";
   	}
?>
			</select>
		<td>View:
			<select name="view" onchange="gform.submit();">
				<option value="installed"<?php if ($view == "installed") print " selected"; ?>>Installed packages
				<option value="supdates"<?php if ($view == "supdates") print " selected"; ?>>Security updates
				<option value="updates"<?php if ($view == "updates") print " selected"; ?>>All needed Updates
				<option value="cve"<?php if ($view == "cve") print " selected"; ?>>CVEs
			</select>
		</td>
	</tr>
	</table>
</form>

<h3><?php echo $title ?></h3>

<table width="100%">
<!-- Start to Display the Main Results -->
<!-- Print table header -->
<tr style="background: #eeeeee; font-style: italic;">
	<td width="15%">
		Host/Package name
	</td>
	<td width="15%">
		Installed version
	</td>
	<td width="25%">
		Required version (<span style="color: red">Security repository</span>, <span style="color: green">Main repository</span>)
	</td>
	<td width="45%">
		CVEs (
		<span style="color: red">Critical</span>, 
		<span style="color: orange">Important</span>, 
		<span style="color: blue">Moderate</span>, 
		<span style="color: green">Low</span>) <span onclick="var x=getElementsByName('cves'); for (var i in x) { if (x[1].style.display == 'none') x[i].style.display=''; else x[i].style.display='none'}" style="cursor: pointer; font-style: italic;">Show/Hide CVEs</span>
	</td>
</tr>
<tr><td colspan="4" style="padding-top: 10px;"></td></tr>
<?php

$bg_color = 0;
$bg_color_alt = 0;

# get info about host
$sql = "SELECT DISTINCT
		host.id, os.os, host.host, 
		UNIX_TIMESTAMP(host.time), TO_DAYS(NOW())-TO_DAYS(host.time), 
		host.arch_id, host.os_id, domain.domain, 
		host.report_host, host.report_ip, host.kernel, host.type, arch.arch
	FROM host, os, domain, arch 
	WHERE host.os_id=os.id AND host.dmn_id=domain.id AND host.arch_id=arch.id ";

if ($h)
	$sql .= "AND host='$h' ";
if ($d)
	$sql .= "AND host.dmn_id=$d ";
if ($tag) 
	$sql .= "AND admin='$tag' ";
# Authz
if ($enable_authz) {
        $dmn_ret = get_authz_domain_ids();
        if (($dmn_ret != 1 || $dmn_ret != -1) && !empty($dmn_ret)) {
	        $sql .= " AND ( $dmn_ret ) ";
        }
}

$sql .= " ORDER by os.os, host.host";

if (!$res = mysql_query($sql)) {
	print "Error: ".mysql_error($link);
	exit;
}

if (mysql_num_rows($res)) {

	/* Cycle through all hosts returned from previous query */
	while ($row = mysql_fetch_row($res)) {
		$host_id = $row[0];
		$host_os = $row[1];
		$hostname = $row[2];
		$hostdate = $row[3];
		$host_days = $row[4];
		$arch_id = $row[5];
		$os_id = $row[6];
		$domain = $row[7];
		$host_report_host = $row[8];
		$host_report_ip = $row[9];
		$kernel = $row[10];
		$os_type = $row[11];
		$arch = $row[12];

		/* Select packages */
		$sql = "SELECT DISTINCT
				pkgs.name, installed_pkgs.version, pkgs.id,
				installed_pkgs.rel, act_version.act_version,
				act_version.is_sec, act_version.act_rel
			FROM pkgs, ";

		switch ($view) {
			case "cve": 
				# if user selects concrete CVE show only this
				if ($cve) $sql .= "cve, ";
				$sql .= "	installed_pkgs_cves, installed_pkgs LEFT JOIN act_version ON installed_pkgs.act_version_id=act_version.id 
				 	 WHERE installed_pkgs.host_id='$host_id' AND 
					       installed_pkgs.pkg_id=pkgs.id AND 
					       installed_pkgs.id=installed_pkgs_cves.installed_pkg_id";
				break;
			case "supdates":
				if ($cve) $sql .= " cve, installed_pkgs_cves,";
				$sql .= " 	installed_pkgs LEFT JOIN act_version ON installed_pkgs.act_version_id=act_version.id
					WHERE installed_pkgs.host_id='$host_id'AND 
					      installed_pkgs.pkg_id=pkgs.id AND 
					      act_version.is_sec=1";
				break;
			case "updates":
				if ($cve) $sql .= " cve, installed_pkgs_cves,";
				$sql .= " 	installed_pkgs LEFT JOIN act_version ON installed_pkgs.act_version_id=act_version.id
					WHERE installed_pkgs.host_id='$host_id' AND 
					      installed_pkgs.pkg_id=pkgs.id AND
					      installed_pkgs.act_version_id > 0";
				break;
			default: 
				if ($cve) $sql .= " cve, installed_pkgs_cves,";
				$sql .= " 	installed_pkgs LEFT JOIN act_version ON installed_pkgs.act_version_id=act_version.id
                                  	WHERE installed_pkgs.host_id='$host_id' AND 
					      installed_pkgs.pkg_id=pkgs.id";
		}
		if ($p) $sql .= " AND pkgs.name='".mysql_escape_string($p)."'"; 
		if ($cve) $sql .= " AND installed_pkgs_cves.cve_id=cve.cves_id AND cve.cve_name='".mysql_escape_string($cve)."'"; 
		$sql .= " ORDER BY act_version.is_sec desc, pkgs.name asc, installed_pkgs.version asc";

		if (!$display = mysql_query($sql)) {
			print "Error: " . mysql_error($link);
			exit;
		};

		print "<tr>
			<td colspan=4>
			<table width=\"100%\" class=\"hostheader\">
			<tr>
				<td width=\"520px\"><a href=\"host.php?h=$hostname\"><b>$hostname</b>  ($host_report_host, $host_report_ip)</a></td>
				<td width=\"250px\"><i>Domain:</i> $domain</td>
				<td width=\"480px\"><i>Os:</i> $host_os ($arch)</td>
				<td><i>Kernel:</i> $kernel</td>
			</tr>
			</table>
			</td>
		       </tr>\n";
	
		if (mysql_num_rows($display) > 0) {
   
			/* Cycle throught returned packages */
			while( $row2 = mysql_fetch_row($display)) {
				$pkg_id    = $row2[2];
				$pkg_name = $row2[0];
				$pkg_ver  = $row2[1];
				$pkg_rel  = $row2[3];
				$pkg_act_ver = $row2[4];
				$pkg_act_rel = $row2[6];
				$pkg_is_sec  = $row2[5];
				$pkg_cves    = array();
				$pkg_cves_severity = array();
					
				# Select CVEs
				$sql = "SELECT 
						cves.id, cves.version, cves.rel, cves.severity 
					FROM cves, cves_os 
					WHERE cves.pkg_id=$pkg_id AND cves.cves_os_id=cves_os.id AND cves_os.os_id=$os_id
					ORDER BY severity";

				if ($cves_res = mysql_query($sql)) {
					while ($cves_row = mysql_fetch_row($cves_res)) {
						$cmp_ret = vercmp($os_type, $cves_row[1], $cves_row[2], $pkg_ver, $pkg_rel);

						if ($cmp_ret > 0) {
							$sql = "SELECT cve_name from cve WHERE cves_id=$cves_row[0] ORDER BY cve_name";
							if ($cve_res = mysql_query($sql)) {
								while ($cve_row = mysql_fetch_row($cve_res)) {
									array_push($pkg_cves,$cve_row[0]);
									array_push($pkg_cves_severity, $cves_row[3]);
								}
							} else {
								print "Error: (cve) " . mysql_error($link);
								exit;
							}
						}
					}
				} else {
					print "Error: (cves) " . mysql_error($link);
					exit;
				}
					/* If CVE view is selected skip packages with no CVE */
					if ($view == "cve" && sizeof($pkg_cves) == 0) continue;

					/* Print out packages */
	
		   			if ($bg_color_alt == 1) {
					   	$bg_color = 'class="bg1"';
						$bg_color_alt = 0;
					} else {
					   	$bg_color = 'class="bg2"';
						$bg_color_alt = 1;
					}
					print "<tr $bg_color>";

				        print "<td><a href=\"packages.php?pkg=$pkg_name\">&nbsp;&nbsp;$pkg_name</a></td>\n";
				        print "<td>$pkg_ver";
					if ($pkg_rel)
						print "/$pkg_rel";
					print"</td>\n";
					print "<td><span style=\"color:";
					if (($pkg_ver != $pkg_act_ver) || (($pkg_rel != "") && ($pkg_rel != $pkg_act_rel))) {
						if ($pkg_is_sec == 1) { 
							print "red";
							$ver_col = "red";
						}  else {
							print "green";
							$ver_col = "green";
						}
					}
					print '">';

					print $pkg_act_ver;
					if ($pkg_act_rel != "") print "/".$pkg_act_rel;

					print"</span></td>\n";
					print "<td><div name=\"cves\">";
					foreach ($pkg_cves as $key => $cve1) {
						print "<a href=\"http://cve.mitre.org/cgi-bin/cvename.cgi?name=$cve1\">";
						switch ($pkg_cves_severity[$key]) {
								case "Important":
									print "<span style=\"color:orange\">$cve1</span>";
									break;
								case "Moderate":
									print "<span style=\"color:blue\">$cve1</span>";
									break;
								case "Low":
									print "<span style=\"color:green\">$cve1</span>";
									break;
								case "Critical":
									print "<span style=\"color:red\">$cve1</span>";
									break;
								default:
									print $cve1;
						}
						print "</a> ";
					}
					print "</div></td>\n</tr>\n";
				    } 
				}
	   		}
		}
   	print "</table>";
	print "<p>\n" ;
?>
<p align="center">
<?php
    $mtime = microtime();
    $mtime = explode(" ", $mtime);
    $mtime = $mtime[1] + $mtime[0];
    $endtime = $mtime;
    $totaltime = ($endtime - $starttime);
    echo "<br><small>Executed in ".round($totaltime, 2)." seconds</small></font></p>";
?>
</body></html>

