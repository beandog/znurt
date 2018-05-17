<?php

	echo "[Ebuilds]\n";

	// FIXME not sure if the whole udate thing is working -- need it for a "new ebuilds" query

	/**
	 * The procedure to update an ebuild is this -- remove the old one, and insert the new one.
	 * This is actually much simpler than trying to update everything *and* it allows the
	 * website to constantly keep a snapshot of its current status regardless of backend
	 * activity.
	 */

	require_once 'header.php';

	if(!$tree)
		$tree = PortageTree::singleton();

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';
	require_once 'class.db.ebuild.php';

	// Get the arches
	$a_larry_arches = $tree->getArches();

	// Get the portage licenses
	$a_larry_licenses = $tree->getLicenses();

	// Procedure to enter all data into ebuild table and return the resulting new primary key
	// FIXME will fail if the package is not in the database
	$rs = pg_prepare('insert_ebuild', 'INSERT INTO ebuild (package, pf, pv, pr, pvr, alpha, beta, pre, rc, p, version, slot, hash, description, keywords, license, iuse) SELECT vp.package, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17 FROM view_package vp WHERE vp.cp = $1 RETURNING ebuild.id;');
	if($rs === false) {
		echo pg_last_error();
		echo "\n";
	}

	$rs = pg_prepare('insert_homepage', 'INSERT INTO ebuild_homepage (ebuild, homepage) VALUES ($1, $2);');
	if($rs === false) {
		echo pg_last_error();
		echo "\n";
	}

	// FIXME will fail if the arch is not in the database
	$rs = pg_prepare('insert_ebuild_arch', 'INSERT INTO ebuild_arch (ebuild, status, arch) SELECT $1, $2, a.id FROM arch a WHERE a.name = $3;');
	if($rs === false) {
		echo pg_last_error();
		echo "\n";
	}

	// FIXME will fail if the license is not in the database
	$rs = pg_prepare('insert_ebuild_license', 'INSERT INTO ebuild_license (ebuild, license) SELECT $1, l.id FROM license l WHERE l.name = $2');
	if($rs === false) {
		echo pg_last_error();
		echo "\n";
	}

	// Verify that packages are imported
	$sql = "SELECT COUNT(1) FROM package;";
	$count = current(pg_fetch_row(pg_query($sql)));
	if(!$count) {
		echo "* No packages in the database\n";
		goto end_ebuilds;
	}

	// Get the portage ebuilds
	$portage_tree = $tree->getTree();
	$retval = -1;
	$find_out_filename = "/tmp/znurt.find.ebuilds.out";
	$str = "find $portage_tree -mindepth 3 -maxdepth 3 -type f -name '*.ebuild' > $find_out_filename";
	echo "* Exec:		$str\n";
	passthru($str, $retval);
	$file_contents = file($find_out_filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	sort($file_contents);

	$a_larry_hashes = array();
	$a_larry_ebuilds = array();

	foreach($file_contents as $filename) {

		$arr = explode('/', $filename);

		$ebuild = array_pop($arr);
		$package_name = array_pop($arr);
		$category_name = array_pop($arr);

		$cp = "$category_name/$package_name";
		$cpf = "$cp/$ebuild";

		$sha1_sum = sha1_file($filename);

		if($sha1_sum === false) {
			echo "* Could not get SHA1 hash for file: $filename\n";
			continue;
		}

		$a_larry_hashes[$cpf] = $sha1_sum;

	}

	$i_larry_ebuilds = count($a_larry_hashes);

	echo "* Larry:	$i_larry_ebuilds\n";

	// Display ebuild count in database
	$sql = "SELECT COUNT(1) FROM ebuild;";
	$i_znurt_ebuilds = current(pg_fetch_row(pg_query($sql)));
	echo "* Znurt:	$i_znurt_ebuilds\n";

	// If no ebuilds, reset the sequence
	if(!$i_znurt_ebuilds) {
		$sql = "ALTER SEQUENCE ebuild_id_seq RESTART WITH 1;";
		pg_query($sql);
	}

	$sql = "SELECT cpf, hash FROM view_ebuild_filename;";
	$rs = pg_query($sql);

	// Get list of znurt ebuilds
	$a_znurt_ebuilds = array();
	while($row = pg_fetch_assoc($rs)) {
		$a_znurt_ebuilds[$row['cpf']] = $row['hash'];
	}

	// Compare ebuild arrays for insert and update
	$a_insert_ebuilds = array();
	$a_update_ebuilds = array();
	foreach($a_larry_hashes as $cpf => $hash) {

		if(!array_key_exists($cpf, $a_znurt_ebuilds)) {
			$a_insert_ebuilds[] = $cpf;
		} elseif($a_znurt_ebuilds[$cpf] != $hash) {
			$a_update_ebuilds[] = $cpf;
		}

	}

	$i_insert_count = count($a_insert_ebuilds);
	$i_update_count = count($a_update_ebuilds);

	echo "* Insert:	$i_insert_count\n";
	echo "* Update:	$i_update_count\n";

	// It is far simpler and cleaner to delete an updated ebuild that has been updated and re-import it
	foreach($a_update_ebuilds as $cpf) {

		$cpe = preg_replace('/\.ebuild$/', '', $cpf);

		$q_cpe = pg_escape_literal($cpe);

		$sql = "DELETE FROM ebuild WHERE id IN (SELECT id FROM view_ebuild_cpe WHERE cpe = $q_cpe);";

		if($rs === false) {
			echo "$sql\n";
			echo pg_last_error();
			echo "\n";
		}

		$arr_insert_ebuilds[] = $cpf;

	}

	// Resort array if new ones got added
	if($i_update_count)
		sort($arr_insert_ebuilds);

	// Insert new ebuilds
	$counter = 1;
	foreach($a_insert_ebuilds as $cpf) {

		echo "\033[K";
		echo "* Progress:	$counter/$i_insert_count\r";
		$counter++;

		$arr = explode('/', $cpf);
		$filename = array_pop($arr);
		$pf = preg_replace('/\.ebuild$/', '', $filename);
		$package_name = array_pop($arr);
		$category_name = array_pop($arr);
		$cp = "$category_name/$package_name";
		$atom = "$category_name/$pf";

		$e = new PortageEbuild($atom);

		$a_metadata = $e->metadata();

		$a_insert_ebuild = array(
			'cp' => $cp,
			'pf' => $pf, // ebuild class has some bugs which doesn't make this completely reliable, use filename's instead
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
			'hash' => $a_larry_hashes[$cpf],
			'description' => $a_metadata['description'],
			'keywords' => $a_metadata['keywords'],
			'license' => $a_metadata['license'],
			'iuse' => $a_metadata['iuse'],
		);

		$rs = pg_execute('insert_ebuild', array_values($a_insert_ebuild));

		if($rs === false) {
			echo pg_last_error();
			echo "\n";
			continue;
		}

		// Get the primary key from above using RETURNING
		$id = current(pg_fetch_row($rs));

		$a_homepages = arrHomepages($a_metadata['homepage']);
		foreach($a_homepages as $homepage) {

			$rs = pg_execute('insert_homepage', array($id, $homepage));

			if($rs === false) {
				echo pg_last_error();
				echo "\n";
				continue;
			}

		}

		// Insert ebuild keywords
		$a_keywords = arrKeywords($a_metadata['keywords'], $a_larry_arches);
		foreach($a_keywords as $arch => $status) {

			$rs = pg_execute('insert_ebuild_arch', array($id, $status, $arch));

			if($rs === false) {
				echo pg_last_error();
				echo "\n";
				continue;
			}

		}

		// Insert ebuild licenses
		$a_licenses = arrLicenses($a_metadata['license'], $a_larry_licenses);
		foreach($a_licenses as $str) {

			$rs = pg_execute('insert_ebuild_license', array($id, $str));

			if($rs === false) {
				echo pg_last_error();
				echo "\n";
				continue;
			}

		}

	}

	if($i_update_count || $i_insert_count)
		echo "\n";

	/**
	 * Create an array of the arch keywords
	 *
	 * @param string keywords
	 * @return array
	 */
	function arrHomepages($str) {

		$arr = explode(' ', $str);

		$arr_homepages = array();

		if(count($arr)) {

			foreach($arr as $str) {
				if(substr($str, 0, 4) == "http" || substr($str, 0, 6) == "ftp://" || substr($str, 0, 9) == "gopher://")
					$arr_homepages[] = $str;
			}
		}

		$arr_homepages = array_unique($arr_homepages);

		return $arr_homepages;
	}

	/**
	 * Create an array of the arch keywords
	 *
	 * @param string keywords
	 * @return array
	 */
	function arrKeywords($str, $arches) {

		if(!$str)
			return array();

		$arr = explode(' ', $str);

		if(!count($arr))
			return array($str);

		$arr_keywords = array();

		// If it has -* at all, set them all to -arch by default
		if(in_array('-*', $arr)) {
			foreach($arches as $name) {
				$arr_keywords[$name] = 2;
			}
		}

		foreach($arr as $name) {
			if($name[0] == '~' || $name[0] == '-')
				$arch = substr($name, 1);
			else
				$arch = $name;

			if($name[0] == '~') {
				$arr_keywords[$arch] = 1;
			} elseif($name[0] == '-') {
				$arr_keywords[$arch] = 2;
			} else {
				$arr_keywords[$arch] = 0;
			}
		}

		ksort($arr_keywords);

		return $arr_keywords;
	}

	/**
	 * Create an array of the ebuild's licenses
	 *
	 * @param string licenses
	 * @return array
	 */
	function arrLicenses($str, $licenses) {

		$arr = explode(' ', $str);

		if(!count($arr))
			return array();

		$arr_licenses = array();

		foreach($arr as $str) {
			if(in_array($str, $licenses))
				$arr_licenses[] = $str;
		}

		$arr_licenses = array_unique($arr_licenses);

		return $arr_licenses;
	}

	// Cleanup large variables
	unset($file_contents);
	unset($a_larry_hashes);
	unset($a_larry_ebuilds);
	unset($a_znurt_ebuilds);
	unset($a_insert_ebuilds);
	unset($a_update_ebuilds);
	unset($a_homepages);
	unset($a_keywords);
	unset($a_licenses);

	end_ebuilds:

	unset($a_larry_arches);
	unset($a_larry_licenses);

?>
