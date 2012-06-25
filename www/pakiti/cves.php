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

$authorized = 1;
if (($anonymous_links == 1) && (get_logged_user() == "")) {
       if (!check_link($_GET['auth'])) {
       	       print "You do not have permissions to access this site or the lifetime of the link has expired.";
               exit;
  	}
       $authorized = 0;
}

$cve = (isset($_GET["cve"])) ? mysql_real_escape_string($_GET["cve"]) : "";
$domain = (isset($_GET["domain"])) ? mysql_real_escape_string($_GET["domain"]) : "";
$select_cve = (isset($_GET["selcve"])) ? mysql_real_escape_string($_GET["selcve"]) : "";
$select_domain = (isset($_GET["seldomain"])) ? mysql_real_escape_string($_GET["seldomain"]) : "";
$tld = (isset($_GET["tld"])) ? mysql_real_escape_string($_GET["tld"]) : "";
$arch = (isset($_GET["arch"])) ? mysql_real_escape_string($_GET["arch"]) : "";
 
$title = "Pakiti Package Results for ";
if ($cve != "") $title .= "$cve";
if ($domain != "") $title .= " for $domain";

?>
<html>
<head>
	<title><?php print $title; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="pakiti.css" media="all" type="text/css" />

	<script type="text/javascript">
	function showhide(dmn_id) {
		var elem = document.getElementsByName(dmn_id);
                for (var i in elem) {
        	        if (elem[i].style.display == 'none') {
                	        elem[i].style.display='';
                        } else {
                                elem[i].style.display='none';
                        }
                }
	}
	</script>
</head>
<body onLoad="document.getElementById('loading').style.display='none';">

	<div id="loading" style="position: absolute; width: 250px; height: 40px; left: 45%; top: 50%; font-weight: bold; font-size: 20pt; text-decoration: blink;">Loading ...</div>

<?php print_header(); 

if ($domain != "") {
        if ($enable_authz) {
		$sql = "SELECT id FROM domain WHERE domain='$domain'";
		$res = mysql_query($sql);
		$row = mysql_fetch_row($res);
                if (check_authz($row[0]) != 1) {
                        exit;
                }
        }
}

?>

<!-- Start a table for the drop down boxes. -->
	<form action="" method="get" name="gform">
		<input type="hidden" name="selcve" id="selcve">
		<input type="hidden" name="seldomain" id="seldomain">

		<table width="100%">
		<tr align="center">
			<td width="50%">
<?php
	/* Show selected CVE */
	print "CVE:";
	if (!$authorized) {
		print "&nbsp; $cve";
        } else {

		print '	<select name="cve" onchange="gform.submit();">';
		print '	<option value=""';
		if ($cve != "") print " selected";
		print '>No CVE selected';
		$cves = mysql_query("SELECT DISTINCT cve_name FROM cve ORDER BY cve_name DESC") ;
		while($row = mysql_fetch_row($cves)) {
			print '<option' ;
			if ( $cve ==  $row[0] )
				print " selected";
			print ' value="'.$row[0].'">'.$row[0] ;
		}
		print "</select>";
	}
	print "</td>"
?>
	<td>Domain:
<?php
       if (!$authorized) {
               print "&nbsp; $domain";
       } else {                

               print '<select name="domain" onchange="gform.submit();">
               <option value="">All';
		$sql = "SELECT domain FROM domain ";
		if ($enable_authz) {
                	$dmn_ret = get_authz_domain_ids();
                        if (($dmn_ret != 1 || $dmn_ret != -1) && !empty($dmn_ret)) {
                        	$sql .= " WHERE  $dmn_ret  ";
                        }
                }	
		$sql .= "ORDER BY domain" ;

		$domains = mysql_query($sql);
		while ($row = mysql_fetch_row($domains)) {
			print '<option' ;
			if ($domain ==  $row[0])
				print " selected"; 
			print ' value="'.$row[0].'">'.$row[0]."\n" ;
   		}

		print '</select>';
	}
?>
		</tr>
		</table>
	</form>

<table width="100%">
       <tr>
               <td align="right">
<?php
       if ($authorized && !empty($cve) && !empty($domain)) {
               print "<span class=\"bu\" onClick=\"this.innerHTML='" . get_link() . "'\">Click to get anonymous link to this page (lifetime of the link is " . $anonymous_link_lifetime/60 . " minutes)</span>";
       }
?>
               </td>
       </tr>
</table>

<h3>Selected CVE: <b><?php print $cve; ?></b></h3>

<table width="100%">
<tr style="background: #eeeeee; font-style: italic;" align="top">
	<td width="30%">
		Domain/Host
	</td>
	<td>
		Packages
	</td>
	<td>
		Last report
	</td>
</tr>

<!-- Start to Display the Main Results -->
<?php

# If no cve is selected, do not show anything

$act_domain = "";
$domains = array();

if ($cve != "") {
	$sql = "SELECT DISTINCT domain, host, UNIX_TIMESTAMP(time), domain.id, substr(domain.domain,-2) as tld FROM host, installed_pkgs_cves, cve, domain, arch WHERE cve.cve_name='$cve' AND installed_pkgs_cves.cve_id=cve.cves_id AND installed_pkgs_cves.host_id=host.id AND host.dmn_id=domain.id";
	if ($domain) $sql .= " AND domain.domain='$domain'";
	if ($tld != "") {
                if (strpos($tld, ',') !== false) {
                        $sql .= " AND (";
                        $tlds = explode(',', $tld);
                        $tlds_size = count($tlds);
                        for ($i = 0; $i < $tlds_size; $i++) {
                                $sql .= "substr(domain.domain, length(domain.domain)-locate('.',reverse(lower(domain.domain)))+2) = '" . $tlds[$i] . "'";
                                if ($i < $tlds_size-1) $sql .= " OR ";
                        }
                        $sql .= ")";
                } else {
                        if ($tld != "") $sql .= " AND substr(domain.domain, length(domain.domain)-locate('.',reverse(lower(domain.domain)))+2) = '$tld'";
                }
        }	
	if ($arch) $sql .= " AND arch.arch='$arch' AND host.arch_id=arch.id ";
	# Authz
        if ($enable_authz) {
	        $dmn_ret = get_authz_domain_ids();
                if (($dmn_ret != 1 || $dmn_ret != -1) && !empty($dmn_ret)) {
        	        $sql .= " AND ( $dmn_ret ) ";
                }
        }       

	$sql .= " ORDER BY tld, domain, host";

	$res = mysql_query($sql) ;
	while($row = mysql_fetch_row($res) ) {

		$domains[$row[3]] = $row[0];

		if (!isset($hosts[$row[3]])) {
			$hosts[$row[3]] = array();
			$dates[$row[3]] = array();
		}
		array_push($hosts[$row[3]], $row[1]);
		array_push($dates[$row[3]], date("j F Y H:i", $row[2]));
	}

	$bg_color = 'class="bg1"';
	foreach ($domains as $dmn_id => $dmn_name) {

		$bg_color == 'class="bg1"' ? $bg_color = 'class="bg2"': $bg_color = 'class="bg1"';

		print "<tr $bg_color>";
		print "<td colspan=\"3\">";
		print "<b><a href=\"hosts.php?d=$dmn_id\">$dmn_name</a></b>";
		print " <span style=\"cursor: pointer;\" onclick=\"showhide($dmn_id);\">+</span></td>";
		print "</tr>\n";
		
		foreach ($hosts[$dmn_id] as $key => $val) {
			$sql = "SELECT pkgs.name, installed_pkgs.version, installed_pkgs.rel FROM pkgs, installed_pkgs_cves, installed_pkgs, cve, host
				WHERE cve.cve_name='$cve' AND installed_pkgs_cves.cve_id=cve.cves_id AND installed_pkgs_cves.host_id=host.id AND host.host='$val'
				AND installed_pkgs.id=installed_pkgs_cves.installed_pkg_id AND pkgs.id=installed_pkgs.pkg_id";
			$res2 = mysql_query($sql);
			$packages = "";
			while ($pkg = mysql_fetch_row($res2)) {
				$packages .= "$pkg[0] ($pkg[1]-$pkg[2])<br> ";
			}
			$bg2_color == 'class="bg1"' ? $bg2_color = 'class="bg2"': $bg2_color = 'class="bg1"';
			print "<tr $bg2_color name=\"$dmn_id\" style=\"display: none;\">";
		        print "<td><a href=\"host.php?h=$val\">&nbsp;&nbsp;$val</a></td>\n";
			print "<td style=\"color: red;\">$packages</td>";
			print "<td>" . $dates[$dmn_id][$key] . "</td>";
			print "</tr>\n";
		}
	}
}
?>
</table>

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

