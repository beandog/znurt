<?php

	// README.php : This returns FALSE
	/*
	$sql = "SELECT FALSE;";
	$sth = $dbh->query($sql);
	$var = $sth->fetchColumn();
	var_dump($var);
	*/

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

	function getCategoryPackageNames($str_category_name) {

		$obj_portage_category = new PortageCategory($str_category_name);
		$arr_package_names = $obj_portage_category->getPackages();

		return $arr_package_names;
		
	}

	/*
	 * Insert a 'package' record
	 * @return primary key
	 */
	// FIXME: standardize this stuff / create functions for insert of arbitrary values
	function insert_package($arr_package) {

		global $dbh;
	
		$stmt = $dbh->prepare("INSERT INTO package (category, name, portage_mtime) VALUES (:category, :name, :portage_mtime);");
		$stmt->bindValue(':category', $arr_package['category']);
		$stmt->bindValue(':name', $arr_package['name']);
		$stmt->bindValue(':portage_mtime', $arr_package['portage_mtime']);

		$boot = $stmt->execute();

		if($bool === false) {
			print_r($stmt->errorInfo());
			return false;
		} else {
			$int_db_package_id = $dbh->lastInsertID('package_id_seq');
			return $int_db_package_id;
		}

	}

	// FIXME: standardize this stuff / create functions for insert of arbitrary values
	function insert_package_changelog($arr_package_changelog) {

		global $dbh;

		// FIXME : Create a build_insert_query function
		$sql = "INSERT INTO package_changelog (package, changelog, mtime, filesize, recent_changes) VALUES (:package, :changelog, :mtime, :filesize, :recent_changes);";
		$stmt = $dbh->prepare($sql);
		$stmt->bindValue(':package', $arr_package_changelog['package']);
		$stmt->bindValue(':changelog', $arr_package_changelog['changelog']);
		$stmt->bindValue(':mtime', $arr_package_changelog['mtime']);
		$stmt->bindValue(':filesize', $arr_package_changelog['filesize']);
		$stmt->bindValue(':recent_changes', $arr_package_changelog['recent_changes']);

		$bool = $stmt->execute();

		if($bool === false) {
			print_r($stmt->errorInfo());
			return false;
		} else {
			$int_db_package_changelog_id = $dbh->lastInsertID('package_changelog_id_seq');
			return $int_db_package_changelog_id;
		}

	}

	// FIXME: standardize this stuff / create functions for insert of arbitrary values
	function insert_package_manifest($arr_package_manifest) {

		global $dbh;

		$sql = "INSERT INTO package_manifest (package, manifest, mtime, hash, filesize) VALUES (:package, :manifest, :mtime, :hash, :filesize);";
		$stmt = $dbh->prepare($sql);
		$stmt->bindValue(':package', $arr_package_manifest['package']);
		$stmt->bindValue(':manifest', $arr_package_manifest['manifest']);
		$stmt->bindValue(':mtime', $arr_package_manifest['mtime']);
		$stmt->bindValue(':hash', $arr_package_manifest['hash']);
		$stmt->bindValue(':filesize', $arr_package_manifest['filesize']);

		$bool = $stmt->execute();

		if($bool === false) {
			print_r($stmt->errorInfo());
			return false;
		} else {
			$int_db_package_manifest_id = $dbh->lastInsertID('package_manifest_id_seq');
			return $int_db_package_manifest_id;
		}

		return $bool;

	}

	function get_db_category_id($str_category_name) {

		global $dbh;

		$str_quote_category_name = $dbh->quote($str_category_name);
		$sql = "SELECT id FROM category WHERE name = $str_quote_category_name;";
		$sth = $dbh->query($sql);
		$int_db_category_id = $sth->fetchColumn();

		return $int_db_category_id;
		
	}


	function import_package($str_category_name, $str_package_name) {

		// FIXME ugh.
		global $dbh;

		// Fetch category id
		$int_db_category_id = get_db_category_id($str_category_name);

		if($int_db_category_id === false) {
			echo "category $str_category_name is not imported";
			return false;
		}

		// Insert package
		$obj_portage_package = new PortagePackage($str_category_name, $str_package_name);
		$int_portage_mtime =& $obj_portage_package->portage_mtime;
		$arr_package = array(
			'category' => $int_db_category_id,
			'name' => $str_package_name,
			'mtime' => $int_portage_mtime,
		);
		$int_db_package_id = insert_package($arr_package);

		// Insert package changelog
		$obj_package_changelog = new PackageChangelog($str_category_name, $str_package_name);
		$arr_package_changelog = array(
			'package' => $int_db_package_id,
			'changelog' => $obj_package_changelog->changelog,
			'mtime' => $obj_package_changelog->mtime,
			'filesize' => $obj_package_changelog->filesize,
			'recent_changes' => $obj_package_changelog->recent_changes,
		);
		$int_db_package_changelog_id = insert_package_changelog($arr_package_changelog);

		// Insert package manifest 
		// FIXME: Fetching 'manifest' from object is broken
		$obj_package_manifest = new PackageManifest($str_category_name, $str_package_name);
		$arr_package_manifest_vars = array('manifest', 'mtime', 'hash', 'filesize');
		$arr_package_manifest = array(
			'package' => $int_db_package_id,
			'manifest' => $obj_package_manifest->manifest,
			'mtime' => $obj_package_manifest->mtime,
			'hash' => $obj_package_manifest->hash,
			'filesize' => $obj_package_manifest->filesize,
		);
		$int_db_package_manifest_id = insert_package_manifest($arr_package_manifest);

		// Insert package distfiles
		$arr_package_distfiles = $obj_package_manifest->getDistfiles();
		$sql = "INSERT INTO package_files (package, filename, type, hash, filesize) VALUES (:package, :filename, :type, :hash, :filesize);";
		$stmt = $dbh->prepare($sql);
		foreach($arr_package_distfiles as $str_package_distfilename) {

			$arr_insert = array(
				'package' => $int_db_package_id,
				'filename' => $str_package_distfilename,
				'type' => 'DIST',
				'hash' => $obj_package_manifest->getHash($str_package_distfilename),
				'filesize' => $obj_package_manifest->getFilesize($str_package_distfilename),
			);


			foreach($arr_insert as $key => $value)
				$stmt->bindValue(":$key", $value);

			$bool = $stmt->execute();

			if($bool === false) {
				print_r($stmt->errorInfo());
				$int_db_package_file_id = null;
			} else {
				$int_db_package_file_id = $dbh->lastInsertID('package_files_id_seq');
			}
		}

		// Insert remaining package files (patches)
		$arr_package_files = $obj_package_manifest->getFiles();
		$sql = "INSERT INTO package_files (package, filename, type, hash, filesize) VALUES (:package, :filename, :type, :hash, :filesize);";
		$stmt = $dbh->prepare($sql);
		foreach($arr_package_files as $str_package_filename) {

			$arr_insert = array(
				'package' => $int_db_package_id,
				'filename' => $str_package_filename,
				'type' => 'AUX',
				'hash' => $obj_package_manifest->getHash($str_package_filename),
				'filesize' => $obj_package_manifest->getFilesize($str_package_filename),
			);

			foreach($arr_insert as $key => $value)
				$stmt->bindValue(":$key", $value);

			$bool = $stmt->execute();

			if($bool === false) {
				$int_db_package_file_id = null;
			} else {
				$int_db_package_file_id = $dbh->lastInsertID('package_files_id_seq');
			}
		}
	}

	// Verify that categories are imported
	$sql = "SELECT COUNT(1) FROM category;";
	$sth = $dbh->query($sql);
	$num_db_categories = $sth->fetchColumn();
	if($num_db_categories === 0) {
		die("There are no categories in the database.  Import those before importing packages.\n");
		exit;
	}

	// Reset sequence if table is empty
	$sql = "SELECT COUNT(1) FROM package;";
	$sth = $dbh->query($sql);
	$num_db_packages = $sth->fetchColumn();
	if($num_db_packages === 0) {
		$sql = "ALTER SEQUENCE package_id_seq RESTART WITH 1;";
		$dbh->exec($sql);
	}
	
	
	$sql = "SELECT name FROM category ORDER BY name;";
	$sth = $dbh->query($sql);
	$num_db_categories = $sth->rowCount();

	while($str_category_name = $sth->fetchColumn()) {

		$arr_package_names = getCategoryPackageNames($str_category_name);

		foreach($arr_package_names as $str_package_name) {

			echo "Importing category: $str_category_name package: $str_package_name";
			echo "\n";

			import_package($str_category_name, $str_package_name);
		}
		
	}

	die;

	
	/**
	 * Migrate to update / remove
	 */
	/*
	if($num_db_packages === 0 || $debug)
		$all = true;
	else {
	
		$sql = "SELECT MAX(portage_mtime) FROM package;";
		$sth = $dbh->query($sql);
		$max_portage_mtime = $sth->fetchColumn();

		if(is_null($max_portage_mtime))
			$all = true;
	
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
	*/
?>
