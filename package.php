<?

	// Testing parsing packages

	require_once 'class.portage.tree.php';
	require_once 'class.portage.category.php';
	require_once 'class.portage.package.php';
	require_once '/home/steve/php/inc/class.shell.php';

	$category = "media-video";
	$package = "mplayer";	

	$p = new PortagePackage($category, $package);
	

// 	shell::msg($p);
	
// 	print_r($p->getEbuilds());
// 	shell::msg($p->getCategory());
// 	shell::msg($p->getChangelog());
// 	shell::msg($p->getChangelogHash());
// 	shell::msg($p->getChangelogMtime());
// 	print_r($p->getHerds());
//  	shell::msg($p->getMaintainers());
//  	shell::msg($p->getManifest());
//   	shell::msg($p->getManifestHash());
//  	shell::msg($p->getManifestMtime());
//  	shell::msg($p->getMetadata());
//  	shell::msg($p->getMetadataHash());
//  	shell::msg($p->getMetadataMtime());
//   	print_r($p->getMetadataXML());
//  	print_r($p->getUseFlags());
// 	print_r($p->());
// 	print_r($p->());

// 	print_r($p->portage_mtime);
// 	print_r($p->changelog_mtime);
// 	print_r($p->metadata_mtime);
	print_r($p->manifest_mtime);
	
 	
?>
