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
	
	$arr = $tree->getCategories();
	
	$arr_diff = importDiff('category', $arr);
	
	if(count($arr_diff['delete'])) {

		$stmt = $dbh->prepare("DELETE FROM category WHERE name = :name;");
		$stmt->bindParam(':name', $name);

		foreach($arr_diff['delete'] as $name) {
			$stmt->execute();
		}
	}
	
	if(count($arr_diff['insert'])) {

		$stmt_category = $dbh->prepare("INSERT INTO category (name) VALUES (:name);");
		$stmt_category->bindParam(':name', $name);

		foreach($arr_diff['insert'] as $name) {
			$stmt_category->execute();

			if($verbose) {
				echo "import category: $name";
				echo "\n";
			}
		
			$category_id = $dbh->lastInsertID('category_id_seq');
			
			$c = new PortageCategory($name);

			$stmt_category_description = $dbh->prepare("INSERT INTO category_description (category, lingua, description) VALUES (:category_id, :lingua, :description);");
			$stmt_category_description->bindParam(':category_id', $category_id);
			$stmt_category_description->bindParam(':lingua', $lingua);
			$stmt_category_description->bindParam(':description', $description);
			
			foreach($c->getDescriptions() as $lingua => $description) {
				$stmt_category_description->execute();
			}
		}
	}
	
?>
