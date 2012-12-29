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

	// Verify that categories are imported
	$sql = "SELECT COUNT(1) FROM category;";
	$sth = $dbh->query($sql);
	$num_db_categories = $sth->fetchColumn();
	if($num_db_categories === 0) {
		die("There are no categories in the database.  Import those before importing packages.\n");
		exit;
	}

	$arr_update = array();

	// Reset sequence if table is empty
	$sql = "SELECT COUNT(1) FROM package;";
	$sth = $dbh->query($sql);
	$num_db_packages = $sth->fetchColumn();
	if($num_db_packages === 0) {
		$sql = "ALTER SEQUENCE package_id_seq RESTART WITH 1;";
		$dbh->exec($sql);
	}
	
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
	
	$sql = "SELECT id, name FROM category ORDER BY name;";
	$sth = $dbh->query($sql);
	$num_db_categories = $sth->rowCount();


	function getCategoryPackageNames($str_category_name) {

		$obj_portage_category = new PortageCategory($str_category_name);
		$arr_package_names = $obj_portage_category->getPackages();

		return $arr_package_names;
		
	}

	$arr_db_category = $sth->fetch();

	$arr_package_names = getCategoryPackageNames($arr_db_category['name']);

	// This would usually be a foreach loop
	$str_package_name = current($arr_package_names);

	// Check to see if the package is already in the database
	// These three lines would be outside the for loop, because you only set it once.
	// README.php: It's logical to use bindParam() if you are going to run a LOT of queries with the same
	// variable names.  But, if you are just running something once, and want to use a prepared
	// statement, then bindValue() makes more sense.
	// bindParam() is just a nice shortcut, because all you have to do is change the variable values (instead
	// of bindValue() where you would re-run the code).  So, bindParam() once or bindValue() multiple times. :)
	$stmt = $dbh->prepare("SELECT id FROM package WHERE category = :category AND name = :name");
	$stmt->bindParam(':category', $arr_db_category['id']);
	$stmt->bindParam(':name', $str_package_name);
	
	// This line would execute the query, using the variables that are set by $arr_db_category
	// and this would be inside the for loop.
	$stmt->execute();
	// So would this, since we're returning just this result
	// README.php : If there is ZERO rows returned, then the result will be FALSE
	$db_package_id = $stmt->fetchColumn();

	/*
	 * Insert a 'package' record
	 * @return primary key
	 */
	// FIXME: standardize this stuff / create functions for insert of arbitrary values
	function insert_package($db_category_id, $str_package_name, $int_portage_mtime) {

		global $dbh;
	
		$stmt = $dbh->prepare("INSERT INTO package (category, name, portage_mtime) VALUES (:category, :name, :portage_mtime);");
		$stmt->bindValue(':category', $db_category_id);
		$stmt->bindValue(':name', $str_package_name);
		$stmt->bindValue(':portage_mtime', $int_portage_mtime);

		$stmt->execute();

		$int_db_package_id = $dbh->lastInsertID('package_id_seq');

		return $int_db_package_id;

	}

	// FIXME: standardize this stuff / create functions for insert of arbitrary values
	function insert_package_changelog($db_package_id, $arr_values) {

		global $dbh;

		// FIXME : Create a build_insert_query function
		$sql = "INSERT INTO package_changelog (package, changelog, mtime, filesize, recent_changes) VALUES (:package, :changelog, :mtime, :filesize, :recent_changes);";
		$stmt = $dbh->prepare($sql);
		$stmt->bindValue(':package', $db_package_id);
		foreach($arr_values as $key => $value)
			$stmt->bindValue(":$key", $value);

		$bool = $stmt->execute();

		if($bool === false) {
			print_r($stmt->errorInfo());
		}

		return $bool;

	}

	// FIXME: standardize this stuff / create functions for insert of arbitrary values
	function insert_package_manifest($db_package_id, $arr_values) {

		global $dbh;

		$sql = "INSERT INTO package_manifest (package, manifest, mtime, hash, filesize) VALUES (:package, :manifest, :mtime, :hash, :filesize);";
		$stmt = $dbh->prepare($sql);
		$stmt->bindValue(':package', $db_package_id);
		foreach($arr_values as $key => $value)
			$stmt->bindValue(":$key", $value);

		print_r($arr_values);

		$bool = $stmt->execute();

		if($bool === false) {
			print_r($stmt->errorInfo());
		}

		return $bool;

	}



	function import_category_package($db_category_id, $str_category_name, $str_package_name) {

		// FIXME ugh.
		global $dbh;
	
		// Insert package
		$obj_portage_package = new PortagePackage($str_category_name, $str_package_name);
		$int_portage_mtime =& $obj_portage_package->portage_mtime;
		$int_db_package_id = insert_package($db_category_id, $str_package_name, $int_portage_mtime);

		// Insert package changelog
		$obj_package_changelog = new PackageChangelog($str_category_name, $str_package_name);
		$arr_package_changelog_vars = array('changelog', 'mtime', 'filesize', 'recent_changes');
		foreach($arr_package_changelog_vars as $str)
			$arr_package_changelog_values[$str] =& $obj_package_changelog->{$str};
		insert_package_changelog($int_db_package_id, $arr_package_changelog_values);

		// Insert package manifest 
		// FIXME: Fetching 'manifest' from object is broken
		$obj_package_manifest = new PackageManifest($str_category_name, $str_package_name);
		print_r($obj_package_manifest);
		$arr_package_manifest_vars = array('manifest', 'mtime', 'hash', 'filesize');
		foreach($arr_package_manifest_vars as $str)
			$arr_package_manifest_values[$str] =& $obj_package_changelog->{$str};
		insert_package_manifest($int_db_package_id, $arr_package_manifest_values);


		die;


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


	if($db_package_id === false) {
		import_category_package($arr_db_category['id'], $arr_db_category['name'], $str_package_name);
	}



	die;

	while($arr = $sth->fetch()) {

		// print_r($arr);

	}
	echo $num_db_categories;
	die;

	
	// FIXME: REMOVE Massive query: 16k rows
	$sql = "SELECT category, package, category_name, package_name FROM view_package;";
	$arr = $db->getAll($sql);
	foreach($arr as $row) {
		$arr_package_ids[$row['category_name']][$row['package_name']] = $row['package'];
	}

	$num_categories = count($arr_categories);
	$counter_categories = 1;

	echo "importing category packages\n";
	
	foreach($arr_categories as $category_id => $category_name) {

		$c = new PortageCategory($category_name);
		$arr_packages = $c->getPackages();

		$num_packages = count($arr_packages);
		$counter_categories = str_pad($counter_categories, strlen($num_categories), 0, STR_PAD_LEFT);

		echo "[$counter_categories/$num_categories] $category_name ($num_packages)\n";
		$counter_categories++;
		
		// FIXME: REMOVE This is also going to be a LARGE query
		$arr_diff = importDiff('package', $arr_packages, "category = $category_id");
		
		// FIXME Flag to be deleted, execute later
		// This is dangerous to delete right now because 1) it will take a *long* time, and
		// 2) you're breaking the whole "snapshot" approach.
		if(count($arr_diff['delete'])) {
			foreach($arr_diff['delete'] as $package_name) {
				$sql = "DELETE FROM package WHERE name = ".$db->quote($package_name)." AND category = $category_id;";
				$db->query($sql);
			}
		}
		
		if(count($arr_diff['insert'])) {
		
			foreach($arr_diff['insert'] as $package_name) {
			
				$p = new PortagePackage($category_name, $package_name);
				$ch = new PackageChangelog($category_name, $package_name);
				$ma = new PackageManifest($category_name, $package_name);
			
 				$arr_insert = array(
 					'category' => $category_id,
 					'name' => $package_name,
 					'portage_mtime' => $p->portage_mtime,
 				);
				$db->autoExecute('package', $arr_insert, MDB2_AUTOQUERY_INSERT);
				
				$package_id = $db->lastInsertID();
				
				// New changelog entry
				$arr_insert = array(
					'package' => $package_id,
					'changelog' => $ch->changelog,
					'mtime' => $ch->mtime,
					'hash' => $ch->hash,
					'filesize' => $ch->filesize,
					'recent_changes' => $ch->recent_changes,
				);
				
				$db->autoExecute('package_changelog', $arr_insert, MDB2_AUTOQUERY_INSERT);
				
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
