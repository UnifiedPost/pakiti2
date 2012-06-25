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

    include_once("../../config/config.php");
    include_once("../../include/functions.php");

    # Check whether user is in admin_dns
    if (check_admin_authz() == -1) {
	echo "You are not authorized.";
	exit;
    } else {
    	header("Cache-Control: no-cache, must-revalidate"); 
        print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" >';
    }


    include_once("../../include/mysql_connect.php");
    include_once("../../include/gui.php");

	
    $error = "";
    # Actions
    $act = (isset($_GET["act"])) ? $_GET["act"] : "noop";
    
    switch ($act) {
	case "add":
		$username = mysql_real_escape_string($_GET["username"]);
		$dn = mysql_real_escape_string($_GET["dn"]);
		$domain_id = mysql_real_escape_string($_GET["domain_id"]);

		if (empty($dn) || empty($domain_id)) {
			$error = "User's dn and domain can't be empty!";
			break;
		}
		$sql = "SELECT id FROM users WHERE dn='$dn'";
		if (!$res = mysql_query($sql)) {
                        $error = "Error during storing the user into the DB.";
                        break;
                }
		if ($row = mysql_fetch_row($res)) {
			$user_id = $row[0];
		} else {
			if (empty($username)) {
			  $error = "Please fill the user name.";
			  break;
			}
			$sql = "INSERT INTO users (user, dn) VALUES ('$username','" . trim($dn) . "')";
			if (!mysql_query($sql)) {
				$error = "Error during storing the user into the DB.";
				break;
			}
			$user_id = mysql_insert_id();		
		}
		
		$sql = "INSERT INTO user_domain (user_id, domain_id) VALUES ($user_id, $domain_id)";
		if (!mysql_query($sql)) {
	                $error = "Error during storing the user into the DB.";
                        break;
        	}
		break;
	case "del":
		$user_id = mysql_real_escape_string($_GET["user_id"]);
		$domain_id = mysql_real_escape_string($_GET["domain_id"]);

		$sql = "DELETE FROM user_domain WHERE user_id=$user_id AND domain_id=$domain_id";
		if (!mysql_query($sql)) {
                        $error = "Error during deleting the user from the DB.";
                        break;
                }

		$sql = "SELECT 1 FROM user_domain WHERE user_id=$user_id";
		if (!$res = mysql_query($sql)) {
                        $error = "Error during getting user's authz info from the DB.";
                        break;
                }
		if (mysql_num_rows($res) == 0) {
			$sql = "DELETE FROM users WHERE id=$user_id";
	                if (!mysql_query($sql)) {
        	                $error = "Error during deleting the user from the DB.";
                	        break;
                	}
		}
		break;
	case "noop":
		break;	
    }
?>


<html>
<head>
	<title>Pakiti Admin Configuration</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="pakiti.css" media="all" type="text/css" />
</script>	
</head>
<body onLoad="document.getElementById('loading').style.display='none';">

<div id="loading" style="position: absolute; width: 250px; height: 40px; left: 45%; top: 50%; font-weight: bold; font-size: 20pt; text-decoration: blink;">Loading ...</div>

<?php print_header(); ?>

<?php if ($error != "") print "<font color=\"red\" size=\"5\">$error</font>"; ?>

<h4>Logged as <?php print $_SERVER['SSL_CLIENT_S_DN']; ?></h4>

<h2>Access Control List</h2>
<form action="" method="get">
<input type="hidden" name="act" value="add">
User name: <input type="text" name="username">
User's DN/Login: <input type="text" name="dn">
Domain: <select name="domain_id">
<?php
	$sql = "SELECT id, domain FROM domain ORDER BY substr(domain,length(domain)-locate('.',reverse(lower(domain)))+2), domain";
	if (!$res = mysql_query($sql)) {
		$error = "Error during getting domains";
		exit;
	}
	while ($row = mysql_fetch_row($res)) {
		print "<option value=\"$row[0]\">$row[1]</option>";
	}
?>
<input type="submit" value="Add">
</form>
<table width="100%">
	<tr>
		<th width="200">User</th>
		<th>User's DN/Login</th>
		<th width="200">Domains</th>
		<th width="80">Action</th>
	</tr>
<?php
	$sql = "SELECT user, dn, domain.domain, users.id, domain.id FROM users, domain, user_domain WHERE 
		users.id=user_domain.user_id AND domain.id=user_domain.domain_id 
		ORDER BY dn, domain.domain";
	if (!$res = mysql_query($sql)) {
        	print "Error: ".mysql_error($link);
        	exit;
	}
	$currentdn = "";
	while ($row = mysql_fetch_row($res)) {

		// Alternate background colors of rows
                if ($bg_color_alt == 1) {
	                $bg_color = 'class="bg1"';
                        $bg_color_alt = 0;
                 } else {
                        $bg_color = 'class="bg2"';
                        $bg_color_alt = 1;
                 }

		if ($currentdn != $row[1]) {
			$currentdn = $row[1];
			print "<tr $bg_color><td>$row[0]</td><td><b>$row[1]</b></td><td>$row[2]</td><td align=\"center\"><a href=\"admin.php?act=del&user_id=$row[3]&domain_id=$row[4]\" title=\"Delete\">[Delete]</a></td></tr>";
		} else {
			print "<tr $bg_color><td>&nbsp;</td><td>&nbsp;</td><td>$row[2]</td><td align=\"center\"><a href=\"admin.php?act=del&user_id=$row[3]&domain_id=$row[4]\" title=\"Delete\">[Delete]</a></td></tr>";
		}
	}
?>
</table>
<hr>
<h4>Administrators</h4>
<?php
	foreach ($admin_dns as $dn) {
		print "$dn</br>";
	}
?>
</body>
</html>

