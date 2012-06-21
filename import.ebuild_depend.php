<?

	require_once 'header.php';
	
// 	$sql = "SELECT REGEXP_SPLIT_TO_TABLE(value, E'(\\\\(|\\\\))') AS value FROM ebuild_metadata WHERE keyword IN ('depend', 'rdepend') LIMIT 22;";
// 	
// 	$sql = "SELECT REGEXP_SPLIT_TO_TABLE(value, E'(!?\\[0-9a-z_-]+\\\\?|\\\\(|\\\\))') AS value FROM ebuild_metadata WHERE keyword IN ('depend', 'rdepend') LIMIT 22;";
// 	
// 	$arr = $db->getCol($sql);
// 	
// 	print_r($arr);
// 	die;
	
// 	$sql = "CREATE TEMP TABLE tmp_depend AS SELECT ebuild, REGEXP_SPLIT_TO_TABLE(value, E'\\\\s+') AS value, keyword FROM ebuild_metadata WHERE keyword IN ('depend', 'rdepend');";
	$sql = "CREATE TEMP TABLE tmp_depend AS SELECT id AS ebuild, REGEXP_SPLIT_TO_TABLE(metadata, E'\\\\s+') AS value, type AS keyword FROM missing_depend;";
	$db->query($sql);
	
	$sql= "DELETE FROM tmp_depend WHERE value NOT LIKE '%/%';";
	$db->query($sql);
	
	$sql = "INSERT INTO ebuild_depend (ebuild, package, type) SELECT ebuild, package_id(value), keyword FROM tmp_depend WHERE package_id(value) IS NOT NULL;";
	$db->query($sql);

?>