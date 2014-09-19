<?php

	// Testing parsing atoms

	require_once 'class.portage.ebuild.php';

	$ebuild = new PortageEbuild('media-video/mplayer-1.0_rc2_p20090731');

// 	print_r($ebuild);

	print_r($ebuild->getSha1Sum());

?>
