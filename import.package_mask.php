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

	$rs = pg_prepare('insert_package_mask', 'INSERT INTO package_mask (package, atom, lt, gt, eq, ar, av, pf, pv, pr, pvr, alpha, beta, pre, rc, p, version) SELECT p.id, $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16 FROM category c INNER JOIN package p ON p.category = c.id WHERE c.name = $17 AND p.name = $18;');

	if($rs === false) {
		echo pg_last_error();
		echo "\n";
		exit;
	}

	// Reset table
	$sql = "DELETE FROM package_mask;";
	pg_query($sql);

	$a_larry_pmask = $pmask->getMaskedPackages();

	// The package.mask file is appended to by the top, so reverse the order
	// of the filename contents so oldest masked gets inserted first.
	$a_larry_pmask = array_reverse($a_larry_pmask);

	$arr_pg_bool = array('false', 'true');

	foreach($a_larry_pmask as $atom) {

		echo "* $atom\n";

		$a = new PortageAtom($atom);

		$pvr = $a->pvr;

		if(!$a->pvr)
			$a->pvr = '';

		if(is_null($a->version))
			$a->version = '';

		if(is_null($a->pf))
			$a->pf = '';

		if(is_null($a->pv))
			$a->pv = '';

		$arr = array(
			'atom' => $atom,
			'lt' => $arr_pg_bool[$a->lt],
			'gt' => $arr_pg_bool[$a->gt],
			'eq' => $arr_pg_bool[$a->eq],
			'ar' => $arr_pg_bool[$a->ar],
			'av' => $arr_pg_bool[$a->av],
			'pf' => $a->pf,
			'pv' => $a->pv,
			'pr' => $a->pr,
			'pvr' => $a->pvr,
			'alpha' => $a->alpha,
			'beta' => $a->beta,
			'pre' => $a->pre,
			'rc' => $a->rc,
			'p' => $a->p,
			'version' => $a->version,
			'category' => $a->category,
			'name' => $a->pn,
		);

		$rs = pg_execute('insert_package_mask', array_values($arr));

		if($rs === false) {
			echo pg_last_error();
			echo "\n";
			continue;
		}

	}

?>
