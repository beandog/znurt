<?php

	echo "[Global USE Flags]\n";

	require_once 'header.php';
	require_once 'class.portage.tree.php';
	require_once 'class.portage.use_flag.php';
	require_once 'class.portage.ebuild.php';
	require_once 'class.portage.atom.php';
	require_once 'class.db.mtime.php';
	require_once 'class.db.use.php';
	require_once 'File/Find.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	// Global use flags
	$type = 'global';
	$u = new PortageUseFlag($type);
 	$filemtime = filemtime($u->filename);
 	$arr_use_flags[$type] = $u->getUseFlags();
 	$keys = array_keys($arr_use_flags[$type]);

	$where = "prefix = ''";

 	$dbmtime = new DBMtime($u->filename);

 	$sql = "SELECT COUNT(1) FROM use WHERE $where;";
 	$db_count = $db->getOne($sql);

 	$arr_new[$type] = $arr_delete[$type] = array();

 	if(is_null($dbmtime->mtime)) {
 		$dbmtime->mtime = $dbmtime->filemtime;
 	} elseif($filemtime > $dbmtime->mtime) {
 		$dbmtime->mtime = $filemtime;
 	}

 	foreach($arr_use_flags[$type] as $name => $arr) {

		echo "\033[K";
		echo "* Progress: $name\r";

 		$dbuse = new DBUse($name, $type);
 		if($dbuse->description != $arr['description'])
 			$dbuse->description = $arr['description'];
 	}

	echo "\n";

 ?>
