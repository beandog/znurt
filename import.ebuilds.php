<?php

	// FIXME not sure if the whole udate thing is working -- need it for a "new ebuilds" query

	/**
	 * The procedure to update an ebuild is this -- remove the old one, and insert the new one.
	 * This is actually much simpler than trying to update everything *and* it allows the 
	 * website to constantly keep a snapshot of it's current status regardless of backend
	 * activity.
	 *
	 * To distinguish between new and updated ebuilds in the database, post-run, the updated
	 * ebuilds will have a non-null value for the "udate" (update date) column.  New
	 * ebuilds will have a null value.
	 */

	/**
	 * Right now this will run fine is run as a cron job.  It will clean up after itself
	 * if there are NO ebuilds in the tree, but if you accidentally remove an entire package
	 * or something, it will still only insert what's new (recently modified since last mtime).
	 * For now, you're going to have to manually flip some bits to get it to correct mistakes
	 * like that.  It's too much of a pain to have it check for it (at this point).
	 */
	 
	 /**
	  * This script is similar to the package one, in that it will create a temporary file
	  * and set the mtime to the last package, and then look for any new changes.  Makes the
	  * find utility do all the heavy lifting, and is much simpler.
	  *
	  * Also, this updates the DB with the mtime of both the actual ebuild and the cache file.
	  * It seems like they are usually the same mtime, though.
	  *
	  * While it may seem odd, any time an ebuild is "changed" (as in, the mtime is different), it is
	  * actually re-inserted as a new ebuild all over again.  It would be too much work to go
	  * through all the scripts and compare differences between old and new data; it is far easier to
	  * simply re-import the data as if it was newly created.  New ebuilds are flagged with a status of 1
	  * and should be ignored by the website.  Unchanged ebuilds are flagged with a status of 0 and ones
	  * marked for removal with a 2.  The website should always pull the ones where the status is 0 or 2.
	  *
	  */

    	$verbose = true;
//   	$debug = false;
//   	$all = false;
	

	require_once 'header.php';
	
	if(!$tree) {
		$tree =& PortageTree::singleton();
	}
	
	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';
	require_once 'class.db.ebuild.php';
	
	$sql = "DELETE FROM ebuild WHERE status > 0;";
	$db->query($sql);
	
	// Check to see if there are any ebuilds
	$sql = "SELECT COUNT(1) FROM ebuild WHERE status = 0;";
	$count = $db->getOne($sql);
	
	// If there aren't any, then import *all* packages
	if(!$count || $debug)
		$all = true;
	else {
		
		// If there are, get the last modified time
		$sql = "SELECT MAX(portage_mtime) AS max_portage_mtime, MAX(cache_mtime) AS max_cache_mtime FROM ebuild WHERE status = 0;";
		$row = $db->getRow($sql);
		
		if(is_array($row)) {
			extract($row);
			
			if(is_null($max_portage_mtime) || is_null($max_cache_mtime))
				$all = true;
			else
				$min = min($max_portage_mtime, $max_cache_mtime);
		}
	}
	
	// If no ebuilds, reset the sequence
	if($count === "0") {
		$sql = "ALTER SEQUENCE ebuild_id_seq RESTART WITH 1;";
		$db->query($sql);
	}
	
	$categories = $tree->getCategories();
	$arr_import = array();
	
	// Find all the ebuilds that are currently in the db
	$arr = $arr_db = array();
	if(!$all) {
		// Find all the packages that have been updated in the last 24 hours
		// Need to check the packages themselves, because something may have
		// been deleted.
		$portage = $tree->getTree();
		$cache = $tree->getTree()."/metadata/md5-cache/";
		
		$tmp = tempnam('/tmp', 'znurt');
		touch($tmp, $min);
		
		$exec = "find $cache -type f -newer $tmp";
		$arr = shell::cmd($exec);
 		unlink($tmp);
		
		if($verbose) {
			shell::msg($exec);
			shell::msg("(".count($arr).") new/updated ebuilds found since last sync.");
		}
		
		foreach($arr as $dir) {
			$atom = str_replace($tree->getTree()."/metadata/md5-cache/", "", $dir);
			$e = new PortageEbuild($atom);
			
			$arr_import[$e->category][] = $e->pn;
			$arr_import[$e->category] = array_unique($arr_import[$e->category]);
		}
		
		// Also add any packages that were flagged when importing those
		$sql = "SELECT c.name AS category_name, p.name AS package_name FROM package p INNER JOIN category c ON c.id = p.category WHERE p.status = 1;";
		$arr = $db->getAll($sql);
		
		if(count($arr)) {
			foreach($arr as $row) {
				$arr_import[$row['category_name']][] = $row['package_name'];
				$arr_import[$row['category_name']] = array_unique($arr_import[$row['category_name']]);
			}
		}
		
		ksort($arr_import);
		
	} elseif($all) {
	
		foreach($categories as $name) {
			$c = new PortageCategory($name);
			$arr_import[$name] = $c->getPackages();
		}
	
	}
	
	if($debug || $all) {
		shell::msg("Checking ALL categories");
	} elseif($verbose) {
		shell::msg("(".count($arr_import).") RECENTLY MODIFIED categories ");
	}
	
	// Get the package IDs for reference
	// and the mtimes of the ebuilds for verification
	$sql = "SELECT p.id AS package_id, c.name AS category_name, p.name AS package_name, e.pf AS ebuild_name, e.id AS ebuild FROM ebuild e RIGHT OUTER JOIN package p ON e.package = p.id INNER JOIN category c ON p.category = c.id ORDER BY c.name, p.name;";
	$arr = $db->getAll($sql);
	if(count($arr)) {
		foreach($arr as $row) {
			extract($row);
			$arr_db[$category_name][$package_name] = $package_id;
			if($ebuild_name) {
				$arr_ebuild_ids[$category_name][$package_name][$ebuild_name] = $ebuild;
			}
		}
	}
	
	
	if(count($arr_import)) {
		
		foreach($arr_import as $category_name => $arr_category) {
		
			foreach($arr_category as $package_name) {
			
				if($debug)
					shell::msg("[$category_name/$package_name]");
			
				$arr_insert = array();
				$arr_delete = array();
				$arr_update = array();
				
				if(count($arr_ebuild_ids[$category_name][$package_name]))
					$arr_db_ebuilds = array_keys($arr_ebuild_ids[$category_name][$package_name]);
				else
					$arr_db_ebuilds = array();
					
				$p = new PortagePackage($category_name, $package_name);
				
				$package_id =& $arr_db[$category_name][$package_name];
				
				// If there are any old ebuilds (in the DB), then compare the new (in portage)
				if(count($arr_db_ebuilds)) {
				
					$arr_fs_ebuilds = $p->getEbuilds();
					
					// Check old against new
					$arr_delete = array_diff($arr_db_ebuilds, $arr_fs_ebuilds);
					$arr_insert = array_diff($arr_fs_ebuilds, $arr_db_ebuilds);
					
					// Next, look at the hashes and see if any need to be updated
					if(count($arr_fs_ebuilds)) {
					
						foreach($arr_fs_ebuilds as $ebuild_name) {
							
							$e = new PortageEbuild("$category_name/$ebuild_name");
							
							$ebuild = $arr_ebuild_ids[$category_name][$package_name][$ebuild_name];
							
							if($ebuild) {
								$db_ebuild = new DBEbuild($ebuild);
								
								if($db_ebuild->hash != $e->hash) {
								
									$arr_update[] = $ebuild_name;
									$arr_insert[] = $ebuild_name;
									
									// Normally I'd add this here, but instead, just go ahead and mark it
									// right away, and avoid having it run twice.
 									$db_ebuild->status = 2;
								
									if($verbose) {
										shell::msg("[update] $category_name/$ebuild_name");
									}
								}
							}
						}
					}
					
					// FIXME just pass the IDs
					if(count($arr_delete)) {
						foreach($arr_delete as $ebuild_name) {
							if($verbose)
								shell::msg("[delete] $category_name/$ebuild_name");
							
							$ebuild = $arr_ebuild_ids[$category_name][$package_name][$ebuild_name];
							
							if($ebuild) {
								$db_ebuild = new DBEbuild($ebuild);
								$db_ebuild->status = 2;
							}
						}
					}
				
				}
				// Otherwise, insert all of them
				else {
					$arr_insert = $p->getEbuilds();
				}
				
				if(count($arr_insert)) {
				
					$arr_insert = array_unique($arr_insert);
					
					foreach($arr_insert as $ebuild_name) {
					
						if($verbose)
							shell::msg("[insert] $category_name/$ebuild_name");
					
						$e = new PortageEbuild("$category_name/$ebuild_name");
						
						if(in_array($ebuild_name, $arr_update)) {
							$udate = $now;
						} else
							$udate = null;
						
						$arr = array(
							'package' => $package_id,
							'pf' => $e->pf,
							'pv' => $e->pv,
							'pr' => $e->pr,
							'pvr' => $e->pvr,
							'alpha' => $e->_alpha,
							'beta' => $e->_beta,
							'pre' => $e->_pre,
							'rc' => $e->_rc,
							'p' => $e->_p,
							'version' => $e->version,
							'slot' => $e->slot,
							'portage_mtime' => $e->portage_mtime,
							'cache_mtime' => $e->cache_mtime,
							'status' => 1,
							'udate' => $udate,
							'source' => $e->source,
							'filesize' => $e->filesize,
							'hash' => $e->hash,
						);
						
						$db->autoExecute('ebuild', $arr, MDB2_AUTOQUERY_INSERT);
						
						
					}
					
				}
				
			}
			
		}
		
	}
	
	unset($e, $p, $db_ebuild, $db_package, $arr, $arr_insert, $arr_update);
	
	// Update the package_recent entries
	$sql = "DELETE FROM package_recent WHERE status = 1;";
	$db->query($sql);
	
	$sql = "INSERT INTO package_recent SELECT DISTINCT package, MAX(cache_mtime), 1 AS status FROM ebuild e GROUP BY package ORDER BY MAX(cache_mtime) DESC, package;";
	$db->query($sql);
	
	// Same for the arches
	$sql = "INSERT INTO package_recent_arch SELECT DISTINCT package, MAX(cache_mtime), 1 AS status, ea.arch FROM ebuild e LEFT OUTER JOIN ebuild_arch ea ON ea.ebuild = e.id WHERE ea.arch IS NOT NULL AND ea.status != 2 GROUP BY package, ea.arch ORDER BY MAX(cache_mtime) DESC, package;";
	$db->query($sql);
	
	
?>
