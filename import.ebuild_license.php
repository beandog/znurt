<?php

	require_once 'header.php';
	
	if(!$tree) {
		$tree =& PortageTree::singleton();
	}
	
	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';

	// Get the arches
	$arr_licenses = $tree->getLicenses();
	
	// Find all the ebuilds that are missing ebuild arch
	$sql = "SELECT ebuild, metadata FROM missing_license;";
	$arr_missing_license = $db->getAssoc($sql);
	
	shell::msg(count($arr_missing_license)." ebuilds to check");
		
	// Get the licenses from the database
	$db_licenses = $db->getAssoc("SELECT name, id FROM license;");
	
	if(count($arr_missing_license)) {
	
		$count = count($arr_missing_license);
		$x = 1;

		foreach($arr_missing_license as $ebuild => $str) {

			shell::msg("$x/$count");
			$x++;
			
			if(!empty($str)) {
				$arr = arrLicenses($str, $arr_licenses);
			
				if(count($arr)) {
					foreach($arr as $str) {
						if($db_licenses[$str]) {
							$arr_insert = array(
								'ebuild' => $ebuild,
								'license' => $db_licenses[$str],
							);
							
							$db->autoExecute('ebuild_license', $arr_insert, MDB2_AUTOQUERY_INSERT);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Create an array of the ebuild's licenses
	 *
	 * @param string licenses
	 * @return array
	 */
	function arrLicenses($str, $licenses) {
		
		$arr = explode(' ', $str);

		$arr_licenses = array();
		
		if(count($arr)) {
			
			foreach($arr as $str) {
				if(in_array($str, $licenses))
					$arr_licenses[] = $str;
			}
			
			$arr_licenses = array_unique($arr_licenses);

		}
		
		return $arr_licenses;
	}
	
	
?>
