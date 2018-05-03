<?php

	/**
	 * This script creates a temporary file in /tmp named znurt[foo] that sets itself
	 * to the mtime of the latest package mtime in the database.  This way, I can simply
	 * use find to do all the heavy lifting to quickly locate any package directories
	 * that were modified since last import.
	 *
	 * Directory names in portage tend to get their mtimes updated on a regular basis;
	 * generally speaking, I'd say that about 50% of them change each sync, though
	 * I can't pin down why.  Packages that haven't been touched in ages get their
	 * directory modified for no reason I can see.
	 *
	 * As a result, the mtime of a package is notoriously unreliable as a reference
	 * for anything.  However, if it does change, it *can* indicate that an ebuild or
	 * file was removed, so, with all due diligence, we will check those later to see
	 * if something was actually taken away, and update the database.
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

	$tree =& PortageTree::singleton();

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.package.changelog.php';
	require_once 'class.portage.package.manifest.php';
	require_once 'class.db.package.php';
	require_once 'class.db.package.changelog.php';
	require_once 'class.db.package.manifest.php';

	// Verify that categories are imported
	$sql = "SELECT COUNT(1) FROM category;";
	$count = $db->getOne($sql);
	if($count === '0') {
		die("There are no categories in the database.  Import those before importing packages.\n");
		exit;
	}

	$arr_update = array();

	// Find the packages updated since last time
	$sql = "SELECT COUNT(1) FROM package;";
	$count = $db->getOne($sql);
	if(!$count || $debug)
		$all = true;
	else {

		$sql = "SELECT MAX(portage_mtime) FROM package;";
		$max_portage_mtime = $db->getOne($sql);

		if(is_null($max_portage_mtime))
			$all = true;

	}

	if($count === "0") {
		$sql = "ALTER SEQUENCE package_id_seq RESTART WITH 1;";
		$db->query($sql);
	}

	if(!$all) {

		$categories = $tree->getCategories();

		$tmp = tempnam('/tmp', 'znurt');
		touch($tmp, $max_portage_mtime);

		$arr = array();

		$dir = $tree->getTree();

		foreach($categories as $category_name) {

			$category_dir = $dir."/".$category_name;

			$exec = "find $category_dir -mindepth 1 -maxdepth 1 -type d -newer $tmp";
			$arr = array_merge($arr, shell::cmd($exec));
		}
		unlink($tmp);

		$count = 0;

		foreach($arr as $name) {

			$name = str_replace($dir."/", "", $name);
			$tmp = explode("/", $name);
			$arr_update[$tmp[0]][] = $tmp[1];

			$count++;

		}

	}

	$sql = "SELECT id, name FROM category ORDER BY name;";
	$arr_categories = $db->getAssoc($sql);

	$sql = "SELECT category, package, category_name, package_name FROM view_package;";
	$arr = $db->getAll($sql);
	foreach($arr as $row) {
		$arr_package_ids[$row['category_name']][$row['package_name']] = $row['package'];
	}

	$num_categories = count($arr_categories);
	$counter_categories = 1;

	echo "[Packages]\n";

	foreach($arr_categories as $category_id => $category_name) {

		$c = new PortageCategory($category_name);
		$arr_packages = $c->getPackages();

		$num_packages = count($arr_packages);
		$counter_categories = str_pad($counter_categories, strlen($num_categories), 0, STR_PAD_LEFT);

		// echo "[$counter_categories/$num_categories] $category_name ($num_packages)\n";
		$counter_categories++;

		$arr_diff = importDiff('package', $arr_packages, "category = $category_id");

		// FIXME Flag to be deleted, execute later
		// This is dangerous to delete right now because 1) it will take a *long* time, and
		// 2) you're breaking the whole "snapshot" approach.
		if(count($arr_diff['delete'])) {
			
			echo "* Deleting num packages: ".count($arr_diff['delete'])."\n";

			foreach($arr_diff['delete'] as $package_name) {
				echo "* Deleting package: $package_name\n";
				$sql = "DELETE FROM package WHERE name = ".$db->quote($package_name)." AND category = $category_id;";
				$db->query($sql);
			}
		}

		if(count($arr_diff['insert'])) {

			echo "[$category_name]\n";
			echo "* New packages: ".count($arr_diff['insert'])."\n";

			/** Package Names **/

			$arr_insert_sql = array();
			$arr_insert_package = array();

			foreach($arr_diff['insert'] as $package_name) {

				$p = new PortagePackage($category_name, $package_name);

				$arr_insert_sql = array(pg_escape_literal($category_id), pg_escape_literal($package_name), pg_escape_literal($p->portage_mtime));

				$arr_insert_package[] = '('.implode(', ', $arr_insert_sql).')';

				echo "* $category_name/$package_name\n";

			}

			$sql_insert = "BEGIN;\n";
			$sql_insert .= "INSERT INTO package (category, name, portage_mtime) VALUES\n";
			$sql_insert .= implode(",\n", $arr_insert_package).";\n";
			$sql_insert .= "COMMIT;\n";

			pg_query($sql_insert);

			/** Package Changelogs **/

			echo "[Changelogs]\n";

			$arr_insert_sql = array();
			$arr_insert_changelog = array();

			foreach($arr_diff['insert'] as $package_name) {

				$sql = "SELECT id FROM package WHERE category = ".pg_escape_literal($category_id)." AND name = ".pg_escape_literal($package_name).";";
				$package_id = current(pg_fetch_row(pg_query($sql)));

				$ch = new PackageChangelog($category_name, $package_name);

				if($ch->valid == false)
					continue;

				$arr_insert_sql = array(
					pg_escape_literal($package_id),
					pg_escape_literal($ch->changelog),
					pg_escape_literal($ch->mtime),
					pg_escape_literal($ch->hash),
					pg_escape_literal($ch->filesize),
					pg_escape_literal($ch->recent_changes),
				);

				$arr_insert_changelog[] = '('.implode(', ', $arr_insert_sql).')';

			}

			if(count($arr_insert_changelog)) {

				$sql_insert = "BEGIN;\n";
				$sql_insert .= "INSERT INTO package_changelog (package, changelog, mtime, hash, filesize, recent_changes) VALUES\n";
				$sql_insert .= implode(",\n", $arr_insert_changelog).";\n";
				$sql_insert .= "COMMIT;\n";

				pg_query($sql_insert);

			}

			foreach($arr_diff['insert'] as $package_name) {

				$ma = new PackageManifest($category_name, $package_name);

				// New Manifest entry
				$arr_insert = array(
					'package' => $package_id,
					'manifest' => $ma->manifest,
					'mtime' => $ma->mtime,
					'hash' => $ma->hash,
					'filesize' => $ma->filesize,
				);

				$db->autoExecute('package_manifest', $arr_insert, MDB2_AUTOQUERY_INSERT);

				// Import package files
				$arr = $ma->getDistfiles();

				foreach($arr as $filename) {

					$arr_insert = array(
						'package' => $package_id,
						'filename' => $filename,
						'type' => 'DIST',
						'hash' => $ma->getHash($filename),
						'filesize' => $ma->getFilesize($filename),
					);

					$db->autoExecute('package_files', $arr_insert, MDB2_AUTOQUERY_INSERT);

				}

				// Import patches
				$arr = $ma->getFiles();

				foreach($arr as $filename) {

					$arr_insert = array(
						'package' => $package_id,
						'filename' => $filename,
						'type' => 'AUX',
						'hash' => $ma->getHash($filename),
						'filesize' => $ma->getFilesize($filename),
					);

					$db->autoExecute('package_files', $arr_insert, MDB2_AUTOQUERY_INSERT);

				}

			}

		}

	}

	unset($c, $p, $ch, $ma, $arr_insert, $arr_diff, $arr_packages, $arr_categories, $categories, $package_id, $arr, $filename);

	$count = 0;

	foreach($arr_update as $category_name => $arr_packages) {

		foreach($arr_packages as $package_name) {

			$package_id = $arr_package_ids[$category_name][$package_name];

			if($package_id) {

				$p = new PortagePackage($category_name, $package_name);
				$db_package = new DBPackage($package_id);

				$manifest = new PackageManifest($category_name, $package_name, $tree->getTree());
				$db_manifest = new DBPackageManifest($package_id);

				if($manifest->hash != $db_manifest->hash || $debug) {

					if($debug) {
						shell::msg("Updating $category_name/$package_name id: $package_id");
					}

					$changelog = new PackageChangelog($category_name, $package_name, $tree->getTree());
					$db_changelog = new DBPackageChangelog($package_id);

					// If the hash of this Manifest file changed, then a file
					// somewhere has been added, deleted or modified.  Flag the status
					// to make sure we examine that directory later.
					$db_package->status = 1;

					// Update the manifest in the DB
					$db_manifest->hash = $manifest->hash;
					$db_manifest->manifest = $manifest->manifest;
					$db_manifest->mtime= $manifest->mtime;
					$db_manifest->filesize = $manifest->filesize;

					// Same for the changelog entry if it's changed on the filesystem.
					// FIXME Add metadata.xml as well
					if($changelog->hash != $db_changelog->hash) {
						$db_changelog->hash = $changelog->hash;
						$db_changelog->manifest = $changelog->manifest;
						$db_changelog->mtime= $changelog->mtime;
						$db_changelog->filesize = $changelog->filesize;
 						$db_changelog->changelog = $changelog->changelog;
 						$db_changelog->recent_changes = $changelog->recent_changes;
					}

					// FIXME hopefully can phase out mtime soon.
					if($p->portage_mtime != $db_package->portage_mtime) {
						$db_package->portage_mtime = $p->portage_mtime;
					}

				}
			}
		}
	}

	unset($arr_update, $category_name, $arr_packages, $package_id, $arr_package_ids, $db_package);

?>
