<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" >
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
    include_once("../../include/functions.php");
    include_once("../../include/mysql_connect.php");
    include_once("../../include/gui.php");

    # Variable initialization
    $stats_nb_hosts = array();
    $stats_nb_clean = array();
    $stats_nb_insecure = array();
    $stats_worse_case = array();
    $stats_avg_sec = array();
    $stats_nb_unpatched = array();
    $stats_nb_dead = array();
    $admin_list = array();

    $version = array() ;
    $currentadmins = array();
    $bg_color_alt = 0;

    $o = (isset($_GET["o"])) ? mysql_real_escape_string($_GET["o"]) : $default_order;
    $a = (isset($_GET["a"])) ? mysql_real_escape_string($_GET["a"]) : $default_tag;
    $t = (isset($_GET["t"])) ? mysql_real_escape_string($_GET["t"]) : $default_type;
    $d = (isset($_GET["d"])) ? mysql_real_escape_string($_GET["d"]) : "";
    $act = (isset($_GET["act"])) ? $_GET["act"] : "noop";
    $tld = (isset($_GET["tld"])) ? mysql_real_escape_string($_GET["tld"]) : "";

    if ($a == 'all' && $o == 'tag' && empty($d)) {
           $displaystyle="display: none;";
        } else {
           $displaystyle="";
        } 

    $tableheader = '<tr>
	<td width="80px"><h5><font color="red">Security</font></h5></td>
	<td width="60px"><h5>Other</h5></td>
	<td width="60px"><h5>CVEs</h5></td>
	<td><h5>Hostname</h5></td>
	<td width="450px"><h5>OS</h5></td>
	<td width="350px"><h5>Current kernel</h5></td>
	<td width="150px"><h5>Last report</h5></td>
	<td width="50px"><h5>Ops</h5></td>
	</tr>';

    # Set some colors for kernel versions.
    $color = array ("#ff0000", "#ffff00", "#00ff00", "#00ffff",
        "#0000cc", "#ff00ff", "#ff9900", "#99cc00",
        "#00cc99", "#0066ff", "#9933ff", "#cc0099",
        "#990000", "#009900", "#aa0099", "#bbff00",
	"#ffdd44", "#cc9900", "#aaeeee", "#bbaaaa", 
	"#0022cc", "#ff0033", "#ff4400", "#99cccc");

    # Actions
    switch ($act) {
	case "del":
		# Delete the host
		if (isset($_GET["host_id"])) {
			$hid = mysql_real_escape_string($_GET["host_id"]);
			$sql = "SELECT dmn_id FROM host WHERE id='$hid'";
			if (!$res = mysql_query($sql)) {
                                $err = mysql_error($link);
                        }
			$dm_id = mysql_fetch_row($res);
			if ($enable_authz) {
                                if (check_authz($dm_id) != 1) {
                                        break;
                                }
                        }
			$sql = "UPDATE domain SET numhosts=numhosts-1 WHERE id='" .$dm_id[0] ."'";
			if (!mysql_query($sql)) {
                                $err = mysql_error($link);
                        }
			$sql = "DELETE FROM host WHERE id='$hid'";
			if (!mysql_query($sql)) {
				$err = mysql_error($link);
			}
			$sql = "DELETE FROM installed_pkgs WHERE host_id='$hid'";
			if (!mysql_query($sql)) {
				$err = mysql_error($link);
			}
		}
		break;
	case "noop":
		break;
    }

?>


<html>
<head>
	<title>Pakiti Results for <?php echo $titlestring ?></title>
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
	      
	             function showhideall() {
			var alldmns=alladmins.split(",");

	                for (k=0; k<alldmns.length; k++) {
	                       var elem = document.getElementsByName(alldmns[k]);
	                       try {
	                               for (var i in elem) {
	                                       if (expandCollapse == 0) {
	                                               elem[i].style.display='';
	                                       } else {
	                                               elem[i].style.display='none';
	                                       }
	                               }
	                       } catch (e) {} 
			}
 		        if (expandCollapse == 0) {
				expandCollapse = 1;
				document.getElementById('expandCollapse').innerHTML = "Collapse all -";
			} else {
				expandCollapse = 0;
				document.getElementById('expandCollapse').innerHTML = "Expand all +";
			}
		      }
	              </script> 
</head>
<body onLoad="document.getElementById('loading').style.display='none';">

<!-- Loading element is shown while page is loading -->
<div id="loading" style="position: absolute; width: 250px; height: 40px; left: 45%; top: 50%; font-weight: bold; font-size: 20pt; text-decoration: blink;">Loading ...</div>

<? print_header(); ?>

<?php 
	if ($d != "") {
		if ($enable_authz) {
	                if (check_authz($d) != 1) {
        	                exit;
                        }
                }

		$sql = "SELECT domain FROM domain WHERE id='$d'";
	        if (!$res = mysql_query($sql)) {
	                print "Error: " . mysql_error($link);
	                exit;
	        }
	        $row = mysql_fetch_row($res);

		print "<h3>Results for domain <b>$row[0]</b></h3>";
	}
?>
</b></h3>


<!-- Page action bar -->
<form action="./hosts.php" method="get" name="qform">
	<input type="hidden" name="t" id="t" value="<? echo $t; ?>">
	<input type="hidden" name="o" id="o" value="<? echo $o; ?>">
	<input type="hidden" name="d" id="d" value="<? echo $d; ?>">
	<table width="100%">
		<tr>
			<td width="33%" align="left">
			<table>
				<tr>
					<td>Show:</td>
				 	<td width="110px" style="background: #FF0000;" class="bu">
						<span onClick="document.getElementById('t').value='vulnerable'; qform.submit();" <? if ($t == "vulnerable") print "style=\"font-weight: bold;\""; ?>>vulnerable</span>
					</td>
				 	<td width="110px" style="background: #FFA000;" class="bu">
						<span onClick="document.getElementById('t').value='unpatched'; qform.submit();" <? if ($t == "unpatched") print "style=\"font-weight: bold;\""; ?>>unpatched</span>
					</td>
				 	<td width="110px" style="background: #CCFF66;" class="bu">
						<span onClick="document.getElementById('t').value='all'; qform.submit();" <? if ($t == "all") print "style=\"font-weight: bold;\""; ?>>all</span>
					</td>
				 	<td width="130px" style="background: #EEEEEE;" class="bu">
						<span onClick="document.getElementById('t').value='notreporting'; qform.submit();" <? if ($t == "notreporting") print "style=\"font-weight: bold;\""; ?>>not reporting</span>
					</td>
				</tr>
			</table>
			<td width="33%" align="center">Order by:
				<span class="bu" onClick="document.getElementById('o').value='tag'; qform.submit();" <? if ($o == "tag") print "style=\"font-weight: bold;\""; ?>>tag</span>
				| <span class="bu" onClick="document.getElementById('o').value='host'; qform.submit();" <? if ($o == "host") print "style=\"font-weight: bold;\""; ?>>host</span>
				| <span class="bu" onClick="document.getElementById('o').value='time'; qform.submit();" <? if ($o == "time") print "style=\"font-weight: bold;\""; ?>>time</span>
				| <span class="bu" onClick="document.getElementById('o').value='kernel'; qform.submit();" <? if ($o == "kernel") print "style=\"font-weight: bold;\""; ?>>kernel</span>
				| <span class="bu" onClick="document.getElementById('o').value='os'; qform.submit();" <? if ($o == "os") print "style=\"font-weight: bold;\""; ?>>os</span>
			</td>
			<td width=33%" align="right">Select tag:
				<select name="a" onchange="qform.submit();">
				<option a="all" <? if ($a == "all") print " selected"; ?>>all</option>
<?php
	# Print all admins
	$sql = "SELECT DISTINCT admin FROM host";
	if (!$res = mysql_query($sql)) {
		print "Error: " . mysql_error($link);
                exit;
	}
	while ($row = mysql_fetch_row($res)) {
		print "<option a=\"$row[0]\"";
		if ($a == $row[0]) print " selected";
		print ">$row[0]</option>";
	}
?>
				</select>
			</td>
		</tr>
	</table>
</form>


<!-- Display Output -->
<?php if ($a == $default_tag && $o == "tag") { print "<span style=\"cursor: pointer;\" onclick=\"showhideall();\" id=expandCollapse>Expand all +</span>"; } ?>
<table width="100%" border="0" class="tg" cellspacing="0" cellpadding="0">
<?php

	# If hosts are not shown by tag, print header
	if ($o != "tag") print $tableheader;
     
	$sql = "SELECT 
			os.os, host.host, host.kernel, 
			UNIX_TIMESTAMP(time), TO_DAYS(NOW())-TO_DAYS(host.time), 
			host.admin, host.conn, host.id, 
			host.report_host, host.report_ip, host.dmn_id, substr(domain.domain,-2) as tld
	 	FROM host, os, domain 
		WHERE os.id=host.os_id AND host.dmn_id=domain.id";

	# Select hosts from one domain only
	if ($d != "") $sql .= " AND host.dmn_id='$d'";
	# Select only hosts from concrete tlds
	if ($tld != "")  {
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
	# Show only hosts which send report before 3 days
	if ($t == "notreporting") $sql .= ' AND TO_DAYS(NOW())-TO_DAYS(host.time) >=3';
	# Show all hosts
	if ($a != "all") $sql .= " AND host.admin='" . mysql_escape_string($a) . "'";
	# Authz
	if ($enable_authz) {
		$dmn_ret = get_authz_domain_ids();
		if (($dmn_ret != 1 || $dmn_ret != -1) && !empty($dmn_ret)) {
			$sql .= " AND ( $dmn_ret ) ";
		}
	}
	# Order by tag
	if ($o == 'tag') $sql .= " ORDER BY host.admin, host.host";
	else if ($o == 'os') $sql .= " ORDER BY os.os, host.host";
	# Else order by selected option
	else if ($o == 'kernel') $sql .= " ORDER BY cast(SUBSTRING_INDEX(SUBSTRING_INDEX(host.kernel,'-',1),'.',1) as unsigned), 
	cast(SUBSTRING_INDEX(SUBSTRING_INDEX(host.kernel,'-',1),'.',-2) as unsigned), cast(SUBSTRING_INDEX(SUBSTRING_INDEX(host.kernel,'-',1),'.',-1)
	as unsigned), SUBSTRING_INDEX(host.kernel,'-',1), cast(SUBSTRING_INDEX(SUBSTRING_INDEX(host.kernel,'-',-1),'.',1) as unsigned),
	cast(SUBSTRING_INDEX(SUBSTRING_INDEX(host.kernel,'-',-3),'.',2) as unsigned), cast(SUBSTRING_INDEX(SUBSTRING_INDEX(host.kernel,'-',-2),'.',-1)
	as unsigned), cast(SUBSTRING_INDEX(SUBSTRING_INDEX(host.kernel,'-',-1),'.',-1) as unsigned), SUBSTRING_INDEX(host.kernel,'-',-1), host.host"; 
	else $sql .= " ORDER BY host." . mysql_escape_string($o) .", host.host" ;
	if (!$hosts = mysql_query($sql)) {
		print "Error: " . mysql_error($link);
		exit;
	}
	
	# Iterate through all returned hosts
	while ($row = mysql_fetch_row($hosts) ) {
		$host_id = $row[7];
		$kernel = $row[2];
		$os = $row[0];
		$admin = $row[5];
		$ia = 0;

		if (!in_array($admin, $admin_list)) {
			array_push($admin_list, $admin);
		}

		if (!isset($stats_nb_hosts[$admin])) $stats_nb_hosts[$admin] = 0;
		if (!isset($stats_nb_clean[$admin])) $stats_nb_clean[$admin] = 0;
		if (!isset($stats_nb_insecure[$admin])) $stats_nb_insecure[$admin] = 0;
		if (!isset($stats_avg_sec[$admin])) $stats_avg_sec[$admin] = 0;
		if (!isset($stats_worse_case[$admin])) $stats_worse_case[$admin] = 0;
		if (!isset($stats_nb_unpatched[$admin])) $stats_nb_unpatched[$admin] = 0;
		if (!isset($stats_nb_dead[$admin])) $stats_nb_dead[$admin] = 0;

		$stats_nb_hosts[$admin] += 1;

		if ($row[4] >= 3) $stats_nb_dead[$admin] += 1;

		# Select number of security packages
		$sql_act = "SELECT
				count(installed_pkgs.act_version_id)
			FROM installed_pkgs, act_version 
			WHERE act_version_id>0 AND host_id='$host_id' AND 
			      installed_pkgs.act_version_id=act_version.id AND 
			      act_version.is_sec=1";
		# Select number of critical CVEs
		$sql_crit_cve = "SELECT
				count(DISTINCT cve.cve_name) 
			FROM cve, installed_pkgs_cves, cves 
			WHERE installed_pkgs_cves.host_id='$host_id' AND 
				installed_pkgs_cves.cve_id=cve.cves_id AND 
				cves.id=cve.cves_id AND 
				cves.severity=\"Critical\"";
		# Selecte number of all other needed updates
		$sql_act_other = "SELECT
					count(installed_pkgs.act_version_id) 
				FROM installed_pkgs, act_version 
				WHERE act_version_id>0 AND host_id='$host_id' AND 
				      installed_pkgs.act_version_id=act_version.id AND
				      act_version.is_sec=0";

		# Show number of security, other and CVEs
		if (!$row_act = mysql_query($sql_act)) {
			$num_up_sec_pkgs="N/A";
		}
		if (!$row_crit_cve = mysql_query($sql_crit_cve)) {
			$num_crit_cve="N/A";
		}
		if (!$row_act_other = mysql_query($sql_act_other)) {
			$num_up_all_pkgs="N/A";
		} else {
			# Fill out stats
			$item_act = mysql_fetch_row($row_act);
			$item_crit_cve = mysql_fetch_row($row_crit_cve);
			$item_act_other = mysql_fetch_row($row_act_other);
//			$num_up_sec_pkgs=$item_act[0];
			$num_up_sec_pkgs=$item_act[0]+$item_crit_cve[0];
			$num_up_other_pkgs=$item_act_other[0];
			if ($num_up_sec_pkgs > 0) {
				$stats_avg_sec[$admin] += $num_up_sec_pkgs;
				if ($stats_worse_case[$admin] < $num_up_sec_pkgs) {
					$stats_worse_case[$admin] = $num_up_sec_pkgs;
				}
			}
		}

		# Get number of CVEs
		$sql = "SELECT 
				count(DISTINCT cve.cve_name) 
			FROM cve, installed_pkgs_cves
			WHERE installed_pkgs_cves.host_id=$host_id AND 
			      installed_pkgs_cves.cve_id=cve.cves_id";

		if (!$res = mysql_query($sql)) {
			print "Error: " . mysql_error($link);
			exit;
		}

		$num_cves = 0;
		$cve_row = mysql_fetch_row($res);
                $num_cves = $cve_row[0];

                # Update stats
                if ($num_cves > 0 || $num_up_sec_pkgs > 0) {
                        $stats_nb_insecure[$admin]++;
		}
		if ($num_up_other_pkgs > 0) {
                        $stats_nb_unpatched[$admin]++;
                }
		if ($num_cves == 0 && $num_up_sec_pkgs == 0 && $num_up_other_pkgs == 0) {
			$stats_nb_clean[$admin]++;
		}

		# Skip hosts which doesn't match selected option
		$skip = 0;
		switch ($t) {
			case "vulnerable":
				# If the host doesn't have any sec updates, skip to another one
				if ($num_up_sec_pkgs == 0 && $num_cves == 0)
					$skip = 1;
				break;
			case "unpatched":
				# If the host doesn't have any updates, skip to another one
				if ($num_up_sec_pkgs == 0 && $num_up_other_pkgs == 0 && $num_cves == 0) 
					$skip = 1;
			break;
		}

		if (!$skip) {
			# Setting up the right tag section
			if (($o == 'tag') && !in_array($admin, $currentadmins)) {
				array_push($currentadmins, $admin);
				$currentadmin = $admin;
				# Print table header
				if ($alladmins) { $alladmins="$alladmins$currentadmin,"; } else { $alladmins="$currentadmin,"; } 
            			print '<tr>
					<td colspan="8">
					<h4>Tag: <a href="hosts.php?a='.$currentadmin.'">'. $currentadmin . '</a>';
				if ($a == $default_tag) print '&nbsp;<span style="cursor: pointer;" onclick="showhide(\''. $currentadmin . '\');">+</span></h4>';
				print '	</td>
					</tr>
					
                                       <tr name="'.$currentadmin.'" style="' . $displaystyle.'">
                                               <td width="80px"><h5 style="color: #cc0000;">Security</h5></td>
                                               <td width="60px"><h5>Other</h5></td>
                                               <td width="60px"><h5>CVEs</h5></td>
                                               <td><h5>Hostname</h5></td>
                                               <td width="450px"><h5>OS</h5></td>
                                               <td width="350px"><h5>Current kernel</h5></td>
                                               <td width="150px"><h5>Last report</h5></td>
                                               <td width="50px"><h5>Ops</h5></td>
                                       </tr>' ; 
			}
			// Alternate background colors of rows
			if ($bg_color_alt == 1) {
				$bg_color = 'class="bg1"';
				$bg_color_alt = 0;
			} else {
				$bg_color = 'class="bg2"';
				$bg_color_alt = 1;
			}
			print "<tr $bg_color name=\"$currentadmin\" id=\"admin$ia\" style=\"$displaystyle\">"; 
			$ia++;

      if ($num_up_sec_pkgs > 0)
				print "<td class=\"s_pkgs\">$num_up_sec_pkgs</td>";
      else
        print "<td class=\"c_pkgs\">0</td>";
      if ($num_up_other_pkgs > 0)
        print "<td class=\"o_pkgs\">$num_up_other_pkgs</td>";
      else
        print "<td class=\"c_pkgs\">0</td>";
 	    if ($num_cves > 0)
        print "<td class=\"cves\">$num_cves</td>";
      else
        print "<td class=\"c_pkgs\">0</td>";

      print "<td><a href=\"./host.php?h=$row[1]&d=$row[10]&tag=$row[5]\">";
	
			/* Show IP if reported hostname is different from REMOTE_HOST from apache */
			if ($row[1] != $row[8])
				print "$row[1] ($row[8], IP:$row[9])";
			else print "$row[1]";
				print "</a></td>" ;
	
			/* Print OS */
			print "<td>$row[0]</td>";

			/* Print kernel */
			if (! array_key_exists($kernel, $version)) {
				if (($col = array_pop($color)) != NULL) {
					$version[$kernel] = $col;
				} else {
					$version[$kernel] = "#000000";
				}
				
	      		}
			print "<td style=\"color: $version[$kernel];\">$kernel</td>";
	
			/* Print date of last connection */
			print '<td';
			if ($row[4] >= 3 )
				print ' style="color: #cc0000;">' ;
			else print '>';
			print date("j.n.y H:i", $row[3]) . '</td>';
	 		
			/* Actions */
	                print "	<td>
					<a href=\"?host_id=$row[7]&act=del\" title=\"Delete host\" style=\"color: #cc0000;\">X</a>
				</td>";
		}
                 
	}
?>
</table>


<br><h3>Statistics</h3>
<table width="100%" class="ts" cellspacing="0" cellpadding="0">
<tr class="bg2"><td><h5>Tag</h5></td><td><h5>Hosts</h5></td>
<td><h5 style="color: #00FF00;">Clean nodes</h5></td>
<td><h5 style="color: #FF9900;">Unpatched hosts</h5></td>

<td><h5 style="color: #CC0000;">Vulnerable hosts</h5></td>
<td><h5>Average # security fixes</h5></td>
<td><h5># security fixes (worse host)</h5></td>

<td><h5 style="color: #666666;">Dead hosts</h5></td>
<td><h5>Last report</h5></td></tr>

<?php #printing stats
asort($admin_list);
foreach($admin_list as $admin) {
	print "<tr><td><b>$admin</b></td>";
	print "<td>$stats_nb_hosts[$admin]</td>";
	print "<td><font color=\"green\">$stats_nb_clean[$admin]</font></td>";
	print "<td><font color=\"orange\">$stats_nb_unpatched[$admin]</font></td>";
	print "<td><font color=\"red\">$stats_nb_insecure[$admin]</font></td>";

	if ($stats_nb_insecure[$admin])
		print "<td>".round($stats_avg_sec[$admin]/$stats_nb_insecure[$admin],0)."</td>";
	else print "<td>0</td>";

	print "<td>$stats_worse_case[$admin]</td>";
	print "<td><font color=\"#666666\">$stats_nb_dead[$admin]</font></td>";
	print "<td>".date("j F Y H:i")."</td>";
	print "</tr>";
}
print '</table>';

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
<script type="text/javascript">
	var alladmins = "<?php print $alladmins; ?>";
	var expandCollapse = 0;
</script>
</body>
</html>
