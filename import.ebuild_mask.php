<?php

	echo "[Ebuild Masks]\n";

	// There's no real need for a status column here,
	// since they won't show up unless the ebuild is completed importing
	// anyway.  Going to leave it in for now, though.

	require_once 'header.php';
	require_once 'import.functions.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';

	// Reset old entries that were new
	$sql = "DELETE FROM ebuild_mask WHERE status = 1;";
	$db->query($sql);

	// Nothing to do if there are no new ebuilds
	$import = false;
	$sql = "SELECT COUNT(1) FROM ebuild WHERE status = 1;";
	$count = $db->getOne($sql);
	if($count)
		$import = true;

	// If there are *new* entries in package_mask, then we are going
	// to check against those.  Otherwise, check against the old ones.
	$sql = "SELECT COUNT(1) FROM package_mask WHERE status = 1;";
	$count = $db->getOne($sql);
	if($count)
		$pm_status = 1;
	else
		$pm_status = 0;

	// If, for some reason, there are no ebuilds masked, check
	// all the ebuilds, not just the new ones.
	$sql = "SELECT COUNT(1) FROM ebuild_mask;";
	$count = $db->getOne($sql);
	if(!$count)
		$e_status = "0,1";
	else
		$e_status = "1";

	if($import) {

		// Insert entries where the entire package is masked
		$sql = "INSERT INTO ebuild_mask SELECT pm.id, e.id, 1 FROM ebuild e INNER JOIN package p ON e.package = p.id INNER JOIN package_mask pm ON pm.package = e.package WHERE e.status IN($e_status) AND pm.status = $pm_status AND pm.pvr = '' AND lt = FALSE AND gt = FALSE AND eq = FALSE AND ar = FALSE AND av = FALSE;";
		$db->query($sql);

		// Insert entries where it's an exact package and version
		$sql = "INSERT INTO ebuild_mask SELECT pm.id, e.id, 1 FROM ebuild e INNER JOIN package p ON e.package = p.id INNER JOIN package_mask pm ON pm.package = e.package WHERE e.status IN($e_status) AND pm.status = $pm_status AND pm.pvr = e.pvr AND lt = FALSE AND gt = FALSE AND eq = TRUE AND ar = FALSE AND av = FALSE;";
		$db->query($sql);

		// Insert entries where atom is like:
		// =media-video/mplayer-1.0*
		// Specifically IGNORE gt and lt
		$sql = "INSERT INTO ebuild_mask SELECT pm.id, e.id, 1 FROM ebuild e INNER JOIN package p ON e.package = p.id INNER JOIN package_mask pm ON pm.package = e.package WHERE e.status IN($e_status) AND pm.status = $pm_status AND e.pvr LIKE (pm.pvr || '%') AND eq = TRUE AND ar = FALSE AND av = TRUE;";
		$db->query($sql);

		// Insert entries where atom is like:
		// ~media-video/mplayer-1.0
		$sql = "INSERT INTO ebuild_mask SELECT pm.id, e.id, 1 FROM ebuild e INNER JOIN package p ON e.package = p.id INNER JOIN package_mask pm ON pm.package = e.package WHERE e.status IN($e_status) AND pm.status = $pm_status AND pm.pvr = e.version AND lt = FALSE AND gt = FALSE AND eq = FALSE AND ar = TRUE AND av = FALSE;";
		$db->query($sql);

		// All others
		$sql = "SELECT pm.id AS pm_id, pm.package, pm.atom, pm.version AS pm_version, pm.gt, pm.lt, pm.eq, pl.level AS pm_level, e.version AS ebuild_version, el.level AS ebuild_level, e.id AS ebuild FROM package_mask pm INNER JOIN view_pmask_level pl ON pl.id = pm.id INNER JOIN ebuild e ON e.package = pm.package INNER JOIN view_ebuild_level el ON el.id = e.id WHERE e.status IN($e_status) AND pm.status = $pm_status AND (pm.gt = TRUE OR pm.lt = TRUE) ORDER BY pm.gt, pm.eq, pm.package;";
	//  	shell::msg($sql);

		$arr = $db->getAll($sql);

		if(count($arr)) {
			foreach($arr as $row) {

				extract($row);

				if(!$arr_pmask[$pm_id]) {
					$arr_pmask[$pm_id] = array(
						'atom' => $atom,
						'package' => $package,
						'version' => $pm_version,
						'level' => $pm_level,
						'gt' => $gt,
						'lt' => $lt,
						'eq' => $eq,
					);

					$arr_versions[$pm_id]['mask'] = $pm_version;

				}

		// 		print_r($arr_pmask);

				$arr_ebuilds[$pm_id][$ebuild] = array(
					'version' => $ebuild_version,
					'level' => $ebuild_level,
				);

				$arr_versions[$pm_id][$ebuild] = $ebuild_version;

			}

		// 	print_r($arr_versions);

			foreach($arr_versions as $pm_id => $arr) {

				// Strip out any alpha chars, since we don't need them here
				foreach($arr as $key => $value)
					$arr[$key] = preg_replace("/[A-Za-z]/", "", $value);

				$ext = extendVersions($arr);

				$arr_extended[$pm_id] = $ext;

		//  		print_r($arr);
		// 		print_r($ext);

		// 		die;

			}

		// 	print_r($arr_extended);

			foreach($arr_pmask as $pm_id => $arr) {

				extract($arr);

				$mask_version = $arr_extended[$pm_id]['mask'];

				foreach($arr_extended[$pm_id] as $key => $str) {

					// Check against versions
					if($key != 'mask' && ( ($gt == 't' && $str > $mask_version) || ($lt == 't' && $str < $mask_version) ) ) {

						$arr_ebuild_masks[$pm_id][] = $key;

						$arr_insert = array(
							'package_mask' => $pm_id,
							'ebuild' => $key,
							'status' => 1
						);

						$db->autoExecute('ebuild_mask', $arr_insert, MDB2_AUTOQUERY_INSERT);

					// If its the same version, look closer
					} elseif($key != 'mask' && $str == $mask_version) {

						$pm_level = $arr_pmask[$pm_id]['level'];
						$ebuild_level = $arr_ebuilds[$pm_id][$key]['level'];

						$pm_ext = $arr_extended[$pm_id][$key];

						// Check against levels (alpha, beta, etc.)
						if( ($gt == 't' && $ebuild_level > $pm_level) || ($lt == 't' && $ebuild_level < $pm_level) ) {

							$arr_insert = array(
								'package_mask' => $pm_id,
								'ebuild' => $key,
								'status' => 1,
							);

							$db->autoExecute('ebuild_mask', $arr_insert, MDB2_AUTOQUERY_INSERT);

						// If the levels are the same too, then you need to look
						// at which one is actually higher / lower
						// Actually, I'm not sure if it ever really gets to this point anyway.... currently
						// I don't have anything that gets this far.
						// I think the queries above for checking level actually grab this already.  Not sure.
						} elseif($ebuild_level == $pm_level && $eq == 'f' && $pm_ext != $str) {

							shell::msg("race condition! check import.ebuild_mask.php");

		// 					var_dump($ebuild_level);
		// 					var_dump($pm_level);

							switch($ebuild_level) {

								case "5":

		// 							var_dump($arr_pmask[$pm_id]['atom']);
		//
		// 							var_dump($arr_extended[$pm_id][$key]);
		// 							var_dump($str);
		// 							echo "\n";

									break;

							}
						}
					}
				}
			}
		}
	}


?>
