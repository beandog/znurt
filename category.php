<?
	// Testing categories

	require_once 'class.portage.tree.php';
	require_once 'class.portage.category.php';
	require_once '/home/steve/php/inc/class.shell.php';
	
	$str = 'app-text';
	
	$c = new PortageCategory($str);
	
	print_r($c->cache_dir);
	
// 	print_r($c->metadata);
	
	$xml = simplexml_load_file($c->metadata);
	
// 	print_r($xml->longdescription[0]['lang']);
	
	foreach($xml->longdescription as $obj) {
		if($obj['lang'] == "en")
			$str = (string)$obj;
	}
	
	$str = trim(preg_replace('/\s+/', ' ', $str));
	
	print_r($c->description);