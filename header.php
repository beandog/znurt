<?

	$hostname = php_uname('n');
	switch($hostname) {
	
		case 'charlie':
		
			$include_path = ":/home/steve/php/inc:/home/steve/svn/znurt";
			$mdb2 = "mdb2/charlie.portage.php";
		
			break;
		
		case 'rom':
		
			$include_path = ":/home/steve/php/inc:/home/steve/svn/znurt";
			$mdb2 = "mdb2/rom.portage.php";
		
			break;
		
		case 'tenforward':
		
			$include_path = ":/var/www/znurt.org/inc:/var/www/znurt.org/htdocs";
			$mdb2 = "mdb2/tenforward.portage.php";
		
			break;
		
		case 'alan-one':
		case 'znurt':
		
			$include_path = ":/var/www/znurt.org/inc:/var/www/znurt.org/htdocs";
			$mdb2 = "mdb2/alan-one.portage.php";
		
			break;
			
		case 'willy':
		case 'dumont':
		
			$include_path = ":/home/znurt/php/inc:/var/www/znurt.org/htdocs";
			$mdb2 = "mdb2/dumont.portage.php";
		
			break;
	
	}

	if($include_path) {
		ini_set('include_path', ini_get('include_path').$include_path);
		
		require_once $mdb2;
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
