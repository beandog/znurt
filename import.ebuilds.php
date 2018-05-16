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

		if($sha1_filename === false) {
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

		$arr = array(
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
		);

		$q_pr = ($arr['pr'] == NULL ? 'NULL' : $arr['pr']);
		$q_cp = pg_escape_literal($cp);

		$sql = 'INSERT INTO ebuild (package, pf, pv, pr, pvr, alpha, beta, pre, rc, p, version, slot, hash) SELECT vp.package, '
			. pg_escape_literal($arr['pf'])
			. ', '
			. pg_escape_literal($arr['pv'])
			. ', '
			. $q_pr
			. ', '
			. pg_escape_literal($arr['pvr'])
			. ', '
			. pg_escape_literal($arr['alpha'])
			. ', '
			. pg_escape_literal($arr['beta'])
			. ', '
			. pg_escape_literal($arr['pre'])
			. ', '
			. pg_escape_literal($arr['rc'])
			. ', '
			. pg_escape_literal($arr['p'])
			. ', '
			. pg_escape_literal($arr['version'])
			. ', '
			. pg_escape_literal($arr['slot'])
			. ', '
			. pg_escape_literal($arr['hash'])
			. " FROM view_package vp WHERE vp.cp = $q_cp;";

		$rs = pg_query($sql);

		if($rs === false) {
			echo "$sql\n";
			echo pg_last_error();
			echo "\n";
		}


						/*
						// Caught something my regexp can't find -- that is, recompiling the filename from the parsed values didn't work because the file doesn't exist
						if($source = '') {
							echo "FIXME - couldn't find ebuild filename\n";
							print_r($arr);
							echo "\n";
							$fixme = true;
						}

						if(!$e->filesize) {
							echo "FIXME - empty filesize for ebuild -- non-existant?\n";
							print_r($arr);
							echo "\n";
							$fixme = true;
						}

						if(!$e->hash) {
							echo "FIXME - empty hash for ebuild -- non-existant?\n";
							print_r($arr);
							echo "\n";
							$fixme = true;
						}

						if($fixme)
							continue;

						$rs = pg_execute('insert_ebuild', array_values($arr));
						if($rs === false) {
							echo pg_last_error()."\n";
							echo "\n";
						}
						*/

	}

	if($i_update_count)
		echo "\n";

	end_ebuilds:

?>
