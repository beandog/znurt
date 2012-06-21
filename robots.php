<?
	
	require_once "header.php";
	require_once "class.shell.php";
	require_once "class.portage.tree.php";
	
	$tree = new PortageTree();
	$arr = $tree->getCategories();
	
	shell::msg("User-agent: *");
	
	shell::msg("Disallow: /arch");
	shell::msg("Disallow: /useflags");
	shell::msg("Disallow: /xml");
	shell::msg("Disallow: /licenses");
	
	foreach($arr as $category_name)
		shell::msg("Disallow: /$category_name");
	
	
?>