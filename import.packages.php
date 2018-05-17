<?php

	echo "[Packages]\n";

	/**
	 * Insert and update category packages
	 *
	 * Check the category/package Manifest to determine if there has been a change
	 * to a package or not.
	 */

	/**
	 * This is the first file where tables start to have a status column.  There is
	 * only three status levels: 0 - completely imported, and "live", 1 - being updated,
	 * or newly inserted and 2 - flagged to be removed.
	 *
	 * The package table only uses 0 and 1.  The website should ignore the status, since
	 * the changes are only to notify the other scripts that something has changed, and to
	 * look more closely at the files related to the package.
	 */

	require_once 'header.php';

	if(!$tree)
		$tree = PortageTree::singleton();

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.package.manifest.php';
	require_once 'class.db.package.php';
	require_once 'class.db.package.manifest.php';

	// Verify that categories are imported
	$sql = "SELECT COUNT(1) FROM category;";
	$count = current(pg_fetch_row(pg_query($sql)));
	if(!$count) {
		echo "* No categories in the database\n";
		goto end_packages;
	}

	$portage_tree = $tree->getTree();

	if(!isset($a_larry_categories))
		$a_larry_categories = $tree->getCategories();

	// Get the package Manifest files
	$retval = -1;
	$a_package_manifest_hashes = array();
	$find_out_filename = "/tmp/znurt.find.out";
	$str = "find $portage_tree -mindepth 3 -maxdepth 3 -type f -name Manifest > $find_out_filename";
	echo "* Exec:		$str\n";
	passthru($str, $retval);
	$file_contents = file($find_out_filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	$a_larry_cps = array();

	// Get the hashes of the Manifest files
	foreach($file_contents as $filename) {

		$arr = explode('/', $filename);

		// Drop the 'Manifest' from the string
		array_pop($arr);

		$package_name = array_pop($arr);
		$category_name = array_pop($arr);

		// Skip Larry directories like 'metadata/glsa'
		if(!in_array($category_name, $a_larry_categories))
			continue;

		$cp = "$category_name/$package_name";
		$a_larry_cps[] = $cp;

		$manifest_hash = md5($filename);
		$a_package_manifest_hashes[$category_name][$package_name] = $manifest_hash;
		$a_larry_manifests[$cp] = $manifest_hash;

	}

	// Display package count in portage
	$i_larry_packages = count($a_larry_cps);
	echo "* Larry:	$i_larry_packages\n";

	// Display package count in database
	$sql = "SELECT COUNT(1) FROM package;";
	$i_znurt_packages = current(pg_fetch_row(pg_query($sql)));
	echo "* Znurt:	$i_znurt_packages\n";

	// Get the existing category => package names from the database
	$sql = "SELECT category_name, package_name FROM view_package;";
	$rs = pg_query($sql);

	$a_znurt_cps = array();

	while($row = pg_fetch_assoc($rs)) {
		$cp = $row['category_name']."/".$row['package_name'];
		$a_znurt_cps[] = $cp;
	}

	// Find packages to insert and delete
	$a_insert_cps = array_diff($a_larry_cps, $a_znurt_cps);
	$a_delete_cps = array_diff($a_znurt_cps, $a_larry_cps);

	$i_insert_count = count($a_insert_cps);
	$i_delete_count = count($a_delete_cps);

	// Delete removed packages
	echo "* Delete: 	$i_delete_count\n";
	foreach($a_delete_cps as $cp) {

		$q_cp = pg_escape_literal($cp);
		$sql = "DELETE FROM package WHERE id IN (SELECT package FROM view_package WHERE cp = $q_cp);";

		$rs = pg_query($sql);

		if($rs === false) {
			echo "$sql\n";
			echo pg_last_error();
			echo "\n";
		}

	}

	// Insert new packages with manifest hashes, distfiles
	echo "* Insert: 	$i_insert_count\n";
	$counter = 1;
	foreach($a_insert_cps as $cp) {

		$arr = explode('/', $cp);
		$category_name = $arr[0];
		$package_name = $arr[1];

		$q_cp = pg_escape_literal($cp);
		$q_category_name = pg_escape_literal($category_name);
		$q_package_name = pg_escape_literal($package_name);

		$manifest_hash = $a_package_manifest_hashes[$category_name][$package_name];
		$q_manifest_hash = pg_escape_literal($manifest_hash);

		echo "\033[K";
		echo "* Progress:	$counter/$i_insert_count\r";
		$counter++;

		$sql = "INSERT INTO package (category, name, manifest_hash) SELECT c.id, $q_package_name, $q_manifest_hash FROM category c WHERE c.name = $q_category_name;";

		$rs = pg_query($sql);

		if($rs === false) {
			echo "$sql\n";
			echo pg_last_error();
			echo "\n";
		}

		// Insert distfiles for new packages
		$pm = new PackageManifest($category_name, $package_name);
		$a_distfiles = $pm->getDistfiles();
		$filesize = $pm->filesize;

		foreach($a_distfiles as $filename) {

			$q_cp = pg_escape_literal($cp);
			$q_filename = pg_escape_literal($filename);

			$sql = "INSERT INTO package_files (package, filename, filesize) SELECT package, $q_filename, $filesize FROM view_package WHERE cp = $q_cp;";

			$rs = pg_query($sql);

			if($rs === false) {
				echo "$sql\n";
				echo pg_last_error();
				echo "\n";
			}

		}

	}

	if($i_insert_count)
		echo "\n";

	// Find packages where the Manifest hashes have changed
	// Note that I could use an array_diff here, but I don't want to run it on thousands of values
	$a_update_cps = array();
	$sql = "SELECT cp, manifest_hash FROM view_package_manifest;";
	$rs = pg_query($sql);
	while($row = pg_fetch_assoc($rs)) {
		$cp = $row['cp'];
		if($row['manifest_hash'] != $a_larry_manifests[$cp]) {
			$a_update_cps[] = $cp;
		}
	}

	$i_update_count = count($a_update_cps);
	echo "* Update:	$i_update_count\n";

	// Update package manifests, distfiles
	$counter = 1;
	foreach($a_update_cps as $cp) {

		$q_cp = pg_escape_literal($cp);
		$manifest_hash = $a_larry_manifests[$cp];
		$q_manifest_hash = pg_escape_literal($manifest_hash);
		$sql = "UPDATE package SET manifest_hash = $q_manifest_hash WHERE id IN (SELECT package FROM view_package WHERE cp = $q_cp);";

		echo "\033[K";
		echo "* Progress:	$counter/$i_update_count\r";
		$counter++;

		$rs = pg_query($sql);

		if($rs === false) {
			echo pg_last_error();
			echo "\n";
		}

		// It's easier and safer (and lazier) to simply reset distfiles

		$sql = "DELETE FROM package_files WHERE id IN (SELECT package FROM view_package WHERE cp = $q_cp);";

		$rs = pg_query($sql);

		if($rs === false) {
			echo pg_last_error();
			echo "\n";
		}

		$pm = new PackageManifest($category_name, $package_name);
		$a_distfiles = $pm->getDistfiles();
		$filesize = $pm->filesize;

		foreach($a_distfiles as $filename) {

			$q_cp = pg_escape_literal($cp);
			$q_filename = pg_escape_literal($filename);

			$sql = "INSERT INTO package_files (package, filename, filesize) SELECT package, $q_filename, $filesize FROM view_package WHERE cp = $q_cp;";

			$rs = pg_query($sql);

			if($rs === false) {
				echo pg_last_error();
				echo "\n";
			}

		}

	}

	if($i_update_count)
		echo "\n";

	// Find the packages updated since last time
	$sql = "SELECT COUNT(1) FROM package;";
	$count = $db->getOne($sql);

	if($count === "0") {
		$sql = "ALTER SEQUENCE package_id_seq RESTART WITH 1;";
		$db->query($sql);
	}

	// Cleanup large variables
	unset($a_larry_categories);
	unset($a_package_manifest_hashes);
	unset($file_contents);
	unset($a_larry_cps);
	unset($a_larry_manifests);
	unset($a_insert_cps);
	unset($a_delete_cps);
	unset($a_distfiles);
	unset($a_update_cps);
	unset($a_distfiles);

	end_packages:

?>
