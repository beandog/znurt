<?php

	require_once 'header.php';
	
	if(!$tree) {
		$tree =& PortageTree::singleton();
	}
	
	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';

	$rs = pg_prepare('insert_ebuild_arch', 'INSERT INTO ebuild_arch (ebuild, arch, status) VALUES ($1, $2, $3);');
	if($rs === false)
		echo pg_last_error()."\n";
	
 	$verbose = true;
// 	$qa = true;	
	
	// Get the arches
	$arr_arches = $tree->getArches();
	
	// Find all the ebuilds that are missing ebuild arch
	$sql = "SELECT ebuild, metadata FROM missing_arch ORDER BY ebuild;";
	$arr_missing_arch = $db->getAssoc($sql);
	
	if($verbose)
		shell::msg(count($arr_missing_arch)." ebuilds to check");
	
	// Get the arches from the database
	$db_arches = $db->getAssoc("SELECT name, id FROM arch ORDER BY arch;");
	
	//FIXME rewrite this entire thing in SQL
	if(count($arr_missing_arch)) {

		$x = 1;
		$count = count($arr_missing_arch);

		foreach($arr_missing_arch as $ebuild => $keywords) {

			shell::msg("ebuild arch: $ebuild ($x/$count)");
			$x++;
			
			if(!empty($keywords))
				$arr = arrKeywords($keywords, $arr_arches);
			else {
				$arr = array();
			}
			
			// Status in this case is the keyword, not the import status
			if(count($arr)) {
				foreach($arr as $arch => $status) {
				
					if($db_arches[$arch]) {

						$ebuild_arch = $db_arches[$arch];
						
						$arr_insert = array(
							'ebuild' => $ebuild,
							'arch' => $db_arches[$arch],
							'status' => $status,
						);

						$rs = pg_execute('insert_ebuild_arch', array($ebuild, $ebuild_arch, $status));
						if($rs === false) {
							echo pg_last_error()."\n";
							echo "import ebuild arch failed:\n";
							print_r($arr_insert);
						}

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
	function arrKeywords($str, $arches) {
		
		$arr = explode(' ', $str);
		
		$arr_keywords = array();
		
		if(count($arr)) {
			
			// If it has -* at all, set them all to -arch by default
			if(in_array('-*', $arr)) {
				foreach($arches as $name) {
					$arr_keywords[$name] = 2;
				}
			}
			
			foreach($arr as $name) {
				if($name[0] == '~' || $name[0] == '-')
					$arch = substr($name, 1);
				else
					$arch = $name;
					
				if($name[0] == '~') {
					$arr_keywords[$arch] = 1;
				}
				elseif($name[0] == '-') {
					$arr_keywords[$arch] = 2;
				}
				else {
					$arr_keywords[$arch] = 0;
				}
			}
		}
		
		ksort($arr_keywords);
		
		return $arr_keywords;
	}
	
	
	
?>
