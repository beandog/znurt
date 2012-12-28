<?php

	require_once 'header.php';
	
	$tree =& PortageTree::singleton();
	
	$arr_arches = $tree->getArches();
	
	$arr_import_diff = importDiff('arch', $arr_arches);

	// Reset sequence if table is empty
	$sql = "SELECT COUNT(1) FROM arch;";
	$sth = $dbh->query($sql);
	$num_db_arches = $sth->fetchColumn();
	if($num_db_arches === 0) {
		$sql = "ALTER SEQUENCE arch_id_seq RESTART WITH 1;";
		$dbh->exec($sql);
	}

	if(count($arr_import_diff['delete'])) {

		$stmt = $dbh->prepare("DELETE FROM arch WHERE name = :name;");
		$stmt->bindParam(':name', $name);

		foreach($arr_import_diff['delete'] as $name) {
			$stmt->execute();
		}
	}
	
	if(count($arr_import_diff['insert'])) {

		sort($arr_import_diff['insert']);

		$stmt = $dbh->prepare("INSERT INTO arch (name) VALUES (:name);");
		$stmt->bindParam(':name', $name);

		foreach($arr_import_diff['insert'] as $name) {
			$stmt->execute();
		}
	}
	
?>
