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
$mtime = explode(" ",$mtime);
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
    $currentadmin = "";
    $bg_color_alt = 0;

    $o = (isset($_GET["o"])) ? mysql_real_escape_string($_GET["o"]) : $default_order;
    $a = (isset($_GET["a"])) ? mysql_real_escape_string($_GET["a"]) : $default_tag;
    $t = (isset($_GET["t"])) ? mysql_real_escape_string($_GET["t"]) : $default_type;
    $d = (isset($_GET["d"])) ? mysql_real_escape_string($_GET["d"]) : "";
    $act = (isset($_GET["act"])) ? $_GET["act"] : "noop";
    $tld = (isset($_GET["tld"])) ? mysql_real_escape_string($_GET["tld"]) : "";


   # 2.0 stuff
   $p = (isset($_GET["p"])) ? $_GET["p"] : "pakiti-client";
   $h = (isset($_GET["h"])) ? $_GET["h"] : "";
   $tag = (isset($_GET["tag"])) ? $_GET["tag"] : "";
   $domain = (isset($_GET["domain"])) ? $_GET["domain"] : "";

   $view = (isset($_GET["view"])) ? $_GET["view"] : "deployment";
   $view = strip_tags($view);
   $p = strip_tags($p); $h = strip_tags($h);

    if ($t == 'all' && $tag=='all' && $o == 'tag') {
        $displaystyle="display: none";   
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

    $color_package = array ("#ff0000", "#ffff00", "#00ff00", "#00ffff",
        "#0000cc", "#ff00ff", "#ff9900", "#99cc00",
        "#00cc99", "#0066ff", "#9933ff", "#cc0099",
        "#990000", "#009900", "#aa0099", "#bbff00",
        "#ffdd44", "#cc9900", "#aaeeee", "#bbaaaa",
        "#0022cc", "#ff0033", "#ff4400", "#99cccc");
?>

<html>
<head>
        <title>Pakiti Outdated/Missing Results for <?php echo $titlestring ?></title>
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
        function showhideall(what) {
                var alldmns=what.split(",");

                for (k=0; k<alldmns.length; k++) {
                        var elem = document.getElementsByName(alldmns[k]);
                        try { 
                                for (var i in elem) {
                                        if (elem[i].style.display == 'none') {
                                                elem[i].style.display='';
                                        } else {
                                                elem[i].style.display='none';
                                        }
                                }
                        } catch (e) {}
                }
        }
        </script>

</head>
<body onLoad="document.getElementById('loading').style.display='none';">

<!-- Loading element is shown while page is loading -->
<div id="loading" style="position: absolute; width: 250px; height: 40px; left: 45%; top: 50%; font-weight: bold;
 font-size: 20pt; text-decoration: blink;">Loading ...</div>

<? print_header(); ?>

<?php

$tag_list=array();
if ($o != "tag") { # Getting the list of tags
    $alltags =  mysql_query("select distinct admin from host order by admin") ;
    while($currenttag = mysql_fetch_row($alltags) ) {
	$crttag=$currenttag[0];
        array_push($tag_list, $currenttag[0]);
    }
}

# Displays headers
function header_dad($dad,$currenttag ) {
   global $colspan; global $p;
   if ($dad == 1) {
	print "<tr><td colspan=\"$colspan\">&nbsp;</td></tr><tr ><td colspan=\"$colspan\">";
	print "<h4>Tag: <b><a href=\"?tag=$currenttag&p=$p\">";
	print $currenttag;
	print "</a>\n";
        print " <span style=\"cursor: pointer;\" onclick=showhide(\"$currenttag\");>+</span></td>";
        print "</h4></b>\n" ;
    	print "</td></tr>";
   } 
	 return 0;
}

function header_ddo($ddo,$currentdomain ) {
   global $colspan;
   if ($ddo == 1) {
          print "<tr><td colspan=\"$colspan\">";
     print "<h2><b><i><font color=\"#990000\">Domain: ";
     print $currentdomain; 
          print "</font></i></b></h2>\n" ;
     print "</td></tr>";
         }  
         return 0;
}
   
# Set some colors for kernel versions.
$color = array   ( "#ff0000", "#000000", "#00ff00", "#00ffff",
                     "#0000cc", "#ff00ff", "#ff9900", "#99cc00",
                     "#00cc99", "#0066ff", "#9933ff", "#cc0099",
                     "#990000" );
$version = array();
$version_package = array();


?>
<form action="/outdated_pkgs.php" method="get" name="cform">
        <input type="hidden" name="t" id="t" value="<? echo $t; ?>">
        <input type="hidden" name="o" id="o" value="<? echo $o; ?>">
        <input type="hidden" name="d" id="d" value="<? echo $d; ?>">

       <table width="100%">
                <tr>
                        <td align="center">Order by:
                                <span class="bu" onClick="document.getElementById('o').value='tag'; cform.submit();" <? if ($o == "tag") print "style=\"font-weight: bold;\""; ?>>tag</span>
                                | <span class="bu" onClick="document.getElementById('o').value='host'; cform.submit();" <? if ($o == "host") print "style=\"font-weight: bold;\""; ?>>host</span>
                                | <span class="bu" onClick="document.getElementById('o').value='time'; cform.submit();" <? if ($o == "time") print "style=\"font-weight: bold;\""; ?>>time</span>
                                | <span class="bu" onClick="document.getElementById('o').value='kernel'; cform.submit();" <? if ($o == "kernel") print "style=\"font-weight: bold;\""; ?>>kernel</span>
                                | <span class="bu" onClick="document.getElementById('o').value='os'; cform.submit();" <? if ($o == "os") print "style=\"font-weight: bold;\""; ?>>os</span>
                        </td>
</form>

<td>Package:
<select name="p" onchange="cform.submit();">


<?php
$view="deployment";
        $colspan = 6;

   $pkgs = mysql_query("select name from pkgs group by name order by name") ;
   while($row = mysql_fetch_row($pkgs)) {
      print '<option' ;
      if ( $p ==  $row[0] ) { print " selected" ; }
      print ' value="'.$row[0].'">'.$row[0] ;
   }
?>
</select>
</td>

                        <td>Tag:
                                <select name="tag" onchange="cform.submit();">
                                <option a="all" <? if ($a == "all") print " selected"; ?>>all</option>
<?php
   $pkgs = mysql_query("select distinct admin from host group by admin order by admin") ;
   while($row = mysql_fetch_row($pkgs)) {
      print '<option' ;
      if ($tag ==  $row[0]) { print " selected" ; }
      print ' value="'.$row[0].'">'.$row[0] ;
      print "</option>";
   }
?>
</select>
</td>


<td>Domain:
<select name="domain" onchange="cform.submit();">
<option value="">All
<?php
   $pkgs = mysql_query("select distinct domain from domain group by domain order by domain") ;
   while($row = mysql_fetch_row($pkgs)) {
      print '<option' ;
      if ($domain ==  $row[0]) { print " selected" ; }
      print ' value="'.$row[0].'">'.$row[0] ;
      print "</option>";
   }

?>
</select>
</td>
</tr></table>


<h2>Packages installed on the hosts</h2>

<table width="100%" border="0" cellspacing="0" cellpadding="1">
<tr>
	<td width="350"><h5>Package name</h5></td>
	<td width="300"><h5>Version</h5></td>
	<td><h5>&nbsp;</h5></td>
</tr>

<?php

if ($p != 'kernel') { 
  $sql = "select DISTINCT  pkgs.name,installed_pkgs.version,installed_pkgs.rel from installed_pkgs,pkgs where pkgs.name='$p' and installed_pkgs.pkg_id=pkgs.id  ORDER BY cast(SUBSTRING_INDEX(installed_pkgs.version,':',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(installed_pkgs.version,'.',-2) as unsigned) DESC, cast(SUBSTRING_INDEX(installed_pkgs.version,'.',-1) as unsigned) DESC, installed_pkgs.version DESC, cast(SUBSTRING_INDEX(installed_pkgs.rel,'.',1) as unsigned) DESC, installed_pkgs.rel DESC " ;
} else { 
  $sql = "select DISTINCT  pkgs.name,installed_pkgs.version,installed_pkgs.rel from installed_pkgs,pkgs where (pkgs.name='kernel' or pkgs.name='kernel-smp' or pkgs.name='kernel-xen' or pkgs.name='kernel-xenU') and installed_pkgs.pkg_id=pkgs.id  ORDER BY cast(SUBSTRING_INDEX(installed_pkgs.version,':',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(installed_pkgs.version,'.',-2) as unsigned) DESC, cast(SUBSTRING_INDEX(installed_pkgs.version,'.',-1) as unsigned) DESC, installed_pkgs.version DESC, cast(SUBSTRING_INDEX(installed_pkgs.rel,'.',1) as unsigned) DESC, cast(SUBSTRING_INDEX(installed_pkgs.rel,'.',2) as unsigned) DESC, cast(SUBSTRING_INDEX(SUBSTRING_INDEX(installed_pkgs.rel,'.',2),'.',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(SUBSTRING_INDEX(installed_pkgs.rel,'.',3),'.',-1) as unsigned) DESC, installed_pkgs.rel DESC " ;
} 

$hosts = mysql_query($sql) ;
$currentos = "" ;

while($row = mysql_fetch_row($hosts) ) {

                        // Alternate background colors of rows
                        if ($bg_color_alt == 1) {
                                $bg_color = 'class="bg1"';
                                $bg_color_alt = 0;
                        } else {
                                $bg_color = 'class="bg2"';
                                $bg_color_alt = 1;
                        }


                        $package="$row[1]-$row[2]";
                        if (! array_key_exists($package, $version_package)) {
				if (($col = array_pop($color_package)) != NULL) {
                                        $version_package[$package] = $col;
                                } else {
                                        $version_pacakge[$package] = "#000000";
                                }
                        }
			$versionColor="style=\"color: $version_package[$package];\"";


        print "\n<tr $bg_color >";
        print "\n<td>$row[0]</td>";
	if (empty($row[2])) {
	        print "\n<td $versionColor>$row[1]</td>";
	} else {
	        print "\n<td $versionColor>$row[1]-$row[2]</td>";
	}
        print "\n<td>installed</td>";
        print "</tr>";

  }

$sql = "select DISTINCT  pkgs.name,act_version.act_version,act_version.act_rel,repositories.name  from act_version,pkgs,repositories where pkgs.name='$p' and act_version.pkg_id=pkgs.id and act_version.repo_id=repositories.id  ORDER BY cast(SUBSTRING_INDEX(act_version.act_version,':',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(act_version.act_version,'.',-2) as unsigned) DESC, cast(SUBSTRING_INDEX(act_version.act_version,'.',-1) as unsigned) DESC, act_version.act_version DESC, cast(SUBSTRING_INDEX(act_version.act_rel,'.',1) as unsigned) DESC, act_version.act_rel DESC  ";

$hosts = mysql_query($sql) ;
$currentos = "" ;
?>
</table>
<h2>Packages present at the repositories</h2>
<table width="100%" border="0" cellspacing="0" cellpadding="1">
<tr>
        <td width="350"><h5>Package name</h5></td>
        <td width="300"><h5>Version</h5></td>
        <td><h5>Repository</h5></td>
</tr>

<?php
while($row = mysql_fetch_row($hosts) ) {

        // Alternate background colors of rows
        if ($bg_color_alt == 1) {
 	       $bg_color = 'class="bg1"';
               $bg_color_alt = 0;
        } else {
               $bg_color = 'class="bg2"';
               $bg_color_alt = 1;
                        }

	$package="$row[1]-$row[2]";
        if (! array_key_exists($package, $version_package)) {
		if (($col = array_pop($color_package)) != NULL) {
	                $version_package[$package] = $col;
                } else {
                        $version_pacakge[$package] = "#000000";
                }
        }
        $versionColor="style=\"color: $version_package[$package];\"";

        print "\n<tr $bg_color>";
        print "\n<td>$row[0]</td>";
	if (empty($row[2])) {
	        print "\n<td $versionColor>$row[1]</td>";
	} else {
	        print "\n<td $versionColor>$row[1]-$row[2]</td>";
	}
        print "\n<td>$row[3]</td>";
        print "</tr>";

  }


?>
</table>



<?php
if ($o != 'tag' && $o != 'os') {  $ordord="HA.$o"; }
if ($o == 'tag') { $ordord1="HA.admin,os.os"; } 
if ($o == 'os') { $ordord1="os.os"; }
if ($o == 'kernel') { $ordord1="cast(SUBSTRING_INDEX(installed_pkgs.version,':',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(installed_pkgs.version,'.',-2) as unsigned) DESC, cast(SUBSTRING_INDEX(installed_pkgs.version,'.',-1) as unsigned) DESC, installed_pkgs.version DESC, cast(SUBSTRING_INDEX(installed_pkgs.rel,'.',1) as unsigned) DESC, cast(SUBSTRING_INDEX(installed_pkgs.rel,'.',2) as unsigned) DESC, cast(SUBSTRING_INDEX(SUBSTRING_INDEX(installed_pkgs.rel,'.',2),'.',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(SUBSTRING_INDEX(installed_pkgs.rel,'.',3),'.',-1) as unsigned) DESC, installed_pkgs.rel DESC,"; }

if ($p != 'kernel') { 
  $sql = "select HA.host,HA.time,os.os,pkgs.name,installed_pkgs.version,installed_pkgs.rel,HA.admin,domain.domain,HA.kernel from domain,installed_pkgs,pkgs,host as HA,os where domain.id=HA.dmn_id and HA.os_id=os.id and installed_pkgs.pkg_id=pkgs.id and pkgs.name='$p' and (installed_pkgs.version,installed_pkgs.rel) != (select installed_pkgs.version as A,installed_pkgs.rel as B from host,pkgs,installed_pkgs where    host.os_id in (select os_id from oses_group where os_group_id in (select os_group_id from oses_group where os_id=HA.os_id))    and host.id=installed_pkgs.host_id and pkgs.name='$p' and pkgs.id=installed_pkgs.pkg_id ORDER BY cast(SUBSTRING_INDEX(A,':',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(A,'.',-2) as unsigned) DESC, cast(SUBSTRING_INDEX(A,'.',-1) as unsigned) DESC, A DESC, cast(SUBSTRING_INDEX(B,'.',1) as unsigned) DESC, B DESC  LIMIT 1)  and (installed_pkgs.version,installed_pkgs.rel) != (select DISTINCT act_version.act_version as A,act_version.act_rel as B  from oses_group,act_version,pkgs,repositories where repositories.os_group_id = oses_group.os_group_id and oses_group.os_id=HA.os_id and pkgs.name='$p' and act_version.pkg_id=pkgs.id and act_version.repo_id=repositories.id  ORDER BY cast(SUBSTRING_INDEX(A,':',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(A,'.',-2) as unsigned) DESC, cast(SUBSTRING_INDEX(A,'.',-1) as unsigned) DESC, A DESC, cast(SUBSTRING_INDEX(B,'.',1) as unsigned) DESC, B DESC  LIMIT 1) and installed_pkgs.host_id=HA.id and HA.host LIKE '%$h%'  ORDER BY $ordord1 $ordord, HA.host ASC";
} else { 
  # replaced: host.os_id=HA.os_id 
  $sql = "select HA.host,HA.time,os.os,pkgs.name,installed_pkgs.version,installed_pkgs.rel,HA.admin,domain.domain,HA.kernel from domain,installed_pkgs,pkgs,host as HA,os where domain.id=HA.dmn_id and HA.os_id=os.id and installed_pkgs.pkg_id=pkgs.id and (pkgs.name='kernel' or pkgs.name='kernel-smp' or pkgs.name='kernel-xen' or pkgs.name='kernel-xenU') and (installed_pkgs.version,installed_pkgs.rel) != (select installed_pkgs.version as A,installed_pkgs.rel as B from host,pkgs,installed_pkgs where   host.os_id in (select os_id from oses_group where os_group_id in (select os_group_id from oses_group where os_id=HA.os_id))   and host.id=installed_pkgs.host_id and (pkgs.name='kernel' or pkgs.name='kernel-smp' or pkgs.name='kernel-xen' or pkgs.name='kernel-xenU') and pkgs.id=installed_pkgs.pkg_id ORDER BY cast(SUBSTRING_INDEX(A,':',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(A,'.',-2) as unsigned) DESC, cast(SUBSTRING_INDEX(A,'.',-1) as unsigned) DESC, A DESC, cast(SUBSTRING_INDEX(B,'.',1) as unsigned) DESC, cast(SUBSTRING_INDEX(SUBSTRING_INDEX(B,'.',2),'.',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(SUBSTRING_INDEX(B,'.',3),'.',-1) as unsigned) DESC, B DESC  LIMIT 1)  and (installed_pkgs.version,installed_pkgs.rel) != (select DISTINCT act_version.act_version as A,act_version.act_rel as B  from oses_group,act_version,pkgs,repositories where repositories.os_group_id = oses_group.os_group_id and oses_group.os_id=HA.os_id and (pkgs.name='kernel' or pkgs.name='kernel-smp' or pkgs.name='kernel-xen' or pkgs.name='kernel-xenU') and act_version.pkg_id=pkgs.id and act_version.repo_id=repositories.id  ORDER BY cast(SUBSTRING_INDEX(A,':',-1) as unsigned) DESC, cast(SUBSTRING_INDEX(A,'.',-2) as unsigned) DESC, cast(SUBSTRING_INDEX(A,'.',-1) as unsigned) DESC, A DESC, cast(SUBSTRING_INDEX(B,'.',1) as unsigned) DESC, cast(SUBSTRING_INDEX(SUBSTRING_INDEX(B,'.',3),'.',-1) as unsigned) DESC, B DESC  LIMIT 1) and installed_pkgs.host_id=HA.id and HA.host LIKE '%$h%'  ORDER BY $ordord1 $ordord, HA.host ASC";
}

      print "<h2>Outdated Location Details</h2>";
      print "<span class=\"bu\" onclick=showhideall(tagsOutdated);>Expand/Collapse all</span>";
      print "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">";


$hosts = mysql_query($sql) ;

$currentos = "" ;

while($row = mysql_fetch_row($hosts) ) {


# Setting up the right OS and Tag

        $clientversion =  $row[13];
        if (($o=='tag') && ($currenttag != $row[6])){
          $currenttag = $row[6] ; $crttag = $row[6] ;
          unset($currentos); $toprint = 0 ;
          $dad = 1;
               if ($M) { $M="${M}$crttag,"; } else { $M="$crttag,"; }

         }
        if (($o=='domain') && ($currentdomain != $row[7])){
          $currentdomain = $row[7];
          unset($currentos);
          $ddo = 1; $toprint = 0 ; 
        }

   if ($currentos != $row[2]) {
       $currentos = $row[2] ;
                 $dos = 1; #$toprint = 0 ;
    }



    if (($tag == "all" || $tag== $row[6]) && ($domain == "" || $domain == $row[7])) {

                $dad = header_dad($dad,$crttag);
                $ddo = header_ddo($ddo, $currentdomain);

	if ($toprint != 1) { 

		print "<tr name=\"$crttag\" style=\"$displaystyle\"><td align=left><h5><i>Node Name</i></h5></td>"; 
		print "<td align=left><h5><i>OS</i></h5></td>";
		print "<td align=left><h5><i>Kernel</i></h5></td>";
                print "<td align=left><h5><i>Last Report Time</i></h5></td>";
		print "<td align=left><h5><i>Package name</i></h5></td>";
		print "<td align=left><h5><i>Package version</i></h5></td></tr>";

		$toprint=1; 
	} 

                        // Alternate background colors of rows
                        if ($bg_color_alt == 1) {
                                $bg_color = 'class="bg1"';
                                $bg_color_alt = 0;
                        } else {
                                $bg_color = 'class="bg2"';
                                $bg_color_alt = 1;
                        }



	print "\n<tr $bg_color name=\"$crttag\" style=\"$displaystyle\">";
	print "\n<td><a href=\"./host.php?h=$row[0]&view=$view\">$row[0]</a></td>";

        print "\n<td>$row[2]</td>";

			$kernel=$row[8]; 
                        if (! array_key_exists($kernel, $version)) {
				if (($col = array_pop($color)) != NULL) {
                                        $version[$kernel] = $col;
                                } else {
                                        $version[$kernel] = "#000000";
                                }
                        }

        print "\n<td style=\"color: $version[$kernel];\">$row[8]</td>";
        if ( strtotime($row[1]) < time() - 3600*24*3 ) {
                print "\n<td><font color=\"red\">$row[1]</font></td>";
        } else {
                print "\n<td>$row[1]</td>";
        }
	print "\n<td><a href=\"./packages.php?pkg=$row[3]\">$row[3]</a></td>";


                        $package="$row[4]-$row[5]";
                        if (! array_key_exists($package, $version_package)) {
       				if (($col = array_pop($color_package)) != NULL) {
                                        $version_package[$package] = $col;
                                } else {
                                        $version_pacakge[$package] = "#000000";
                                }
                        }
                        $versionColor="style=\"color: $version_package[$package];\"";


	if (empty($row[5])) {
		print "\n<td $versionColor>$row[4]</td>";
	} else {
		print "\n<td $versionColor>$row[4]-$row[5]</td>";
	}
	print "</tr>";
    }

  }
?>
</table>


<br><br>


<?php


$ordord="" ; $ordord1="";
if ($o != 'tag' && $o != 'os') {  $ordord="host.$o"; }
if ($o == 'tag') { $ordord1="host.admin,os.os"; }
if ($o == 'os') { $ordord1="os.os"; }

if ($p != 'kernel') { 
  $sql = "select * from host,os,domain where host.dmn_id=domain.id and host.host not in (select host from installed_pkgs,pkgs,host where installed_pkgs.pkg_id=pkgs.id and pkgs.name='$p' and host.id=installed_pkgs.host_id ) and host.os_id=os.id ORDER BY $ordord1 $ordord,host.host ASC"; 
} else { 
  $sql = "select * from host,os,domain where host.dmn_id=domain.id and host.host not in (select host from installed_pkgs,pkgs,host where installed_pkgs.pkg_id=pkgs.id and (pkgs.name='kernel' or pkgs.name='kernel-smp' or pkgs.name='kernel-xen' or pkgs.name='kernel-xenU') and host.id=installed_pkgs.host_id ) and host.os_id=os.id ORDER BY $ordord1 $ordord,host.host ASC";
}

      print "<h2>Missing Location Details</h2>";
      print "<span class=\"bu\" onclick=showhideall(tagsMissing);>Expand/Collapse all</span><br><br>";
      print "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\">";


$hosts = mysql_query($sql) ;
$currentos = "" ;  $toprint=0; 
while($row = mysql_fetch_row($hosts) ) {

# Setting up the right OS and Tag

        $clientversion =  $row[5];
        if (($o=='tag') && ($currenttag != $row[6])){
        $currenttag = $row[6] ; $crttag = $row[6] ;
        unset($currentos); $toprint = 0 ;
        $dad = 1;
               if ($N) { $N="$N$crttag,"; } else { $N="$crttag,"; }

         }        
        if (($o=='domain') && ($currentdomain != $row[7])){
        $currentdomain = $row[7];
        unset($currentos);
        $ddo = 1; $toprint = 0 ;
        }

   if ($currentos != $row[14]) {       $currentos = $row[14] ;
                 $dos = 1; # $toprint = 0 ;
    }


    if (($tag == "all" || $tag== $row[6]) && ($domain == "" || $domain == $row[16])) {


                $dad = header_dad($dad,$crttag);
                $ddo = header_ddo($ddo, $currentdomain);

        if ($toprint != 1) {

                print "<tr name=\"$crttag\" style=\"$displaystyle\"><td align=left><h5><i>Node Name</h5></i></td>";
                print "<td align=left><h5><i>OS</h5></i></td>";
                print "<td align=left><h5><i>Kernel</h5></i></td>";
                print "<td align=left><h5><i>Last Report Time</h5></i></td>";

                $toprint=1;
        }

       
                        // Alternate background colors of rows
                        if ($bg_color_alt == 1) {
                                $bg_color = 'class="bg1"';
                                $bg_color_alt = 0;
                        } else {
                                $bg_color = 'class="bg2"';
                                $bg_color_alt = 1;
                        }


        print "\n<tr $bg_color name=\"$crttag\" style=\"$displaystyle\">";
        print "\n<td><a href=\"./host.php?h=$row[2]&view=$view\">$row[2]</a></td>";

        print "\n<td>$row[16]</td>";

                        $kernel=$row[4];
                        if (! array_key_exists($kernel, $version)) {
				if (($col = array_pop($color)) != NULL) {
                                        $version[$kernel] = $col;
                                } else {
                                        $version[$kernel] = "#000000";
                                }
                        }


        print "\n<td style=\"color: $version[$kernel];\">$row[4]</td>";

        if ( strtotime($row[1]) < time() - 3600*24*3 ) {
                print "\n<td><font color=\"red\">$row[1]</font></td>";
        } else {
                print "\n<td>$row[1]</td>";
        }

        print "</tr>";

     } 

  }
?>
</table>



<?

$mtime = microtime();
   $mtime = explode(" ",$mtime);
   $mtime = $mtime[1] + $mtime[0];
   $endtime = $mtime;
   $totaltime = ($endtime - $starttime);
   echo "<br><small>Executed in ".round($totaltime,2)." seconds</small></font></p>"; 

?>

<script type="text/javascript">
	var tagsMissing = "<?php echo $N;?>";
	var tagsOutdated = "<?php echo $M;?>";
</script>
</body></html>

