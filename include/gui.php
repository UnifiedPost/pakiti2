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

function print_header() {
global $ext_pages_outdated;
global $enable_authz;
global $anonymous_links;

// Checking authorization, this will allow all admins and all users
if ($enable_authz && check_authz_all() != 1) {
       print "<h2>You are not authorized to access this page!</h2>";
       exit;
} ;

print '<table class="headertable">
	<tr>
        <td><span class="maintitle">Pakiti</span><span class="submaintitle"> - Patching Status System</span></td>';
	
        if (($anonymous_links == 1) && (get_logged_user() == "")) {  
	        print '<td></td>';
	} else {
		print '<td align="right" valign="top"><span class="navpanel"><b>Navigation</b>: Select hosts by <a href="cves.php">CVE</a> | <a href="packages.php">package</a>, Display all <a href="hosts.php">hosts</a> | <a href="index.php">domains</a>';
		if ($ext_pages_outdated == 1) {
			print ' | <a href="outdated_pkgs.php">outdated pkgs</a>';
		}

		if (($enable_authz == 0) || ($enable_authz == 1 && check_admin_authz() == 1)) {
			print '&nbsp;&nbsp;&nbsp&nbsp;<a href="settings.php" style="color: #00CC33;">Settings</a>';
		} 
		if ($enable_authz == 1 && check_admin_authz() == 1) {
			print '&nbsp;|&nbsp;<a href="admin.php" style="color: #00CC33;">ACL</a>';
		} 
		print "</span>";
	}
	print '</td>
	</tr>
	</table>';
}
?>
