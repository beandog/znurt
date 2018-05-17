<?php

	echo "[Ebuild Extended Versions]\n";

	require_once 'header.php';
	require_once 'import.functions.php';

	// Also fix the levels
	echo "* Fixing levels\n";
	$sql = "UPDATE ebuild e SET lvl = CASE
            WHEN e.p IS NOT NULL THEN 6
            WHEN e.rc IS NOT NULL THEN 4
            WHEN e.pre IS NOT NULL THEN 3
            WHEN e.beta IS NOT NULL THEN 2
            WHEN e.alpha IS NOT NULL THEN 1
            ELSE 5
        END WHERE e.status = 1 OR e.lvl = 0;";
	pg_query($sql);

	$sql = "SELECT * FROM missing_ev;";
	$arr = $db->getAll($sql);

	$arr_packages = array();

	foreach($arr as $row) {
		extract($row);
		$arr_packages[$package][$ebuild] = $version;
	}

	$count_packages = count($arr_packages);

	foreach($arr_packages as $package => $arr) {

		echo "\033[K";
		echo "* Progress: $package/$count_packages\r";

		$ext = extendVersions($arr);

		foreach($ext as $ebuild => $ev) {
			$arr_update = array('ev' => $ev);
			$db->autoExecute('ebuild', $arr_update, MDB2_AUTOQUERY_UPDATE, "id = $ebuild");
		}

	}

	if($count_packages)
		echo "\n";

?>
