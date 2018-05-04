<?php

	echo "[Arches]\n";

	require_once 'header.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	// FIXME This is really dumb, just grab all the arches
	// since I look at all of them now.
	$arr_arches = $tree->getArches();
	$arr_arches = array_merge($arr_arches, $tree->getArches(true));

	$arr = importDiff('arch', $arr_arches);

	// Reset sequence if table is empty
	$sql = "SELECT COUNT(1) FROM arch;";
	$count = $db->getOne($sql);
	if($count == 0) {
		$sql = "ALTER SEQUENCE arch_id_seq RESTART WITH 1;";
		$db->query($sql);
	}

	if(count($arr['delete'])) {
		foreach($arr['delete'] as $name) {
			$sql = "DELETE FROM arch WHERE name = ".$db->quote($name).";";
			$db->query($sql);
		}
	}

	if(count($arr['insert'])) {
		foreach($arr['insert'] as $name) {
			$arr_insert = array('name' => $name);
			$db->autoExecute('arch', $arr_insert, MDB2_AUTOQUERY_INSERT);
		}
	}

	unset($arr);

?>
