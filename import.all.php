<?php

	echo "[Start Import]\n";

	require_once 'header.php';

	// Had it choke out on me when starting from scratch
	ini_set('memory_limit', -1);

	// Thankfully, I've never really had these break down much, never
	// had much use for the grouping.
	$arr_import['base'] = array('arches', 'licenses');
	$arr_import['packages'] = array('categories', 'packages');
	$arr_import['ebuilds'] = array('ebuilds');
	$arr_import['metadata'] = array('ebuild_metadata', 'ebuild_arch', 'ebuild_homepage', 'ebuild_license', 'package_mask', 'ebuild_mask', 'ebuild_ev', 'use_global', 'use_local', 'use_expand', 'ebuild_use');

	foreach($arr_import as $key => $arr) {
		if($$key) {
			foreach($arr as $file) {
 				include "import.$file.php";
			}
		}
	}

?>
