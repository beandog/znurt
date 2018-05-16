<?php

	echo "[Licenses]\n";

	require_once 'header.php';

	if(!$tree) {
		$tree = PortageTree::singleton();
	}

	// Get and display Portage's licenses
	$a_tree_licenses = $tree->getLicenses();
	$i_tree_licenses = count($a_tree_licenses);
	echo "* Larry:	$i_tree_licenses\n";

	// Get and display Znurt's licenses
	$sql = "SELECT name FROM license ORDER BY name;";
	$a_znurt_licenses = pg_column_array(pg_fetch_all(pg_query($sql)));
	$i_znurt_licenses = count($a_znurt_licenses);
	echo "* Znurt:	$i_znurt_licenses\n";

	// Get the difference between the two sets and display changes
	$a_import_diff = importDiff('license', $a_tree_licenses);
	$i_insert_count = count($a_import_diff['insert']);
	echo "* Insert:	$i_insert_count\n";
	$i_delete_count = count($a_import_diff['delete']);
	echo "* Delete:	$i_delete_count\n";

	// Reset sequence if table is empty
	if(!$i_znurt_licenses) {
		$sql = "ALTER SEQUENCE license_id_seq RESTART WITH 1;";
		pg_query($sql);
	}

	// Delete removed licenses
	foreach($a_import_diff['delete'] as $str) {
		$q_str = pg_escape_literal($str);
		$sql = "DELETE FROM license WHERE name = $q_str;";
		pg_query($sql);
	}

	// Insert new licenses
	foreach($a_import_diff['insert'] as $str) {
		$q_str = pg_escape_literal($str);
		$sql = "INSERT INTO license (name) VALUES ($q_str);";
		pg_query($sql);
	}

?>
