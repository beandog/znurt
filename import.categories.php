<?php

	require_once 'header.php';
	
	$tree =& PortageTree::singleton();
	
	require_once 'class.portage.category.php';
	
	// Reset sequence if table is empty
	$sql = "SELECT COUNT(1) FROM category;";
	$sth = $dbh->query($sql);
	$num_db_categories = $sth->fetchColumn();
	if($num_db_categories === 0) {
		$sql = "ALTER SEQUENCE category_id_seq RESTART WITH 1;";
		$dbh->exec($sql);
	}
	
	$arr_tree_categories = $tree->getCategories();
	
	$arr_import_diff = importDiff('category', $arr_tree_categories);
	
	if(count($arr_import_diff['delete'])) {

		$stmt = $dbh->prepare("DELETE FROM category WHERE name = :name;");
		$stmt->bindParam(':name', $name);

		foreach($arr_import_diff['delete'] as $name) {
			$stmt->execute();
		}
	}
	
	// FIXME
	// If there are new / updated category descriptions, add those to
	// the database, regardless of whether the category is in there
	// or not.
	if(count($arr_import_diff['insert'])) {

		$stmt_category = $dbh->prepare("INSERT INTO category (name) VALUES (:name);");
		$stmt_category->bindParam(':name', $name);

		foreach($arr_import_diff['insert'] as $name) {
			$stmt_category->execute();

			if($verbose) {
				echo "import category: $name";
				echo "\n";
			}
		
			$db_category_id = $dbh->lastInsertID('category_id_seq');
			
			$obj_portage_category = new PortageCategory($name);

			$stmt_category_description = $dbh->prepare("INSERT INTO category_description (category, lingua, description) VALUES (:category_id, :lingua, :description);");
			$stmt_category_description->bindParam(':category_id', $db_category_id);
			$stmt_category_description->bindParam(':lingua', $lingua);
			$stmt_category_description->bindParam(':description', $description);
			
			foreach($obj_portage_category->getDescriptions() as $lingua => $description) {
				$stmt_category_description->execute();
			}
		}
	}

	// Cleanup
	$arr_import_diff = null;
	$arr_tree_categories = null;
	$obj_portage_category = null;
	
?>
