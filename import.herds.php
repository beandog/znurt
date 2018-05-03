<?php

	require_once 'header.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	$table = 'herd';

	$arr = $tree->getHerds();

	$arr_diff = importDiff($table, $arr);

	// Reset sequence if table is empty
	$sql = "SELECT COUNT(1) FROM herd;";
	$count = $db->getOne($sql);
	if($count == 0) {
		$sql = "ALTER SEQUENCE herd_id_seq RESTART WITH 1;";
		$db->query($sql);
	}

	if(count($arr_diff['delete'])) {
		foreach($arr_diff['delete'] as $name) {
			$sql = "DELETE FROM $table WHERE name = ".$db->quote($name).";";
			$db->query($sql);
		}
	}

	if(count($arr_diff['insert'])) {
		foreach($arr_diff['insert'] as $name) {
			$arr_insert = array('name' => $name);
			$db->autoExecute($table, $arr_insert, MDB2_AUTOQUERY_INSERT);
		}
	}

?>
