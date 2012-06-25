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
	$xml = new XMLReader();
	libxml_set_streams_context(get_context());
	if ($xml->open($filename) === FALSE) {
	    syslog(LOG_ERR, "XML: Cannot open XML file $filename with XMLReader");
	    closelog();
	    exit;
	}

	while ($xml->read()) {
		if ($xml->name == "data" && $xml->nodeType == XMLReader::ELEMENT && $xml->getAttribute("type") == "primary") {
			while ($xml->read()) {
				if ($xml->name == "location" && $xml->nodeType == XMLReader::ELEMENT) {
					$primary_file = basename($xml->getAttribute("href"));
					break;
				}
			}
			break;
		}
 	}

	$filename = dirname($filename) . "/" . $primary_file;

	if (substr($filename, -3, 3) == ".gz") {
               	$filename = ungzip($filename);
        } else if (substr($filename, -4, 4) == ".bz2") {
       	        $filename = unbzip($filename);
        } 
}

$xml = new XMLReader();
libxml_set_streams_context(get_context());
if ($xml->open($filename) === FALSE) {
    syslog(LOG_ERR, "XML: Cannot open XML file $filename with XMLReader");
    closelog();
    exit;
}

while($xml->read()) {
          if ($xml->name == "name" && $xml->nodeType == XMLReader::ELEMENT) {
                  $xml->read();
                  $pkg = $xml->value;
                  $search = true;
                  while ($search) {
                          $xml->read();
        if ($xml->name == "arch" && $xml->nodeType == XMLReader::ELEMENT) {
                                        $xml->read();
                                        $arch = $xml->value;
                                }
                          if ($xml->name == "version" && $xml->nodeType ==
XMLReader::ELEMENT) {
                                  $epoch = $xml->getAttribute("epoch");
                                  $version = $xml->getAttribute("ver");
                                  $rel = $xml->getAttribute("rel");
                                  $search = false;
                    }
                          if ($xml->name == "name" && $xml->nodeType ==
XMLReader::ELEMENT) {
                syslog(LOG_ERR, "XML: Missing attribute version after
attribute name $pkg");
          closelog();
          exit;
                          }
                  }
      if (strcasecmp($arch, $arch_name) == 0 || strcasecmp($arch, "noarch") == 0) {
        if ($epoch != "") {
          $version = $epoch . ":" . $version;
        }
        process_pkg("rpm", $pkg, $version, $rel, $is_sec, $arch_id, $os_group_id, $repo_id);
      }
      $pkg = "";
      $version = "";
      $rel = "";
    }
}

?>
