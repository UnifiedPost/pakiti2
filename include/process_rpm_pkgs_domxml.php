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

# Compare packages version based on type of packages
# deb - compare version first, it they are equal then compare releases  
# rpm - compre version and release together
# Returns 0 if $a and $b are equal
# Returns 1 if $a is greater than $b
# Returns -1 if $a is lower than $b

# If repomd.xml was provided, get the path to the primary.xml.gz
if (strcasecmp(basename($filename), 'repomd.xml') == 0) {
        if (!$repomd = domxml_open_file($filename)) {
                syslog(LOG_ERR, "XML: Cannot open XML file $filename with
DOMDOcument");
                closelog();
                exit;
        }
	
	$node_def = $repomd->get_elements_by_tagname("data");
	foreach ($node_def as $def) {
		if ($def->get_attribute('type') == "primary") {
			$location = $def->get_elements_by_tagname("location");
			$url = $location[0];
			$filename = basename($url->get_attribute("href"));
			break;
		}
	}
        $filename = dirname($filename) . "/" . $primary_file;

        if (substr($filename, -3, 3) == ".gz") {
	        $filename = ungzip($filename)
        } else if (substr($filename, -4, 4) == ".bz2") {
                $filename = unbzip($filename);
        }

}

if (!$rpms = domxml_open_file($filename)) {
	syslog(LOG_ERR, "XML: Cannot open XML file $filename with
DOMDOcument");
        closelog();
        exit;
}

        $node_def = $rpms->get_elements_by_tagname('package');
        foreach ($node_def as $def) {
                $pkg = "";
                $version = "";
                $rel = "";

                $pkg_tag = $def->get_elements_by_tagname('name');
                $pkg_item = $pkg_tag[0];
                $pkg = $pkg_item->node_value;

                if (empty($pkg)) continue;

                $arch_tag = $def->get_elements_by_tagname('arch');
                $arch_item = $arch_tag[0];
                $arch = $arch_item->node_value;

                if (empty($arch)) continue;

                $version_tag = $def->get_elements_by_tagname('version');
                $epoch_item = $version_tag[0];
                $epoch = $epoch_item->get_attribute('epoch');
                $version_item = $version_tag[0];
                $version = $version_item->get_attribute('ver');
                $rel_item = $version_tag[0];
                $rel = $rel_item->get_attribute('rel');

                if (empty($version)) continue;

                if (strcasecmp($arch, $arch_name) == 0 || strcasecmp($arch,
"noarch") == 0) {
                        if ($epoch != "") {
                                $version = $epoch . ":" . $version;
                        }
                        process_pkg("rpm", $pkg, $version, $rel, $is_sec,
$arch_id, $os_group_id, $repo_id);
                } 
        }

?>
