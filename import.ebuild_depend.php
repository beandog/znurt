<?php

	echo "[Ebuild Dependencies]\n";

	require_once 'header.php';

	$sql = "CREATE TEMP TABLE tmp_depend AS SELECT id AS ebuild, REGEXP_SPLIT_TO_TABLE(metadata, E'\\\\s+') AS value, type AS keyword FROM missing_depend;";
	$db->query($sql);

	$sql= "DELETE FROM tmp_depend WHERE value NOT LIKE '%/%';";
	$db->query($sql);

	$sql = "INSERT INTO ebuild_depend (ebuild, package, type) SELECT ebuild, package_id(value), keyword FROM tmp_depend WHERE package_id(value) IS NOT NULL;";
	$db->query($sql);

?>
