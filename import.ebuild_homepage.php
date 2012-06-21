<?

	require_once 'header.php';
	
	if(!$tree) {
		$tree =& PortageTree::singleton();
	}
	
	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';
	
	// Find all the ebuilds that are missing ebuild arch
	$sql = "SELECT ebuild, metadata FROM missing_homepage;";
	$arr_missing_homepage = $db->getAssoc($sql);
	
	if($verbose)
		shell::msg(count($arr)." ebuilds to check");
	
	if(count($arr_missing_homepage)) {
		foreach($arr_missing_homepage as $ebuild => $str) {
			
			if(!empty($str)) {
				$arr = arrHomepages($str);
			
				if(count($arr)) {
					foreach($arr as $url) {
					
						$arr_insert = array(
							'ebuild' => $ebuild,
							'homepage' => $url,
						);
						
						$db->autoExecute('ebuild_homepage', $arr_insert, MDB2_AUTOQUERY_INSERT);
					}
				}
			}
		}
	}
	
	/**
	 * Create an array of the arch keywords
	 *
	 * @param string keywords
	 * @return array
	 */
	function arrHomepages($str) {
		
		$arr = explode(' ', $str);
		
		$arr_keywords = array();
		
		if(count($arr)) {
			
			foreach($arr as $str) {
				if(substr($str, 0, 4) == "http" || substr($str, 0, 6) == "ftp://" || substr($str, 0, 9) == "gopher://")
					$arr_homepages[] = $str;
			}
		}
		
		return $arr_homepages;
	}
	
	
?>