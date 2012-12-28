<?php

// 	$verbose = true;
// 	$qa = true;	

	require_once 'header.php';
	
	$sql = "DROP TABLE IF EXISTS tmp_ebuild_use;";
	$db->query($sql);
	
	$sql = "CREATE TEMP TABLE tmp_ebuild_use AS SELECT eu.id AS ebuild, REGEXP_SPLIT_TO_TABLE(metadata, E'\\\\s+') AS name FROM missing_use eu;";
	$db->query($sql);
	
	$sql = "UPDATE tmp_ebuild_use SET name = SUBSTR(name, 2) WHERE SUBSTR(name, 1, 1) = '+' OR SUBSTR(name, 1, 1) = '-';";
	$db->query($sql);
	
	$sql = "INSERT INTO ebuild_use SELECT DISTINCT eu.ebuild, u.id FROM tmp_ebuild_use eu INNER JOIN use u ON u.name = eu.name;";
	$db->query($sql);
	
	
	
?>
