<?php

	require_once 'header.php';
	
	$tree =& PortageTree::singleton();
	
	$arr_tree_licenses = $tree->getLicenses();
	
	$arr_import_diff = importDiff('license', $arr_tree_licenses);

	// Reset sequence if table is empty
	$sql = "SELECT COUNT(1) FROM license;";
	$sth = $dbh->query($sql);
	$num_db_licenses = $sth->fetchColumn();
	if($num_db_licenses === 0) {
		$sql = "ALTER SEQUENCE license_id_seq RESTART WITH 1;";
		$dbh->exec($sql);
	}
	
	if(count($arr_import_diff['delete'])) {

		$stmt = $dbh->prepare("DELETE FROM license WHERE name = :name;");
		$stmt->bindParam(':name', $name);

		foreach($arr_import_diff['delete'] as $name) {
			$stmt->execute();
		}
	}
	
	if(count($arr_import_diff['insert'])) {

		$stmt = $dbh->prepare("INSERT INTO license (name) VALUES (:name);");
		$stmt->bindParam(':name', $name);

		foreach($arr_import_diff['insert'] as $name) {
			$stmt->execute();

			if($verbose) {
				echo "import license: $name";
				echo "\n";
			}
		}
	}
	
?>
