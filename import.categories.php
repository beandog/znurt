<?php

	echo "[Categories]\n";

	require_once 'header.php';

	$tree =& PortageTree::singleton();

	require_once 'class.portage.category.php';

	$sql = "SELECT COUNT(1) FROM category;";
	$count = $db->getOne($sql);

	// If no categories, reset the sequence
	if($count === "0") {
		$sql = "ALTER SEQUENCE category_id_seq RESTART WITH 1;";
		$db->query($sql);
	}

	$arr = $tree->getCategories();

	$arr_diff = importDiff('category', $arr);

	if(count($arr_diff['delete'])) {
		foreach($arr_diff['delete'] as $name) {
			$sql = "DELETE FROM category WHERE name = ".$db->quote($name).";";
			shell::msg($sql);
			$db->query($sql);
		}
	}

	if(count($arr_diff['insert'])) {
		foreach($arr_diff['insert'] as $name) {
			$arr_insert = array('name' => $name);
			$db->autoExecute('category', $arr_insert, MDB2_AUTOQUERY_INSERT);

			$category_id = $db->lastInsertID();

			$c = new PortageCategory($name);

			foreach($c->getDescriptions() as $lingua => $description) {

				$arr_insert = array(
					'category' => $category_id,
					'lingua' => $lingua,
					'description' => $description,
				);

				$db->autoExecute('category_description', $arr_insert, MDB2_AUTOQUERY_INSERT);

			}

		}

	}

?>
