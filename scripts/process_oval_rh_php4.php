#!/usr/bin/php
<?php
# Process oval data for RedHat/Sceintific Linux
# Notice: In OVAL for RH there is no distinguish between i686 and x86_64

include_once("../config/config.php");
include_once("../include/mysql_connect.php");

$verbose = 0;
if (isset($argv[1]) && $argv[1] == "-v") $verbose = 1;

$sql = "SELECT value, value2 FROM settings WHERE name='RedHat CVEs URL' ORDER BY value ASC";
if (!$res = mysql_query($sql)) {
                die("DB: Select settings: ".mysql_error($link));
}
while ($row = mysql_fetch_row($res)) {
	// If value2 == 1 => the source is enabled
	if ($row[1] == 1) $oval_rh_file = $row[0];
	else continue;

	# Remove white characters from begin and end
	$oval_rh_file = trim($oval_rh_file);

	# mjk download the oval file
	$filepath = tempnam("/tmp", "pakiti");;
	$out = fopen($filepath, 'wb');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_FILE, $out);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_URL, $oval_rh_file);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 0);
	curl_exec($ch);
	curl_close($ch);
	fclose($out);

	if (!$oval = domxml_open_file($filepath)) {
		syslog(LOG_ERR, "XML: Cannot open XML document $filepath with DOMDOcument");
		closelog();
	        # Remove temporary file
		unlink($filepath);
		exit;
	}
	
	$cves = array();
	$cves_desc = array();
	
	#Get OVAL generator
	$el_l_generator = $oval->get_elements_by_tagname('generator');
	
	foreach ($el_l_generator as $el_generator) {
		$el_l_product = $el_generator->get_elements_by_tagname('product_name');
		if ($verbose) print $el_l_product[0]->node_name . ": " . $el_l_product[0]->node_value .  " from $row[0]\n";
	}
	
	# Go through the OVAL file and get definitions
	
	$el_l_defs = $oval->get_elements_by_tagname("definition");

	$sql = "LOCK TABLES pkgs WRITE, cves_os WRITE, cves WRITE, cve WRITE";
	if (!mysql_query($sql)) {
	        # Remove temporary file
		unlink($filepath);
		die("DB: Unable to lock tables: ".mysql_error($link));
	}
	foreach($el_l_defs as $el_def) {
		$def_id = $el_def->get_attribute('id');
		$def_version = $el_def->get_attribute('version');
		$def_class = $el_def->get_attribute('class');
	

		$title = $el_def->get_elements_by_tagname('title');
		$title = $title[0];
		$title = rtrim($title->node_value);

		$platform = $el_def->get_elements_by_tagname('platform');
		$platform = $platform[0];
		$platform = $platform->node_value;

		$ref_url = $el_def->get_elements_by_tagname('reference');
		$ref_url = $ref_url[0];
		$ref_url = $ref_url->get_attribute('ref_url');


		$el_severity = $el_def->get_elements_by_tagname('severity');
		$el_severity = $el_severity[0];



		if (!empty($el_severity)) {
			$severity = $el_severity->node_value;
		} else $severity = "n/a";

		$cves = array();
		$el_l_cves = $el_def->get_elements_by_tagname('cve');
		foreach ($el_l_cves as $el_cve) {
			if ($el_cve->node_value != "")
				array_push($cves, $el_cve->node_value);
		}
	
		$el_l_comments = $el_def->get_elements_by_tagname('criterion');
		$redhat_release = "";
		$package = "";
		foreach ($el_l_comments as $el_comment) {
			$el_tmp = $el_comment->get_attribute('comment');
			if (strpos($el_tmp, "is installed")) {
				# Get the release
				preg_match("/^Red Hat Enterprise Linux.* (\d+) is installed$/", $el_tmp, $redhat_release);
			}
		
			# Get the info about versions
			if (strpos($el_tmp, "is earlier than")) {
				list ($package, ,,, $version_raw) = explode(" ", $el_tmp);
				list ($version, $release) = explode("-", $version_raw);
			}
		
			if ($package != "" && $version != "") {
				# Now we have all necessary information: Package name (package), package
				# version (version), package release (release), RH release (redhat_release) and CVEs (cves)
	
				# Store it into DB
			
				# Find the package id
				$sql = "SELECT id FROM pkgs WHERE name='" . $package ."'";
				if (!$row = mysql_query($sql)) {
				        # Remove temporary file
					unlink($filepath);
					die("DB: Unable to get pkg id:".mysql_error($link));
				}
				if (mysql_num_rows($row) >= 1) {
					$item = mysql_fetch_row($row);
					$pkg_id = $item[0];
				} else {
					# PKG is not present, so insert it
          $sql = "INSERT INTO pkgs (name) VALUES ('" .$package. "')";
          if (!mysql_query($sql)) {
	        # Remove temporary file
		unlink($filepath);
	        die("DB: Unable to add new pkg:".mysql_error($link));
          }
          $pkg_id = mysql_insert_id();
				}

				$sql = "INSERT INTO cves (def_id, cves_os_id, arch_id, pkg_id, version, rel, operator, severity, title, reference) VALUES ('$def_id','rh_" . $redhat_release[1] . "',0 ,'$pkg_id','$version','$release','<','$severity','$title','$ref_url') ON DUPLICATE KEY UPDATE id=last_insert_id(id), version='$version', rel='$release', severity='$severity', title='$title', reference='$ref_url'";
				if (!mysql_query($sql)) {
				        # Remove temporary file
					unlink($filepath);
					die("DB: Cannot insert cves data: ".mysql_error($link));
				}
				$ins_id = mysql_insert_id();
				# Insert detailed info about each CVE
				foreach ($cves as $cve) {
					$sql2 = "INSERT IGNORE INTO cve (cves_id, cve_name) VALUES ($ins_id, '$cve')";
					if (!mysql_query($sql2)) {
					    # Remove temporary file
					    unlink($filepath);
					    die("DB: Cannot insert cves data: ".mysql_error($link));
					}
				}
				$package = "";
				$version = "";
				$release = "";
			}
	
	  	}
	
	}
	$sql = "UNLOCK TABLES" ;
	if (!mysql_query($sql)) {
	        # Remove temporary file
		unlink($filepath);
		die("DB: Unable to unlock tables: ".mysql_error($link));
	}
        # Remove temporary file
	unlink($filepath);
}
?>
