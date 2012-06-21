<?

	// Testing parsing packages
	
	require_once 'header.php';

	require_once 'class.portage.package.changelog.php';
	require_once '/home/steve/svn/znurt/class.db.package.changelog.php';
	require_once '/home/steve/php/inc/class.shell.php';

	$category = "media-video";
	$package = "mkvtoolnix";	

	$c = new PackageChangelog($category, $package);
	
	print_r($c->recent_changes);
	
// 	$dbc = new DBPackageChangelog(9307);
	

// 	print_r($dbc->changelog);
	
// 	$dbc->changelog = "test";
	
// 	print_r($c->recent_date);
// 	print_r($c->package);
// 	print_r($c->changelog);
	
	
 	
?>