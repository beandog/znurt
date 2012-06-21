<?

	require_once 'header.php';
	
	if(!$tree) {
		$tree =& PortageTree::singleton();
	}
	
	require_once 'class.portage.use_flag.php';
	require_once 'class.portage.ebuild.php';
	require_once 'class.portage.atom.php';
	require_once 'class.db.mtime.php';
	require_once 'class.db.use.php';
	require_once 'File/Find.php';
	
 	// Expand use flags
	$type = 'expand';
	$arr_new[$type] = $arr_delete[$type] = $arr_use_flags[$type] = array();
	
	$arr_find = File_Find::search('desc$', $tree->getTree().'/profiles/desc/');
	 
	foreach($arr_find as $filename) {
	
		$basename = $prefix = basename($filename);
		$prefix = str_replace(".desc", "", $prefix);
		
		$u = new PortageUseFlag($type, $prefix);
		$filemtime = filemtime($u->filename);
		$arr_use_flags[$type] = array_merge($arr_use_flags[$type], $u->getUseFlags());
		$keys = array_keys($u->getUseFlags());
		$where = "prefix = ".$db->quote($prefix);
	
		$dbmtime = new DBMtime($u->filename);
		
		$sql = "SELECT COUNT(1) FROM use WHERE $where;";
		$db_count = $db->getOne($sql);
		
		if(is_null($dbmtime->mtime) || ($filemtime > $dbmtime->mtime) ) {
			$dbmtime->mtime = $filemtime;
		}
	
	}
	
	foreach($arr_use_flags[$type] as $name => $arr) {
	
		extract($arr);
	
 		$dbuse = new DBUse($name, 'expand', $prefix);
 		if($dbuse->description != $description)
 			$dbuse->description = $description;
 		if($dbuse->prefix != $prefix)
 			$dbuse->prefix = $prefix;
 	}
	
?>