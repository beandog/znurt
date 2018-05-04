<?php

	echo "[USE Flags]\n";

	require_once 'header.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	require_once 'class.portage.use_flag.php';
	require_once 'class.portage.ebuild.php';
	require_once 'class.portage.atom.php';
	require_once '/home/steve/svn/znurt/class.db.mtime.php';
	require_once '/home/steve/svn/znurt/class.db.use.php';
	require_once 'File/Find.php';

 	// Local use flags
 	$type = 'local';
 	$u = new PortageUseFlag($type);
  	$filemtime = filemtime($u->filename);
  	$arr_use_flags[$type] = $u->getUseFlags();
  	$keys = array_keys($arr_use_flags[$type]);

  	$dbmtime = new DBMtime($u->filename);

  	foreach($arr_use_flags[$type] as $cp => $arr_package_use_flags) {
  		shell::msg($cp);

  		$sql = "SELECT package FROM view_package WHERE cp = ".$db->quote($cp);
  		$package = $db->getOne($sql);

  		foreach($arr_package_use_flags as $name => $arr) {
			$where = "package = $package";

			$sql = "SELECT COUNT(1) FROM use WHERE $where;";
			$db_count = $db->getOne($sql);

			if(is_null($dbmtime->mtime)) {



			}
		}

  	}

  	$arr_new[$type] = $arr_delete[$type] = array();

  	if(is_null($dbmtime->mtime)) {

 		$arr_new[$type] = $keys;

 		$dbmtime->mtime = $dbmtime->filemtime;

 	} elseif(($filemtime > $db_mtime) || ($db_count != count($keys))) {

 		$arr_import = importDiff('use', $keys, $where);

 		$arr_new[$type] = $arr_import['insert'];
 		$arr_delete[$type] = $arr_import['delete'];

 		$dbmtime->mtime = $filemtime;

 	}


// 	if(count($arr_diff['delete'])) {
// 		foreach($arr_diff['delete'] as $name) {
// 			$sql = "DELETE FROM $table WHERE name = ".$db->quote($name).";";
// 			$db->query($sql);
// 		}
// 	}
//
// 	if(count($arr_diff['insert'])) {
// 		foreach($arr_diff['insert'] as $name) {
// 			$arr_insert = array('name' => $name);
// 			$db->autoExecute($table, $arr_insert, MDB2_AUTOQUERY_INSERT);
// 		}
// 	}

?>
