<?php
	echo "[Ebuild Licenses]\n";

	require_once 'header.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';

	$rs = pg_prepare('insert_ebuild_license', 'INSERT INTO ebuild_license (ebuild, license) VALUES ($1, $2);');
	if($rs === false)
		echo pg_last_error()."\n";

	// Get the arches
	$arr_licenses = $tree->getLicenses();

	// Find all the ebuilds that are missing ebuild arch
	$sql = "SELECT ebuild, metadata FROM missing_license;";
	$arr_missing_license = $db->getAssoc($sql);

	echo "* Ebuilds: ".count($arr_missing_license)."\n";

	// Get the licenses from the database
	$db_licenses = $db->getAssoc("SELECT name, id FROM license;");

	if(count($arr_missing_license)) {

		$count = count($arr_missing_license);
		$x = 1;

		foreach($arr_missing_license as $ebuild => $str) {

			echo "\033[K";
			echo "* Progress: $x/$count\r";
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
							$rs = pg_execute('insert_ebuild_license', array($ebuild, $db_licenses[$str]));
							if($rs === false)
								echo pg_last_error()."\n";

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
