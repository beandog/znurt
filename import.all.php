<?php

	echo "[Start Import]\n";

	require_once 'header.php';

	require_once 'import.arches.php';
	require_once 'import.licenses.php';
	require_once 'import.categories.php';
	require_once 'import.packages.php';
	require_once 'import.package_changelog.php';
	require_once 'import.ebuilds.php';
	// require_once 'import.package_mask.php';
	// require_once 'import.ebuild_mask.php';
	require_once 'import.ebuild_ev.php';
	require_once 'import.use_global.php';
	require_once 'import.use_local.php';
	require_once 'import.use_expand.php';
	require_once 'import.ebuild_use.php';

?>
