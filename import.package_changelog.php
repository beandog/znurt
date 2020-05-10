<?php

	echo "[Packages]\n";

	/**
	 * Insert and update package changelogs
	 *
	 */

	require_once 'header.php';

	if(!$tree)
		$tree = PortageTree::singleton();

	// Find empty changelogs and insert
	$sql = "SELECT p.id, c.name || '/' || p.name AS cp FROM package p JOIN category c ON p.category = c.id LEFT OUTER JOIN package_changelog pc ON pc.package = p.id WHERE pc.changelog IS NULL ORDER BY c.name, p.name;";
	$rs = pg_query($sql);

	while($row = pg_fetch_assoc($rs)) {

		$id = $row['id'];
		$cp = $row['cp'];

		echo "Import changelog: $cp\n";

		$cmd = "git log $git_dir/$cp";
		chdir("$git_dir/$cp");
		exec($cmd, $output, $retval);
		$changelog = implode("\n", $output);

		if($retval == 0 && $changelog) {

			$q_changelog = pg_escape_literal($changelog);
			$sql = "INSERT INTO package_changelog (package, changelog) VALUES ($id, $q_changelog);";
			pg_query($sql);

		}

	}

?>
