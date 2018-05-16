<?php

	// FIXME this runs way too slow. The reason is because there's an index on the database
	// for unique ebuild ID and homepage string. The code should check it for duplicate
	// values instead, and drop the database constraint.

	// FIXME do multi-value inserts, all one ebuild at once

	echo "[Ebuild Homepages]\n";

	require_once 'header.php';

	if(!$tree) {
		$tree = PortageTree::singleton();
	}

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';

	$rs = pg_prepare('insert_ebuild_homepage', 'INSERT INTO ebuild_homepage (ebuild, homepage) VALUES ($1, $2);');
	if($rs === false)
		echo pg_last_error()."\n";

	// Find all the ebuilds that are missing ebuild arch
	$sql = "SELECT ebuild, metadata FROM missing_homepage;";
	$arr_missing_homepage = $db->getAssoc($sql);

	$count = 0;
	$num_missing = count($arr_missing_homepage);

	if($num_missing) {

		foreach($arr_missing_homepage as $ebuild => $str) {

			$percent_complete = round((++$count / count($arr_missing_homepage)) * 100);
			$d_remaining_count = str_pad($count, strlen($num_missing), 0, STR_PAD_LEFT);
			$d_percent_complete = str_pad($percent_complete, 2, 0, STR_PAD_LEFT)."% ($d_remaining_count/$num_missing)";
			echo "Progress: $d_percent_complete\r";

			if(!empty($str)) {
				$arr = arrHomepages($str);

				if(count($arr)) {
					foreach($arr as $url) {

						$arr_insert = array(
							'ebuild' => $ebuild,
							'homepage' => $url,
						);

						$rs = pg_execute('insert_ebuild_homepage', array_values($arr_insert));
						if($rs === false) {
							echo pg_last_error()."\n";
							echo "Import ebuild_homepage failed:\n";
							print_r($arr_insert);
							echo "\n";
						}
					}
				}
			}
		}
		echo "\n";
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
