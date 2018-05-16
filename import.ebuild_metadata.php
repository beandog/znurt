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
	$rs = pg_prepare('insert_ebuild_metadata_description', 'INSERT INTO ebuild_metadata (ebuild, keyword, value) VALUES ($1, \'description\', $2);');
	if($rs === false) {
		echo pg_last_error();
		echo "\n";
	}

	// Get count of ebuilds with missing metadata
	$sql = "SELECT COUNT(1) FROM missing_metadata;";
	$i_insert_count = current(pg_fetch_row(pg_query($sql)));
	if(!$i_insert_count) {
		echo "* No ebuild metadata to import\n";
		goto end_ebuild_metadata;
	}

	echo "* Insert:	$i_insert_count\n";

	// Import ebuild metadata
	$sql = "SELECT * FROM missing_metadata\n";

	$metadata_rs = pg_query($sql);

	if($metadata_rs === false) {
		echo "$sql\n";
		echo pg_last_error();
		echo "\n";
		goto end_ebuild_metadata;
	}

	$counter = 1;

	while($row = pg_fetch_assoc($metadata_rs)) {

		echo "\033[K";
		echo "* Progress:	$counter/$i_insert_count\r";
		$counter++;

		extract($row);

		$e = new PortageEbuild("$category_name/$pf");

		$arr_metadata = $e->metadata();

		if(!array_key_exists('description', $arr_metadata))
			continue;

		$rs = pg_execute('insert_ebuild_metadata_description', array($ebuild, $arr_metadata['description']));

		if($rs === false) {
			echo pg_last_error();
			echo "\n";
		}

		// Constantly update the package's description based on the
		// highest version of the ebuild (not the one most
		// recently inserted)
		$sql = "UPDATE package SET description = package_description(id) WHERE id = $ebuild;";

		$rs = pg_query($sql);

		if($rs === false) {
			echo "$sql\n";
			echo pg_last_error();
			echo "\n";
			continue;
		}

	}

	if($i_insert_count)
		echo "\n";

	end_ebuild_metadata:

?>
