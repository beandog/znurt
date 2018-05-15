<?php

	echo "[Arches]\n";

	require_once 'header.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	// Get and display Portage's arches
	$a_tree_arches = $tree->getArches(true);
	$d_tree_arches = implode(' ', $a_tree_arches);
	echo "* Larry:	$d_tree_arches\n";

	// Display Znurt's arches
	$sql = "SELECT name FROM arch ORDER BY name;";
	$a_znurt_arches = pg_column_array(pg_fetch_all(pg_query($sql)));
	$d_znurt_arches = implode(" ", $a_znurt_arches);
	$i_znurt_arches = count($a_znurt_arches);
	echo "* Znurt:	$d_znurt_arches\n";

	// Get the difference between the two sets and display changes
	$a_import_diff = importDiff('arch', $a_tree_arches);
	$i_insert_count = count($a_import_diff['insert']);
	echo "* Insert:	$i_insert_count\n";
	if($i_insert_count) {
		$d_insert_arch = implode(' ', $a_import_diff['insert']);
		echo "* Insert:	$d_insert_arch\n";
	}
	$i_delete_count = count($a_import_diff['delete']);
	echo "* Delete:	$i_delete_count\n";
	if($i_delete_count) {
		$d_delete_arch = implode(' ', $a_import_diff['delete']);
		echo "* Delete: $d_delete_arch\n";
	}

	// Reset sequence if table is empty
	if(!$i_znurt_arches) {
		$sql = "ALTER SEQUENCE arch_id_seq RESTART WITH 1;";
		pg_query($sql);
	}

	// Delete removed arches
	foreach($a_import_diff['delete'] as $arch) {
		$q_arch = pg_escape_literal($arch);
		$sql = "DELETE FROM arch WHERE name = $q_arch;";
		pg_query($sql);
	}

	// Insert new arches
	foreach($a_import_diff['insert'] as $arch) {
		$q_arch = pg_escape_literal($arch);
		$sql = "INSERT INTO arch (name) VALUES ($q_arch);";
		pg_query($sql);
	}

?>
