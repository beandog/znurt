<?php

	echo "[Ebuild Metadata]\n";

	/**
	 * The ebuild metadata is where the database really begins to shine, as
	 * it gathers really specific details about an ebuild. However, since
	 * there's little usage or need for it while searching the available
	 * packages, and because this information is more relative to running
	 * some QA, I'm skipping all of it except for the descriptions.
	 *
	 * Even then, the descriptions are not language specific, and I'd
	 * rather access it on the frontend from the metadata.xml file
	 * instead.
	 *
	 * There's lots of *potential* uses for all of it, but importing it
	 * all increases the amount of time for this to run. It's set up right
	 * now to skip over everything not needed / wanted at the time.
	 */

	/**
	 * It may seem a little odd, and to break normalization, to have a query to set the description on the package
	 * table when it can be queried from the ebuilds.  The fact is this is just one of many shortcuts taken, since
	 * the site is a snapshot, and information like that is not required in realtime.  Not to mention it makes
	 * life a whole lot easier.
	 */

	require_once 'header.php';

	if(!$tree) {
		$tree = PortageTree::singleton();
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
	$sql = "SELECT * FROM missing_metadata;";
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

				// Znurt website only displays description right now, skip the
				// rest while the others are not needed.
				if($keyword != 'description')
					continue;

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

					// Update ebuild metadata in database
					$sql = "UPDATE package SET description = package_description(id) WHERE id = $ebuild;";

					$rs = pg_query($sql);

					if($rs === false) {
						echo "$sql\n";
						echo pg_last_error();
						echo "\n";
					}
				}
			}
		}

		$count++;

	}

?>
