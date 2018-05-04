<?php

	echo "[Ebuild Metadata]\n";

	/**
	 * It may seem a little odd, and to break normalization, to have a query to set the description on the package
	 * table when it can be queried from the ebuilds.  The fact is this is just one of many shortcuts taken, since
	 * the site is a snapshot, and information like that is not required in realtime.  Not to mention it makes
	 * life a whole lot easier.
	 */

	require_once 'header.php';

	if(!$tree) {
		$tree =& PortageTree::singleton();
	}

	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.ebuild.php';

	// Postgres
	$rs = pg_prepare('insert_ebuild_metadata', 'INSERT INTO ebuild_metadata (ebuild, keyword, value) VALUES ($1, $2, $3);');
	if($rs === false) {
		echo pg_last_error();
		echo "\n";
	}

	// Find all the ebuilds that are missing ebuild arch
	$sql = "SELECT * FROM missing_metadata ORDER BY category_name, package_name, pf;";
	$arr = $db->getAll($sql);
	$num_ebuilds = count($arr);
	$d_num_ebuilds = number_format($num_ebuilds);

	echo "* Remaining # of ebuilds to check: $d_num_ebuilds\n";

	$total = count($arr);
	$count = 0;

	foreach($arr as $row) {

		extract($row);

		$e = new PortageEbuild("$category_name/$pf");

		$percent_complete = round(($count / $num_ebuilds) * 100);
		$d_remaining_count = str_pad($count, strlen($num_ebuilds), 0, STR_PAD_LEFT);
		$d_percent_complete = str_pad($percent_complete, 2, 0, STR_PAD_LEFT)."% ($d_remaining_count/$num_ebuilds)";

		echo "\033[K";
		echo "* Progress: $d_percent_complete $category_name/$e\r";

		$arr_metadata = $e->metadata();

		if(count($arr_metadata)) {

			foreach($arr_metadata as $keyword => $value) {

				if(!empty($value)) {
					$arr_insert = array(
						'ebuild' => $ebuild,
						'keyword' => $keyword,
						'value' => $value,
					);

					pg_execute('insert_ebuild_metadata', array_values($arr_insert));
					if($rs === false) {
						echo pg_last_error();
						echo "\n";
					}
				}
			}
		}

		$count++;

	}

	// Set the new package descriptions
	$sql = "SELECT COUNT(1) FROM package WHERE status = 1 OR description = '';";
	$count = $db->getOne($sql);
	$total = 1;
	echo "* Update package descriptions\n";
	echo "* Processing query in background, package.description will be empty until finished\n";
	// This will take a very long time, so throw it to run off asynchronously

	$sql = "UPDATE package SET description = package_description(id) WHERE id IN (SELECT id FROM package WHERE description = '');";
	$bool = pg_send_query($pg, $sql);
	if($bool === false) {
		echo "* Running $sql failed\n";
		$rs = pg_get_result();
		var_dump($rs);
		echo "\n";
	}

?>
