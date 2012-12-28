<?php

	error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

	// Include MDB2 credentials
	// See header.mdb2.php for generic connection settings
	// See header.dsn.php for specific connection settings for your system
	require_once 'header.mdb2.php';

	if($include_path) {
		ini_set('include_path', ini_get('include_path').$include_path);
		require_once 'class.common.php';
		require_once 'class.shell.php';
	}
	
	$now = $db->getOne("SELECT NOW();");
	
	function importDiff($table, $arr_new, $where = "") {
 		
 		$db =& MDB2::singleton();
 		
 		if($where)
 			$where = "WHERE $where";
 		
 		$sql = "SELECT name FROM $table $where ORDER BY name;";
 		$arr_old = $db->getCol($sql);
 		
 		$arr_insert = array_diff($arr_new, $arr_old);
 		$arr_delete = array_diff($arr_old, $arr_new);
 		
 		$arr = array('insert' => $arr_insert, 'delete' => $arr_delete);
 		
 		return($arr);
 	}
 	
 	// This gets used everywhere, might as well create it here
	// and check for it later.
	require_once 'class.portage.tree.php';
	$tree =& PortageTree::singleton();

?>
