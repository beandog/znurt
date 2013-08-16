<?php

	require_once 'header.php';
	
	// Had it choke out on me when starting from scratch
	ini_set('memory_limit', -1);

	// Always verbose since we are generally running manually from CLI
	$verbose = true;
	
	// Do some performance analysis
	$hits = array();
	
	// Run with -cron arg to go quietly into the night.
	// No code in here to do emerge --sync.  Runs separately.
	$cron = false;
	if(in_array("-cron", $argv)) {
		$verbose = false;
		$cron = true;
	}
	
	// Log the import times of the scripts
	if($cron) {
		$sql = "INSERT INTO znurt (action) VALUES ('start_import');";
		$db->query($sql);
	}
	
	$sql = "INSERT INTO import_status (status) VALUES ('start');";
	$db->query($sql);
	$import_id = $db->lastInsertID();
	
	// FIXME these could be accidentally overwritten in one of the includes
	$base = true;
  	$packages = true;
   	$ebuilds = true;
 	$metadata = true;
 	$use = true;
 	$final = true;
	
	// Thankfully, I've never really had these break down much, never
	// had much use for the grouping.
	$arr_import['base'] = array('arches', 'licenses');
	$arr_import['packages'] = array('categories', 'packages', 'bugzilla');
	if($development)
		$arr_import['packages'] = array('categories', 'packages');
	$arr_import['ebuilds'] = array('ebuilds');
	$arr_import['metadata'] = array('ebuild_metadata', 'ebuild_arch', 'ebuild_homepage', 'ebuild_license', 'package_mask', 'ebuild_mask', 'ebuild_ev', 'use_global', 'use_local', 'use_expand', 'ebuild_use', 'ebuild_depend');
	$arr_import['final'] = array('final');

	// FIXME updating the website with our import status would be nice.
	foreach($arr_import as $key => $arr) {
		if($$key) {
			foreach($arr as $file) {
  				if($verbose)
					echo "[import] $file\n";
					
				if(is_dir("/tmp/znurt") && is_writable("/tmp/znurt")) {
					file_put_contents("/tmp/znurt/status", "$file\n");
				}
				
				$sql = "UPDATE import_status SET status = ".$db->quote($file).", udate = NOW() WHERE id = ".$db->quote($import_id).";";
				$db->query($sql);
				
 				include "import.$file.php";
			}
		}
	}
	
	function memory_usage() {
        $mem_usage = memory_get_usage(true);
       
        if ($mem_usage < 1024)
            $str = $mem_usage."b";
        else
            $str = round($mem_usage/1024,2)."kb";
//         else
//             $str = round($mem_usage/1048576,2)." megabytes";
           
        return $str;
    } 
	

?>
