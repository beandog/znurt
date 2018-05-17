<?php

	echo "[Package Mask]\n";

	/**
	 * Import method is slightly different than others, here.
	 *
	 * Instead of waiting for the final import script to run to update
	 * masked status, figure it out here.  It's not going to be detrimental
	 * to update masks while the rest of the process imports ebuilds.
	 *
	 * So, the status check here will stop and start in these files.
	 *
	 * The ebuild_mask import script will just check all of them.
	 */

	require_once 'header.php';

	if(!$tree) {
		$tree = PortageTree::singleton();
	}

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';
	require_once 'class.portage.atom.php';
	require_once 'class.portage.package.mask.php';
	require_once 'class.db.mtime.php';

	$pmask = new PackageMask();

	$dbmtime = new DBMtime($pmask->filename);

	$import = false;

	$sql = "SELECT COUNT(1) FROM package_mask WHERE status = 0;";
	$count = current(pg_fetch_row(pg_query($sql)))

	if(is_null($dbmtime->mtime) || ($pmask->mtime > $dbmtime->mtime) || !$count) {
		$dbmtime->mtime = $pmask->mtime;
		$import = true;
	}

	if($import) {

		// Delete any previous import attempts
		$sql = "DELETE FROM package_mask WHERE status = 1;";

		$arr = $pmask->getMaskedPackages();

		$arr_pg_bool = array('false', 'true');

		function null2str($var) {

			$db = MDB2::singleton();

			if(is_null($var))
				return 'NULL';
			else
				return $db->quote($var);
		}

		foreach($arr as $str) {

			echo "\033[K";
			echo "* $str\r";

			$a = new PortageAtom($str);

			$pvr = $a->pvr;

			if(!$pvr)
				$pvr = '';

			// FIXME? The # of inserts on an empty db is very low (less than 1k) so I'm not
			// going to write a prepare statement right now.

			$sql = "INSERT INTO package_mask (package, atom, lt, gt, eq, ar, av, pf, pv, pr, pvr, alpha, beta, pre, rc, p, version, status) SELECT p.id, ".$db->quote($str).", ".$arr_pg_bool[intval($a->lt)].",  ".$arr_pg_bool[intval($a->gt)].",  ".$arr_pg_bool[intval($a->eq)].",  ".$arr_pg_bool[intval($a->ar)].",  ".$arr_pg_bool[intval($a->av)].", ".null2str($a->pf).", ".null2str($a->pv).", ".null2str($a->pr).", ".null2str($a->pvr).", ".null2str($a->_alpha).", ".null2str($a->_beta).", ".null2str($a->_pre).", ".null2str($a->_rc).", ".null2str($a->_p).", ".null2str($a->version).", 1  FROM category c INNER JOIN package p ON p.category = c.id WHERE c.name = ".$db->quote($a->category)." AND p.name = ".$db->quote($a->pn).";";
			pg_query($sql);

		}

		echo "\n";

	}

?>
