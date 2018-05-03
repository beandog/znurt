<?php

 	$verbose = true;
// 	$qa = true;

 	// $debug = true;

	/**
	 * It may seem a little odd, and to break normalization, to have a query to set the description on the package
	 * table when it can be queried from the ebuilds.  The fact is this is just one of many shortcuts taken, since
	 * the site is a snapshot, and information like that is not required in realtime.  Not to mention it makes
	 * life a whole lot easier.
	 */

	require_once 'header.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';

	// Find all the ebuilds that are missing ebuild arch
	$sql = "SELECT * FROM missing_metadata ORDER BY category_name, package_name, pf;";
	$arr = $db->getAll($sql);

	if($verbose)
		shell::msg(number_format(count($arr))." ebuilds to check");

	$total = count($arr);
	$count = 0;

	foreach($arr as $row) {
		extract($row);

		$e = new PortageEbuild("$category_name/$pf");

		shell::msg("$category_name/$e ($count/$total)");

		$arr_metadata = $e->metadata();

		if(count($arr_metadata)) {

			foreach($arr_metadata as $keyword => $value) {

				if(!empty($value)) {
					$arr_insert = array(
						'ebuild' => $ebuild,
						'keyword' => $keyword,
						'value' => $value,
					);

					$db->autoExecute('ebuild_metadata', $arr_insert, MDB2_AUTOQUERY_INSERT);
				}
			}
		} else {
			if($verbose || $qa)
				shell::msg("[QA] No metadata: $category_name/".$e->pf);
		}

		$count++;

	}

	// Set the new package descriptions
	$sql = "SELECT COUNT(1) FROM package WHERE status = 1 OR description = '';";
	$count = $db->getOne($sql);
	$total = 1;
	if($count) {
		if($verbose)
			shell::msg("Setting the new package descriptions for $count packages");

		$sql = "SELECT p.id FROM package p INNER JOIN package_recent pr ON pr.package = p.id WHERE (p.status = 1 AND p.portage_mtime = pr.max_ebuild_mtime) OR p.description = '';";
		$arr = $db->getCol($sql);
		foreach($arr as $package_id) {

			$total++;

			$sql = "UPDATE package SET description = package_description(id) WHERE id = $package_id;";
			$db->query($sql);
		}
		// $sql = "UPDATE package SET description = package_description(id) WHERE id IN (SELECT p.id FROM package p INNER JOIN package_recent pr ON pr.package = p.id WHERE (p.status = 1 AND p.portage_mtime = pr.max_ebuild_mtime) OR p.description = '');";
		// $db->query($sql);
	}

?>
