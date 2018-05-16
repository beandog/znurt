<?php

	echo "[Categories]\n";

	require_once 'header.php';

	if(!$tree) {
		$tree = PortageTree::singleton();
	}

	require_once 'class.portage.category.php';

	// Get and display Portage's categories
	$a_tree_categories = $tree->getCategories();
	$i_tree_categories = count($a_tree_categories);
	echo "* Larry:	$i_tree_categories\n";

	// Get and display Znurt's categories
	$sql = "SELECT name FROM category ORDER BY name;";
	$a_znurt_categories = pg_column_array(pg_fetch_all(pg_query($sql)));
	$i_znurt_categories = count($a_znurt_categories);
	echo "* Znurt:	$i_znurt_categories\n";

	// Get the difference between the two sets and display changes
	$a_import_diff = importDiff('category', $a_tree_categories);
	$i_insert_count = count($a_import_diff['insert']);
	echo "* Insert:	$i_insert_count\n";
	$i_delete_count = count($a_import_diff['delete']);
	echo "* Delete:	$i_delete_count\n";

	// Reset sequence if table is empty
	if(!$i_znurt_categories) {
		$sql = "ALTER SEQUENCE category_id_seq RESTART WITH 1;";
		pg_query($sql);
	}

	// Delete removed categories
	foreach($a_import_diff['delete'] as $str) {
		$q_str = pg_escape_literal($str);
		$sql = "DELETE FROM category WHERE name = $q_str;";
		pg_query($sql);
	}

	// Insert new categories
	$import_counter = 1;
	foreach($a_import_diff['insert'] as $str) {

		echo "\033[K";
		echo "* Progress:	$import_counter/$i_insert_count\r";
		$import_counter++;

		$q_str = pg_escape_literal($str);
		$sql = "INSERT INTO category (name) VALUES ($q_str);";
		pg_query($sql);

		$c = new PortageCategory($str);

		foreach($c->getDescriptions() as $lingua => $description) {

			$q_lingua = pg_escape_literal($lingua);
			$q_description = pg_escape_literal($description);

			$sql = "INSERT INTO category_description (category, lingua, description) SELECT id, $q_lingua, $q_description FROM category WHERE name = $q_str;";

			pg_query($sql);

		}
	}

?>
