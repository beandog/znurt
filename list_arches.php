<?php

	require_once 'header.php';

	$sql = "SELECT DISTINCT name FROM arch a INNER JOIN ebuild_arch ea ON ea.arch = a.id AND ea.status != 2;";
	$arr = $db->getCol($sql);

	foreach($arr as $name) {
		shell::msg($name);
	}

?>
