<?php

	require_once 'class.portage.tree.php';
	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once 'class.portage.package.manifest.php';
	
	$manifest = new PackageManifest('app-admin', 'config_confd');
	
	print_r($manifest);
	
	print_r($manifest->getFiles());
// 	print_r($manifest->getDistfiles());
// 	print_r($manifest->getEbuilds());
// 	print_r($manifest->getMisc());
// 	
// 	print_r($manifest->getHash('mplayer-9999.ebuild'));

?>
